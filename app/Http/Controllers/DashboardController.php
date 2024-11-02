<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        //Get total number of analysis
        $totalAnalyses = Analysis::count();

        // Get positive cases (completed analyses with high confidence positive results)
        $positiveCases = Analysis::where('status', 'completed')
            ->where('result', 'positive')
            ->where('confidence_score','>=', 70)
            ->count();

        // Get pending analyses (including those marked as inconclusive due to low confidence)
        $pendingAnalyses = Analysis::where(function($query) {
            $query->where('status', 'pending')
                  ->orWhere('status', 'processing')
                  ->orWhere(function($q) {
                      $q->where('status', 'completed')
                        ->where('confidence_score', '<', 70);
                  });
        })->count();

        // Calculate success rate (completed analyses with high confidence)
        $completedAnalyses = Analysis::where('status', 'completed')
            ->where('confidence_score', '>=', 70)
            ->count();
        
        $successRate = $totalAnalyses > 0 
            ? round(($completedAnalyses / $totalAnalyses) * 100, 1)
            : 0;

        // Get recent analyses
        $recentAnalyses = Analysis::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($analysis) {
                // Modify the analysis object
                $analysis->confidence = round($analysis->confidence_score, 1);
                $analysis->result = $this->determineResult($analysis);
                return $analysis; // Return the modified analysis object
        });
        
        // Get analysis trends (last 7 days)
        $trends = Analysis::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN result = "positive" AND confidence_score >= 70 THEN 1 ELSE 0 END) as positive_cases')
        )
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('dashboard', compact(
            'totalAnalyses',
            'positiveCases',
            'pendingAnalyses',
            'successRate',
            'recentAnalyses',
            'trends'
        ));
    }
    private function determineResult(Analysis $analysis)
    {
        if ($analysis->status !== 'completed') {
            return 'pending';
        }

        if ($analysis->confidence_score < 70) {
            return 'inconclusive';
        }

        return $analysis->result;
    }

    public function getStatistics()
    {
        // API endpoint for updating dashboard statistics via AJAX if needed
        $statistics = [
            'total' => Analysis::count(),
            'positive' => Analysis::where('status', 'completed')
                ->where('result', 'positive')
                ->where('confidence_score', '>=', 70)
                ->count(),
            'pending' => Analysis::where('status', 'pending')->count(),
            'processing' => Analysis::where('status', 'processing')->count(),
            'failed' => Analysis::where('status', 'failed')->count(),
        ];

        return response()->json($statistics);
    }

    public function getSystemStatus()
    {
        // Check system health
        $status = [
            'api_status' => $this->checkApiStatus(),
            'last_successful_analysis' => Analysis::where('status', 'completed')
                ->latest()
                ->first()?->created_at,
            'processing_queue' => Analysis::where('status', 'processing')->count(),
            'storage_usage' => $this->getStorageUsage(),
        ];

        return response()->json($status);
    }

    private function checkApiStatus()
    {
        // Check if the Gemini API is responding
        try {
            // Implement your API health check logic here
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getStorageUsage()
    {
        // Calculate storage usage for analysis images
        try {
            $totalSpace = disk_total_space(storage_path('app/public/analyses'));
            $freeSpace = disk_free_space(storage_path('app/public/analyses'));
            $usedSpace = $totalSpace - $freeSpace;
            
            return [
                'used' => round($usedSpace / 1024 / 1024 / 1024, 2), // GB
                'total' => round($totalSpace / 1024 / 1024 / 1024, 2), // GB
                'percentage' => round(($usedSpace / $totalSpace) * 100, 1)
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}