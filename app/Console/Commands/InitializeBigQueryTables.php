<?php

namespace App\Console\Commands;

use App\Services\BigQueryService;
use Illuminate\Console\Command;

class InitializeBigQueryTables extends Command
{
    /**
     * Summary of signature
     * @var string
     */
    protected $signature = 'bigquery:init';
    protected $description = 'Initialize BigQuery tables';

    protected $bigQuery;

    /**
     * Execute the console command.
     *
     * @return int
     */

    
    public function __construct(BigQueryService $bigQuery)
    {
        parent::__construct();
        $this->bigQuery = $bigQuery;
    }

    public function handle()
    {
        try {
            $this->info('Creating dataset...');
            $dataset = $this->bigQuery->initializeDataset();
            
            $this->info('Creating analyses table...');
            $this->bigQuery->createAnalysesTable();
            
            $this->info('Creating users table...');
            $this->bigQuery->createUsersTable();
            
            $this->info('BigQuery tables initialized successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to initialize BigQuery tables: ' . $e->getMessage());
            return 1;
        }
    }
}