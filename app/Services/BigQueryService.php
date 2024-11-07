<?php

namespace App\Services;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Dataset;
use Illuminate\Support\Facades\Log;

class BigQueryService
{
    protected $bigQuery;
    protected $dataset;
    
    public function __construct()
    {
        $this->bigQuery = new BigQueryClient([
            'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
            'keyFilePath' => env('GOOGLE_APPLICATION_CREDENTIALS')
        ]);
    }

    public function initializeDataset($datasetId = 'malaria_detection')
    {
        try {
            if (!$this->bigQuery->dataset($datasetId)->exists()) {
                $this->dataset = $this->bigQuery->createDataset($datasetId);
            } else {
                $this->dataset = $this->bigQuery->dataset($datasetId);
            }
            return $this->dataset;
        } catch (\Exception $e) {
            Log::error("Failed to initialize dataset: " . $e->getMessage());
            throw new \Exception("Failed to initialize dataset: " . $e->getMessage());
        }
    }

    public function createAnalysesTable()
    {
        $tableId = 'analyses';
        $schema = [
            'fields' => [
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
            ]
        ];

        try {
            $tableExists = $this->dataset->table($tableId)->exists();
            if (!$tableExists) {
                $this->dataset->createTable($tableId, ['schema' => $schema]);
            }
            return $this->dataset->table($tableId);
        } catch (\Exception $e) {
            Log::error("Failed to create analyses table: " . $e->getMessage());
            throw new \Exception("Failed to create analyses table: " . $e->getMessage());
        }
    }

    public function createUsersTable()
    {
        $tableId = 'users';
        $schema = [
            'fields' => [
                ['name' => 'id', 'type' => 'INTEGER', 'mode' => 'REQUIRED'],
                ['name' => 'name', 'type' => 'STRING', 'mode' => 'REQUIRED'],
                ['name' => 'email', 'type' => 'STRING', 'mode' => 'REQUIRED'],
                ['name' => 'created_at', 'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
                ['name' => 'updated_at', 'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
                ['name' => 'deleted_at', 'type' => 'TIMESTAMP', 'mode' => 'NULLABLE'],
            ]
        ];

        try {
            $tableExists = $this->dataset->table($tableId)->exists();
            if (!$tableExists) {
                $this->dataset->createTable($tableId, ['schema' => $schema]);
            }
            return $this->dataset->table($tableId);
        } catch (\Exception $e) {
            Log::error("Failed to create users table: " . $e->getMessage());
            throw new \Exception("Failed to create users table: " . $e->getMessage());
        }
    }

    public function insertAnalysis(array $data)
    {
        try {
            $table = $this->dataset->table('analyses');
            return $table->insertRows([
                ['data' => $data]
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to insert analysis: " . $e->getMessage());
            throw new \Exception("Failed to insert analysis: " . $e->getMessage());
        }
    }

    public function queryAnalyses($conditions = [])
    {
        try {
            $query = "SELECT * FROM `{$this->dataset->id()}.analyses`";
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }
            $query .= " ORDER BY created_at DESC LIMIT 1000";
            
            $queryJobConfig = $this->bigQuery->query($query);
            $queryResults = $this->bigQuery->runQuery($queryJobConfig);
        } catch (\Exception $e) {
            Log::error("Failed to query analyses: " . $e->getMessage());
            throw new \Exception("Failed to query analyses: " . $e->getMessage());
        }
    }

    public function batchInsertAnalyses(array $records)
    {
    $rows = [];
    foreach ($records as $record) {
        $rows[] = [
            'data' => $record,
            'insertId' => (string) $record['id'] // Prevent duplicate insertions
        ];
    }

    return $this->dataset->table('analyses')->insertRows($rows, [
        'ignoreUnknownValues' => true,
        'skipInvalidRows' => false
    ]);
    }
}