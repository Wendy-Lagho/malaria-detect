<?php

namespace App\Console\Commands;

use App\Services\BigQueryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Core\ExponentialBackoff;
use Exception;
use Throwable;

class MigrateMySQLToBigQuery extends Command
{
    protected $signature = 'migrate:mysql-to-bigquery 
                          {--chunk=1000 : The number of records to process at a time}
                          {--table= : Specific table to migrate (optional)}
                          {--retry=3 : Number of retry attempts for failed operations}
                          {--parallel=1 : Number of parallel processes}
                          {--verify : Verify data after migration}';

    protected $description = 'Migrate data from MySQL to BigQuery with verification';

    protected BigQueryService $bigQueryService;
    protected array $stats = [];
    protected array $failedRecords = [];

    // Define table schemas
    protected array $tableSchemas = [
        'analyses' => [
            ['name' => 'id', 'type' => 'INTEGER', 'mode' => 'REQUIRED'],
            ['name' => 'image_path', 'type' => 'STRING', 'mode' => 'REQUIRED'],
            ['name' => 'result_data', 'type' => 'JSON', 'mode' => 'NULLABLE'],
            ['name' => 'confidence_score', 'type' => 'FLOAT', 'mode' => 'NULLABLE'],
            ['name' => 'result', 'type' => 'STRING', 'mode' => 'NULLABLE'],
            ['name' => 'status', 'type' => 'STRING', 'mode' => 'REQUIRED'],
            ['name' => 'user_id', 'type' => 'INTEGER', 'mode' => 'NULLABLE'],
            ['name' => 'processed_at', 'type' => 'TIMESTAMP', 'mode' => 'NULLABLE'],
            ['name' => 'error_message', 'type' => 'STRING', 'mode' => 'NULLABLE'],
            ['name' => 'processing_time_ms', 'type' => 'INTEGER', 'mode' => 'NULLABLE'],
            ['name' => 'report_generated', 'type' => 'BOOLEAN', 'mode' => 'REQUIRED'],
            ['name' => 'report_path', 'type' => 'STRING', 'mode' => 'NULLABLE'],
            ['name' => 'created_at', 'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
            ['name' => 'updated_at', 'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
            ['name' => 'deleted_at', 'type' => 'TIMESTAMP', 'mode' => 'NULLABLE'],
        ],
        'users' => [
            ['name' => 'id', 'type' => 'INTEGER', 'mode' => 'REQUIRED'],
            ['name' => 'name', 'type' => 'STRING', 'mode' => 'REQUIRED'],
            ['name' => 'email', 'type' => 'STRING', 'mode' => 'REQUIRED'],
            ['name' => 'created_at', 'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
            ['name' => 'updated_at', 'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
            ['name' => 'deleted_at', 'type' => 'TIMESTAMP', 'mode' => 'NULLABLE'],
        ],
    ];

    public function __construct(BigQueryService $bigQueryService)
    {
        parent::__construct();
        $this->bigQueryService = $bigQueryService;
    }

    public function handle(): int
    {
        $this->info('Starting MySQL to BigQuery migration...');
        
        try {
            // Initialize BigQuery dataset
            $dataset = $this->bigQueryService->initializeDataset();
            $this->info('Dataset initialized successfully.');

            // Create tables if they don't exist
            $this->createTables();
            
            // Get tables to migrate
            $tablesToMigrate = $this->option('table') 
                ? [$this->option('table')] 
                : array_keys($this->tableSchemas);

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
            Log::error("Migration failed: ", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return 1;
        }
    }

    protected function createTables(): void
    {
        $this->info('Creating tables if they don\'t exist...');
        
        foreach ($this->tableSchemas as $tableId => $schema) {
            try {
                $table = $this->bigQueryService->getTable($tableId);
                
                if (!$table->exists()) {
                    $this->info("Creating table: $tableId");
                    $table = $this->bigQueryService->createTable($tableId, $schema);
                    $this->info("Table $tableId created successfully.");
                } else {
                    $this->info("Table $tableId already exists.");
                }
            } catch (Exception $e) {
                $this->error("Failed to create/verify table $tableId: " . $e->getMessage());
                throw $e;
            }
        }
    }

    protected function migrateTable(string $table): void
    {
        $this->info("Starting migration for table: $table");
        
        // Initialize stats for this table
        $this->stats[$table] = ['total' => 0, 'migrated' => 0, 'failed' => 0];
        
        // Get total count from MySQL
        $totalRecords = DB::table($table)->count();
        $this->stats[$table]['total'] = $totalRecords;
        
        $progressBar = $this->output->createProgressBar($totalRecords);
        $chunkSize = (int) $this->option('chunk');
        
        try {
            DB::table($table)
                ->orderBy('id')
                ->chunk($chunkSize, function ($records) use ($table, $progressBar) {
                    $batchData = [];
                    
                    foreach ($records as $record) {
                        try {
                            $formattedData = $this->formatRecordForBigQuery($table, $record);
                            $batchData[] = $formattedData;
                        } catch (Exception $e) {
                            $this->handleRecordError($table, $record->id, $e);
                            continue;
                        }
                        $progressBar->advance();
                    }
                    
                    if (!empty($batchData)) {
                        $this->insertBatchWithRetry($table, $batchData);
                    }
                });
                
            $progressBar->finish();
            $this->newLine(2);
            
        } catch (Exception $e) {
            $this->error("Failed to migrate table $table: " . $e->getMessage());
            throw $e;
        }
    }

    protected function insertBatchWithRetry(string $table, array $batchData): void
    {
        $backoff = new ExponentialBackoff($this->option('retry'));
        
        try {
            $backoff->execute(function () use ($table, $batchData) {
                $insertMethod = "batchInsert" . ucfirst($table);
                $result = $this->bigQueryService->$insertMethod($batchData);
                
                if (!$result->isSuccessful()) {
                    throw new Exception("Batch insert failed: " . json_encode($result->failedRows()));
                }
                
                $this->stats[$table]['migrated'] += count($batchData);
            });
        } catch (Exception $e) {
            $this->stats[$table]['failed'] += count($batchData);
            foreach ($batchData as $record) {
                $this->failedRecords[$table][] = $record['id'];
            }
            throw $e;
        }
    }

    protected function formatRecordForBigQuery(string $table, $record): array
    {
        $formatMethod = "format" . ucfirst(rtrim($table, 's')) . "ForBigQuery";
        return $this->$formatMethod($record);
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
            } else {
                $this->info("  ✅ Counts match!");
            }
        }
    }

    protected function displaySummary(): void
    {
        $this->info('Migration Summary:');
        
        foreach ($this->stats as $table => $stat) {
            $this->info("Table: $table");
            $this->info("  Total records: {$stat['total']}");
            $this->info("  Successfully migrated: {$stat['migrated']}");
            $this->info("  Failed: {$stat['failed']}");
        }
        
        if (!empty($this->failedRecords)) {
            $this->warn('Failed records have been logged for retry.');
            $this->saveFailedRecords();
        }
    }

    protected function saveFailedRecords(): void
    {
        $path = storage_path('app/failed_migrations.json');
        file_put_contents($path, json_encode($this->failedRecords, JSON_PRETTY_PRINT));
        $this->info("Failed records saved to: $path");
    }

    protected function handleRecordError(string $table, int $id, Exception $e): void
    {
        $this->stats[$table]['failed']++;
        $this->failedRecords[$table][] = $id;
        Log::error("Failed to format record", [
            'table' => $table,
            'id' => $id,
            'error' => $e->getMessage()
        ]);
    }
}