<?php

namespace App\Services;

use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Dataset;
use Google\Cloud\BigQuery\Table;
use Illuminate\Support\Facades\Log;

class BigQueryService
{
    protected $bigQuery;
    protected $dataset;
    protected $tables = [];

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

    public function getTable(string $tableId): Table
    {
        if (isset($this->tables[$tableId])) {
            return $this->tables[$tableId];
        }

        try {
            $this->tables[$tableId] = $this->dataset->table($tableId);
        } catch (\Exception $e) {
            Log::error("Failed to get table $tableId: " . $e->getMessage());
            throw new \Exception("Failed to get table $tableId: " . $e->getMessage());
        }

        return $this->tables[$tableId];
    }

    public function createAnalysesTable()
    {
        return $this->createTable('analyses', [
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
        ]);
    }

    public function createUsersTable()
    {
        return $this->createTable('users', [
            ['name' => 'id', 'type' => 'INTEGER', 'mode' => 'REQUIRED'],
            ['name' => 'name', 'type' => 'STRING', 'mode' => 'REQUIRED'],
            ['name' => 'email', 'type' => 'STRING', 'mode' => 'REQUIRED'],
            ['name' => 'created_at', 'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
            ['name' => 'updated_at', 'type' => 'TIMESTAMP', 'mode' => 'REQUIRED'],
            ['name' => 'deleted_at', 'type' => 'TIMESTAMP', 'mode' => 'NULLABLE'],
        ]);
    }

    private function createTable(string $tableId, array $schema): Table
    {
        try {
            $table = $this->getTable($tableId);
            if (!$table->exists()) {
                $this->dataset->createTable($tableId, ['schema' => ['fields' => $schema]]);
            }
            return $table;
        } catch (\Exception $e) {
            Log::error("Failed to create table $tableId: " . $e->getMessage());
            throw new \Exception("Failed to create table $tableId: " . $e->getMessage());
        }
    }

    public function insertAnalysis(array $data)
    {
        try {
            $table = $this->getTable('analyses');
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
        $table = $this->getTable('analyses');
        $rows = [];
        foreach ($records as $record) {
            $rows[] = [
                'data' => $record,
                'insertId' => (string) $record['id'] // Prevent duplicate insertions
            ];
        }

        return $table->insertRows($rows, [
            'ignoreUnknownValues' => true,
            'skipInvalidRows' => false
        ]);
    }

    public function getTableCount(string $tableId): int
    {
        $table = $this->getTable($tableId);
        $query = $this->bigQuery->query("SELECT COUNT(*) as count FROM `{$this->dataset->id()}.$tableId`");
        $queryResults = $this->bigQuery->runQuery($query);
        foreach ($queryResults as $row) {
            return (int) $row['count'];
        }
        return 0;
    }
    public function batchInsertUsers(array $userData)
    {
        $tableId = 'users'; // Explicitly use 'users' table ID
        $table = $this->getTable($tableId); // Get the table object

        try {
            // Prepare the data for insertion
            $rows = array_map(function ($user) {
                return [
                    'json' => $user,
                ];
            }, $userData);

            // Insert the data into BigQuery
            $insertResponse = $table->insertRows($rows);

            // Check if insert operation was successful
            if ($insertResponse->isSuccessful()) {
                return $insertResponse;
            }

            // If there were errors, log them and throw an exception
            foreach ($insertResponse->failedRows() as $failedRow) {
                Log::error("Failed to insert user row: " . json_encode($failedRow['json']));
            }

            return $insertResponse;

        } catch (\Exception $e) {
            Log::error("Error during batch insert for users: " . $e->getMessage());
            throw $e;
        }
}

}