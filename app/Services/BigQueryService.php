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
    
    /**
     * Table schemas for all supported tables
     * @var array
     */
    protected $tableSchemas = [
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

    public function __construct()
    {
        $this->bigQuery = new BigQueryClient([
            'projectId' => env('GOOGLE_CLOUD_PROJECT_ID'),
            'keyFilePath' => env('GOOGLE_APPLICATION_CREDENTIALS')
        ]);
    }

    /**
     * Get all table schemas
     *
     * @return array
     */
    public function getTableSchemas(): array
    {
        return $this->tableSchemas;
    }

    /**
     * Get schema for a specific table
     *
     * @param string $tableId
     * @return array|null
     */
    public function getTableSchema(string $tableId): ?array
    {
        return $this->tableSchemas[$tableId] ?? null;
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

        if (!$this->dataset) {
            throw new \Exception("Dataset not initialized. Call initializeDataset() first.");
        }

        try {
            $this->tables[$tableId] = $this->dataset->table($tableId);
            return $this->tables[$tableId];
        } catch (\Exception $e) {
            Log::error("Failed to get table $tableId: " . $e->getMessage());
            throw new \Exception("Failed to get table $tableId: " . $e->getMessage());
        }
    }

    /**
     * Create a table with the specified schema
     *
     * @param string $tableId
     * @param array|null $schema
     * @return Table
     */
    public function createTable(string $tableId, ?array $schema = null): Table
    {
        try {
            $table = $this->getTable($tableId);
            
            if (!$table->exists()) {
                // Use provided schema or fall back to predefined schema
                $tableSchema = $schema ?? $this->getTableSchema($tableId);
                
                if (!$tableSchema) {
                    throw new \Exception("No schema defined for table $tableId");
                }
                
                $this->dataset->createTable($tableId, [
                    'schema' => ['fields' => $tableSchema]
                ]);
                
                // Refresh the table instance after creation
                $this->tables[$tableId] = $this->dataset->table($tableId);
            }
            
            return $this->tables[$tableId];
        } catch (\Exception $e) {
            Log::error("Failed to create table $tableId: " . $e->getMessage());
            throw new \Exception("Failed to create table $tableId: " . $e->getMessage());
        }
    }

    public function createAnalysesTable()
    {
        return $this->createTable('analyses', $this->tableSchemas['analyses']);
    }

    public function createUsersTable()
    {
        return $this->createTable('users', $this->tableSchemas['users']);
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

    public function batchInsertUsers(array $userData)
    {
        $table = $this->getTable('users');

        try {
            $rows = array_map(function ($user) {
                return [
                    'data' => $user,
                    'insertId' => (string) $user['id']
                ];
            }, $userData);

            return $table->insertRows($rows, [
                'ignoreUnknownValues' => true,
                'skipInvalidRows' => false
            ]);
        } catch (\Exception $e) {
            Log::error("Error during batch insert for users: " . $e->getMessage());
            throw $e;
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
            return $this->bigQuery->runQuery($queryJobConfig);
        } catch (\Exception $e) {
            Log::error("Failed to query analyses: " . $e->getMessage());
            throw new \Exception("Failed to query analyses: " . $e->getMessage());
        }
    }
    /**
     * Get all IDs from a BigQuery table
     *
     * @param string $tableId
     * @return array
     */
    public function getTableIds(string $tableId): array
    {
        try {
            $query = "SELECT id FROM `{$this->dataset->id()}.$tableId`";
            $queryJobConfig = $this->bigQuery->query($query);
            $queryResults = $this->bigQuery->runQuery($queryJobConfig);
            
            $ids = [];
            foreach ($queryResults as $row) {
                $ids[] = (int) $row['id'];
            }
            
            return $ids;
        } catch (\Exception $e) {
            Log::error("Failed to get IDs from table $tableId: " . $e->getMessage());
            throw new \Exception("Failed to get IDs from table $tableId: " . $e->getMessage());
        }
    }

    public function getTableCount(string $tableId): int
    {
        try {
            $query = "SELECT COUNT(*) as count FROM `{$this->dataset->id()}.$tableId`";
            $queryJobConfig = $this->bigQuery->query($query);
            $queryResults = $this->bigQuery->runQuery($queryJobConfig);
            
            foreach ($queryResults as $row) {
                return (int) $row['count'];
            }
            
            return 0;
        } catch (\Exception $e) {
            Log::error("Failed to get table count for $tableId: " . $e->getMessage());
            throw new \Exception("Failed to get table count for $tableId: " . $e->getMessage());
        }
    }

    public function runJob($jobConfig)

    {

        $bigQuery = new BigQueryClient();
        $job = $bigQuery->startJob($jobConfig);

        return $job;

    }
}