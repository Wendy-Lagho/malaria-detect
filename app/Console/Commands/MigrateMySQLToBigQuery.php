<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Analysis;
use App\Services\BigQueryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateMySQLToBigQuery extends Command
{
    protected $signature = 'migrate:to-bigquery {--chunk=100} {--start-id=} {--end-id=}';
    protected $description = 'Migrate existing MySQL Analysis data to BigQuery';

    private $bigQuery;
    private $totalRecords = 0;
    private $migratedRecords = 0;
    private $failedRecords = 0;

    public function __construct(BigQueryService $bigQuery)
    {
        parent::__construct();
        $this->bigQuery = $bigQuery;
    }

    public function handle()
    {
        $this->info('Starting MySQL to BigQuery migration...');
        
        $query = Analysis::query();
        
        if ($startId = $this->option('start-id')) {
            $query->where('id', '>=', $startId);
        }
        
        if ($endId = $this->option('end-id')) {
            $query->where('id', '<=', $endId);
        }

        $this->totalRecords = $query->count();
        $this->info("Total records to migrate: {$this->totalRecords}");

        $progressBar = $this->output->createProgressBar($this->totalRecords);
        
        $query->orderBy('id')->chunk($this->option('chunk'), function ($analyses) use ($progressBar) {
            $batchData = [];
            
            foreach ($analyses as $analysis) {
                try {
                    $batchData[] = $analysis->formatForBigQuery();
                    $this->migratedRecords++;
                } catch (\Exception $e) {
                    $this->failedRecords++;
                    Log::error("Failed to format analysis ID {$analysis->id}: " . $e->getMessage());
                    $this->warn("Failed to format analysis ID {$analysis->id}");
                }
                $progressBar->advance();
            }
            
            if (!empty($batchData)) {
                try {
                    $this->bigQuery->batchInsertAnalyses($batchData);
                } catch (\Exception $e) {
                    $this->failedRecords += count($batchData);
                    $this->migratedRecords -= count($batchData);
                    Log::error("Batch insert failed: " . $e->getMessage());
                    $this->warn("Failed to insert batch");
                }
            }
        });

        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("Migration completed:");
        $this->info("Total records processed: {$this->totalRecords}");
        $this->info("Successfully migrated: {$this->migratedRecords}");
        $this->info("Failed records: {$this->failedRecords}");
    }
}