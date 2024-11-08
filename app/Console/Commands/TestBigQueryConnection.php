<?php

namespace App\Console\Commands;

use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestBigQueryConnection extends Command
{
    protected $signature = 'bigquery:test';
    protected $description = 'Test BigQuery connection and verify malaria detection data';

    public function handle()
    {
        try {
            // 1. First verify environment variables
            $this->info('Checking configuration...');
            $this->info('Project ID: ' . env('GOOGLE_CLOUD_PROJECT_ID'));
            $this->info('Credentials Path: ' . env('GOOGLE_APPLICATION_CREDENTIALS'));

            // 2. Initialize BigQuery client
            $bigQuery = new BigQueryClient([
                'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
                'keyFilePath' => env('GOOGLE_APPLICATION_CREDENTIALS')
            ]);

            // 3. Check connection and datasets
            $this->info("\n📡 Testing BigQuery Connection...");
            $datasets = $bigQuery->datasets();
            $this->info('✅ Successfully connected to BigQuery!');
            
            // 4. Display available datasets
            $this->info("\n📚 Available datasets:");
            foreach ($datasets as $dataset) {
                $this->line(' - ' . $dataset->id());
            }

            // 5. Verify malaria detection specific tables
            $datasetId = env('GOOGLE_CLOUD_DATASET_ID', 'malaria_detection'); // adjust as needed
            $dataset = $bigQuery->dataset($datasetId);
            
            $this->info("\n🔍 Verifying Malaria Detection Tables...");
            
            // List of expected tables - adjust these names to match your actual tables
            $expectedTables = [
                'analyses' => 'Analyses',
                'results' => 'Results',
                // add other tables as needed
            ];

            foreach ($expectedTables as $tableId => $tableName) {
                try {
                    $table = $dataset->table($tableId);
                    $tableInfo = $table->info();
                    
                    // Get row count from BigQuery
                    $query = $bigQuery->query("SELECT COUNT(*) as count FROM `$datasetId.$tableId`");
                    $queryResults = $bigQuery->runQuery($query);
                    $bqCount = 0;
                    foreach ($queryResults as $row) {
                        $bqCount = $row['count'];
                    }

                    // Get row count from MySQL for comparison
                    $mysqlCount = DB::table($tableId)->count();

                    $this->info("\n📊 Table: $tableName");
                    $this->info("├── Status: ✅ Exists");
                    $this->info("├── BigQuery Records: $bqCount");
                    $this->info("├── MySQL Records: $mysqlCount");
                    
                    if ($bqCount === $mysqlCount) {
                        $this->info("└── Match Status: ✅ Counts match");
                    } else {
                        $this->warn("└── Match Status: ⚠️ Count mismatch!");
                    }

                    // Sample data verification (optional)
                    $sampleQuery = $bigQuery->query("SELECT * FROM `$datasetId.$tableId` LIMIT 1");
                    $sampleResults = $bigQuery->runQuery($sampleQuery);
                    
                    foreach ($sampleResults as $row) {
                        $this->info("\n📝 Sample Record Structure:");
                        foreach ($row as $field => $value) {
                            $this->line("   ├── $field: " . (is_array($value) ? json_encode($value) : $value));
                        }
                    }

                } catch (\Exception $e) {
                    $this->error("❌ Error checking table $tableId: " . $e->getMessage());
                }
            }

            // 6. Verify specific malaria detection queries
            $this->info("\n🔬 Verifying Malaria Detection Analysis...");
            try {
                // Add queries specific to your malaria detection logic
                $analysisQuery = $bigQuery->query("
                    SELECT 
                        COUNT(*) as total_analyses,
                        COUNT(DISTINCT patient_id) as unique_patients
                    FROM `$datasetId.analyses`
                ");
                
                $analysisResults = $bigQuery->runQuery($analysisQuery);
                
                foreach ($analysisResults as $row) {
                    $this->info("Total Analyses: {$row['total_analyses']}");
                    $this->info("Unique Patients: {$row['unique_patients']}");
                }

            } catch (\Exception $e) {
                $this->error("❌ Error running analysis verification: " . $e->getMessage());
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Connection failed: ' . $e->getMessage());
            return 1;
        }
    }
}