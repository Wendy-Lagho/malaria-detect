<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use App\Services\BigQueryService;
use Illuminate\Support\Facades\Log;


class Analysis extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'image_path',
        'result_data',
        'confidence_score',
        'status',
        'result',
        'user_id',
        'processed_at',
        'error_message',
        'processing_time_ms',
        'report_generated',
        'report_path'
    ];

    protected $casts = [
        'result_data' => 'array',
        'confidence_score' => 'decimal:2',
        'processed_at' => 'datetime',
        'report_generated' => 'boolean',
        'processing_time_ms' => 'integer',
    ];
    protected static $bigQuery;
    protected static $useBigQuery;

    public static function boot()
    {
        parent::boot();
        static::$useBigQuery = Config::get('database.use_bigquery', false);
    }

    // Initialize BigQuery service when needed
    protected static function getBigQuery()
    {
        if (!static::$bigQuery) {
            static::$bigQuery = new BigQueryService();
        }
        return static::$bigQuery;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
            
            return $queryResults;
        } catch (\Exception $e) {
            Log::error("Failed to query analyses: " . $e->getMessage());
            throw new \Exception("Failed to query analyses: " . $e->getMessage());
        }
    }

    public function scopePositive($query)
    {
        return $query->where('result', 'positive')
                    ->where('confidence_score', '>=', 70);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    public function scopeInconclusive($query)
    {
        return $query->where('status', 'completed')
                    ->where('confidence_score', '<', 70);
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Awaiting Analysis',
            'processing' => 'Processing',
            'completed' => $this->confidence_score >= 70 ? 'Completed' : 'Needs Review',
            'failed' => 'Failed',
            default => 'Unknown'
        };
    }

    public function getResultLabelAttribute()
    {
        if ($this->status !== 'completed') {
            return 'Pending';
        }

        if ($this->confidence_score < 70) {
            return 'Inconclusive';
        }

        return ucfirst($this->result);
    }
    public function canGenerateReport()
    {
    return !$this->report_generated;
    }

    public function saveToBigQuery()
    {
        try {
            $data = $this->formatForBigQuery();
            return static::getBigQuery()->insertAnalysis($data);
        } catch (\Exception $e) {
            Log::error('BigQuery Insert Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function formatForBigQuery()
    {
        return [
            'id' => $this->id,
            'image_path' => $this->image_path,
            'result_data' => json_encode($this->result_data),
            'confidence_score' => (float) $this->confidence_score,
            'result' => $this->result,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'processed_at' => $this->processed_at ? $this->processed_at->format('Y-m-d H:i:s') : null,
            'error_message' => $this->error_message,
            'processing_time_ms' => $this->processing_time_ms,
            'report_generated' => (bool) $this->report_generated,
            'report_path' => $this->report_path,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }

    // Static BigQuery query methods that mirror the scopes
    public static function findInBigQuery($id)
    {
        return static::getBigQuery()->queryAnalyses(["id = {$id}"]);
    }

    public static function getPositiveFromBigQuery()
    {
        return static::getBigQuery()->queryAnalyses([
            "result = 'positive'",
            "confidence_score >= 70"
        ]);
    }

    public static function getPendingFromBigQuery()
    {
        return static::getBigQuery()->queryAnalyses([
            "status IN ('pending', 'processing')"
        ]);
    }

    public static function getInconclusiveFromBigQuery()
    {
        return static::getBigQuery()->queryAnalyses([
            "status = 'completed'",
            "confidence_score < 70"
        ]);
    }

    // Override save to optionally save to BigQuery as well
    public function save(array $options = [])
    {
        $saved = parent::save($options);
        
        if ($saved && Config::get('database.use_bigquery', false)) {
            try {
                $this->saveToBigQuery();
            } catch (\Exception $e) {
                Log::error('BigQuery Save Failed: ' . $e->getMessage());
                //implement a job queue for retrying failed saves
                // dispatch(new RetryBigQuerySave($this));
            }
        }

        return $saved;
    }

    // Helper method to determine storage type
    public static function isUsingBigQuery()
    {
        return static::$useBigQuery;
    }

    // Add this method for bulk operations
    public static function batchSaveToBigQuery(array $records)
    {
        if (!static::$useBigQuery) {
            return false;
        }

        try {
            return static::getBigQuery()->batchInsertAnalyses($records);
        } catch (\Exception $e) {
            Log::error('Batch BigQuery Insert Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}