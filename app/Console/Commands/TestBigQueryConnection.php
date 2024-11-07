<?php

namespace App\Console\Commands;

use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Console\Command;

class TestBigQueryConnection extends Command
{
    /**
     * Summary of signature
     * @var string
     */
    protected $signature = 'bigquery:test';
    protected $description = 'Test BigQuery connection';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $bigQuery = new BigQueryClient([
                'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
                'keyFilePath' => env('GOOGLE_APPLICATION_CREDENTIALS')
            ]);

            // Try to list datasets
            $datasets = $bigQuery->datasets();
            
            $this->info('Successfully connected to BigQuery!');
            $this->info('Available datasets:');
            
            foreach ($datasets as $dataset) {
                $this->line(' - ' . $dataset->id());
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Connection failed: ' . $e->getMessage());
            return 1;
        }
    }
}