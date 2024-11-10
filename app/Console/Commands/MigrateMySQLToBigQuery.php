<?php

namespace App\Console\Commands;

use App\Services\BigQueryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Core\ExponentialBackoff;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Exception;

class MigrateMySQLToBigQuery extends Command
{
    protected $signature = 'migrate:mysql-to-bigquery 
                          {--chunk=1000 : The number of records to process at a time}
                          {--table= : Specific table to migrate (optional)}
                          {--retry=3 : Number of retry attempts for failed operations}
                          {--parallel=1 : Number of parallel processes}
                          {--verify : Verify data after migration}
                          {--force : Force migration even if tables exist}';

    protected $description = 'Migrate data from MySQL to BigQuery with verification and parallel processing';

    protected BigQueryService $bigQueryService;
    protected array $stats = [];
    protected array $failedRecords = [];
    protected $startTime;

    public function __construct(BigQueryService $bigQueryService)
    {
        parent::__construct();
        $this->bigQueryService = $bigQueryService;
    }

    public function handle(): int
    {
        $this->startTime = microtime(true);
        $this->info('Starting MySQL to BigQuery migration...');
        
        try {
            $dataset = $this->bigQueryService->initializeDataset();
            $this->info('Dataset initialized successfully.');

            $tablesToMigrate = $this->getTargetTables();
            $this->verifyAndCreateTables($tablesToMigrate);
            
            foreach ($tablesToMigrate as $table) {
                $this->migrateTable($table);
            }

            if ($this->option('verify')) {
                $this->verifyMigration($tablesToMigrate);
            }

            $this->displaySummary();
            return 0;
        } catch (Exception $e) {
            $this->error("Migration failed: " . $e->getMessage());
            Log::error("Migration failed: ", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function migrateTable(string $table): void
    {
        $this->info("Starting migration for table: $table");
        
        // Initialize stats
        $this->stats[$table] = ['total' => 0, 'migrated' => 0, 'failed' => 0];
        
        // Get total count
        $totalRecords = DB::table($table)->count();
        $this->stats[$table]['total'] = $totalRecords;
        
        $chunkSize = (int) $this->option('chunk');
        $tempFile = storage_path("app/temp/{$table}_export.jsonl");

        // Ensure temp directory exists
        if (!file_exists(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }
        
        $bar = $this->output->createProgressBar($totalRecords);
        $fileHandle = fopen($tempFile, 'w');

        try {
            // Export data to JSONL file
            DB::table($table)
                ->orderBy('id')
                ->chunk($chunkSize, function ($records) use ($fileHandle, $bar, $table) {
                    foreach ($records as $record) {
                        $formattedRecord = $this->formatRecordForBigQuery($record);
                        fwrite($fileHandle, json_encode($formattedRecord) . "\n");
                        $bar->advance();
                        $this->stats[$table]['migrated']++;
                    }
                });

            fclose($fileHandle);
            $bar->finish();
            $this->newLine(2);

            // Upload the file to BigQuery
            $this->info("Uploading data to BigQuery...");
            $this->loadDataToBigQuery($table, $tempFile);

            // Cleanup
            unlink($tempFile);
            
        } catch (Exception $e) {
            if (is_resource($fileHandle)) {
                fclose($fileHandle);
            }
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw $e;
        }
    }

    protected function loadDataToBigQuery(string $table, string $filepath): void
    {
        // Load the data into the table from the file
        $jobConfig = $this->bigQueryService->getTable($table)->load(fopen($filepath, 'r'), [
            'sourceFormat' => 'NEWLINE_DELIMITED_JSON',
            'writeDisposition' => 'WRITE_APPEND',
            'ignoreUnknownValues' => true,
        ]);

        // Start the job
        $job = $this->bigQueryService->runJob($jobConfig);

        // Polling for job completion
        while (true) {
            $jobInfo = $job->info();

            // Check if the job has completed
            if (isset($jobInfo['status']['state']) && $jobInfo['status']['state'] === 'DONE') {
                // If there are errors, throw an exception
                if (isset($jobInfo['status']['errorResult'])) {
                    $error = $jobInfo['status']['errorResult'];
                    throw new Exception("Failed to load data to BigQuery: " . json_encode($error));
                }
    
                // Success
                $this->info("Data loaded successfully to BigQuery table: $table");
                break;
            }
    
            // Delay before the next check to avoid rate limiting
            sleep(1);
        }
    }


    protected function formatRecordForBigQuery($record): array
    {
        $data = (array) $record;

        // Convert timestamps to ISO 8601 format
        foreach (['created_at', 'updated_at', 'deleted_at', 'processed_at'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = $data[$field] ? date('c', strtotime($data[$field])) : null;
            }
        }
        
        // Convert JSON fields
        if (isset($data['result_data'])) {
            $data['result_data'] = json_encode($data['result_data']);
        }
        
        if (isset($data['confidence_score'])) {
            $data['confidence_score'] = (float) $data['confidence_score'];
        }
        
        if (isset($data['processing_time_ms'])) {
            $data['processing_time_ms'] = (int) $data['processing_time_ms'];
        }
        
        return $data;
    }


    protected function processBatch(string $table, Collection $records, $progressBar): void
    {
        $batchData = [];
        
        foreach ($records as $record) {
            try {
                $batchData[] = $this->formatRecordForBigQuery($record);
                $progressBar->advance();
                $this->stats[$table]['migrated']++;
            } catch (Exception $e) {
                $this->handleRecordError($table, $record->id, $e);
            }
        }
        
        if (!empty($batchData)) {
            $this->insertBatchWithRetry($table, $batchData);
        }
    }

    protected function verifyMigration(array $tables): void
    {
        $this->info('Verifying migration...');
        
        foreach ($tables as $table) {
            $mysqlCount = DB::table($table)->count();
            $bigQueryCount = $this->bigQueryService->getTableCount($table);
            
            $this->info("Table: $table");
            $this->info("  MySQL count: $mysqlCount");
            $this->info("  BigQuery count: $bigQueryCount");
            
            if ($mysqlCount !== $bigQueryCount) {
                $this->warn("  ⚠️ Count mismatch for table $table!");
                $this->verifyDataIntegrity($table);
            } else {
                $this->info("  ✅ Counts match!");
            }
        }
    }

    protected function verifyDataIntegrity(string $table): void
    {
        $this->info("Performing detailed integrity check for $table...");
        
        $mysqlRecords = DB::table($table)->pluck('id')->toArray();
        $bigQueryRecords = collect($this->bigQueryService->queryAnalyses(["table_name = '$table'"]))
            ->pluck('id')
            ->toArray();
            
        $missingInBigQuery = array_diff($mysqlRecords, $bigQueryRecords);
        $extraInBigQuery = array_diff($bigQueryRecords, $mysqlRecords);
        
        if (!empty($missingInBigQuery)) {
            $this->warn("Records missing in BigQuery: " . count($missingInBigQuery));
            $this->failedRecords[$table] = array_merge(
                $this->failedRecords[$table] ?? [],
                $missingInBigQuery
            );
        }
        
        if (!empty($extraInBigQuery)) {
            $this->warn("Extra records in BigQuery: " . count($extraInBigQuery));
        }
    }

    protected function getTargetTables(): array
    {
        $specifiedTable = $this->option('table');
        $availableTables = array_keys($this->bigQueryService->getTableSchemas());
        
        if ($specifiedTable) {
            if (!in_array($specifiedTable, $availableTables)) {
                throw new Exception("Table '$specifiedTable' is not configured for migration.");
            }
            return [$specifiedTable];
        }
        
        return $availableTables;
    }

    protected function verifyAndCreateTables(array $tables): void
    {
        $this->info('Verifying and creating tables...');
        
        foreach ($tables as $table) {
            $this->info("Checking table: $table");
            
            try {
                if ($this->bigQueryService->getTable($table)->exists() && !$this->option('force')) {
                    if (!$this->confirm("Table '$table' already exists. Do you want to proceed with migration?")) {
                        throw new Exception("Migration aborted by user.");
                    }
                } else {
                    $this->info("Creating table: $table");
                    $createMethod = "create" . ucfirst($table) . "Table";
                    $this->bigQueryService->$createMethod();
                    $this->info("Table $table created successfully.");
                }
            } catch (Exception $e) {
                $this->error("Failed to verify/create table $table: " . $e->getMessage());
                throw $e;
            }
        }
    }

    protected function migrateTableWithParallelProcessing(string $table): void
    {
        $this->info("Starting migration for table: $table");
        
        // Initialize stats
        $this->stats[$table] = ['total' => 0, 'migrated' => 0, 'failed' => 0];
        
        // Get total count and calculate chunks
        $totalRecords = DB::table($table)->count();
        $this->stats[$table]['total'] = $totalRecords;
        
        $parallelProcesses = (int) $this->option('parallel');
        $chunkSize = (int) $this->option('chunk');
        
        if ($parallelProcesses > 1) {
            $this->migrateInParallel($table, $totalRecords, $chunkSize, $parallelProcesses);
        } else {
            $this->migrateSequentially($table, $totalRecords, $chunkSize);
        }
    }

    protected function migrateInParallel(string $table, int $totalRecords, int $chunkSize, int $parallelProcesses): void
    {
        $chunks = ceil($totalRecords / $chunkSize);
        $chunksPerProcess = ceil($chunks / $parallelProcesses);
        
        $processes = [];
        for ($i = 0; $i < $parallelProcesses; $i++) {
            $startChunk = $i * $chunksPerProcess;
            $endChunk = min(($i + 1) * $chunksPerProcess, $chunks);
            
            $processes[] = Process::start(
                "php artisan migrate:mysql-to-bigquery-chunk --table={$table} " .
                "--start={$startChunk} --end={$endChunk} --chunk={$chunkSize}"
            );
        }
        
        $bar = $this->output->createProgressBar($totalRecords);
        
        foreach ($processes as $process) {
            $result = $process->wait();
            
            if (!$result->successful()) {
                throw new Exception("Parallel migration failed: " . $result->errorOutput());
            }
            
            $stats = json_decode($result->output(), true);
            $this->mergeStats($table, $stats);
            $bar->advance($stats['migrated']);
        }
        
        $bar->finish();
        $this->newLine(2);
    }

    protected function migrateSequentially(string $table, int $totalRecords, int $chunkSize): void
    {
        $bar = $this->output->createProgressBar($totalRecords);
        
        try {
            DB::table($table)
                ->orderBy('id')
                ->chunk($chunkSize, function ($records) use ($table, $bar) {
                    $this->processBatch($table, $records, $bar);
                });
                
            $bar->finish();
            $this->newLine(2);
            
        } catch (Exception $e) {
            $this->error("Failed to migrate table $table: " . $e->getMessage());
            throw $e;
        }
    }

    protected function insertBatchWithRetry(string $table, array $batchData): void
    {
        $backoff = new ExponentialBackoff((int) $this->option('retry'));
        
        try {
            $backoff->execute(function () use ($table, $batchData) {
                $insertMethod = "batchInsert" . ucfirst($table);
                $result = $this->bigQueryService->$insertMethod($batchData);
                
                if (!$result->isSuccessful()) {
                    throw new Exception("Batch insert failed: " . json_encode($result->failedRows()));
                }
                
                $this->stats[$table]['migrated'] += count($batchData);
                
                // Cache successful migration progress
                Cache::put(
                    "migration_progress_{$table}",
                    $this->stats[$table],
                    now()->addHours(24)
                );
            });
        } catch (Exception $e) {
            $this->stats[$table]['failed'] += count($batchData);
            foreach ($batchData as $record) {
                $this->failedRecords[$table][] = $record['id'];
            }
            throw $e;
        }
    }

    protected function displaySummary(): void
    {
        $endTime = microtime(true);
        $duration = round($endTime - $this->startTime, 2);
        
        $this->info("\nMigration Summary:");
        $this->info("Duration: {$duration} seconds");
        
        foreach ($this->stats as $table => $stat) {
            $this->info("\nTable: $table");
            $this->info("  Total records: {$stat['total']}");
            $this->info("  Successfully migrated: {$stat['migrated']}");
            $this->info("  Failed: {$stat['failed']}");
            
            if ($stat['failed'] > 0) {
                $this->warn("  Failed records have been logged for retry.");
            }
            
            $successRate = round(($stat['migrated'] / $stat['total']) * 100, 2);
            $this->info("  Success rate: {$successRate}%");
        }
        
        if (!empty($this->failedRecords)) {
            $this->saveFailedRecords();
        }
    }

    protected function saveFailedRecords(): void
    {
        $path = storage_path('app/failed_migrations.json');
        file_put_contents($path, json_encode([
            'timestamp' => now()->toIso8601String(),
            'records' => $this->failedRecords
        ], JSON_PRETTY_PRINT));
        $this->info("Failed records saved to: $path");
    }

    protected function handleRecordError(string $table, int $id, Exception $e): void
    {
        $this->stats[$table]['failed']++;
        $this->failedRecords[$table][] = $id;
        Log::error("Failed to process record", [
            'table' => $table,
            'id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}