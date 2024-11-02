<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function user()
    {
        return $this->belongsTo(User::class);
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
}