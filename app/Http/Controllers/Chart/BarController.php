<?php

namespace App\Http\Controllers\Chart;

use App\Models\Analysis;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BarController extends Controller
{
    protected $data;

    public function __construct()
    {
        $this->data = $this->getAnalysisData()->getData(true);
    }
    /**
     * Get data for the analysis status chart
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAnalysisData()
    {
        // Query the database for analysis counts by result
        $positiveCount = Analysis::where('result', 'positive')->count();
        $negativeCount = Analysis::where('result', 'negative')->count();
        $pendingCount = Analysis::where('result', 'pending')->count();

        // Prepare the data for the chart
        $data = [
            'labels' => ['Positive', 'Negative', 'Pending'],
            'data' => [$positiveCount, $negativeCount, $pendingCount],
            'colors' => [
                'rgba(255, 99, 132, 0.5)',  // Light red for positive
                'rgba(75, 192, 192, 0.5)',  // Light teal for negative
                'rgba(255, 206, 86, 0.5)'   // Light yellow for pending
            ],
            'borderColors' => [
                'rgba(255, 99, 132, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(255, 206, 86, 1)'
            ]
        ];
        return response()->json($data);
    }

    /**
     * Display the analysis dashboard with chart
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $totalAnalyses = Analysis::count();
        $positiveCases = Analysis::where('result', 'positive')->count();
        $pendingAnalyses = Analysis::where('result', 'pending')->count();
        $successRate = $totalAnalyses > 0 
            ? round((Analysis::where('result', '!=', 'pending')->count() / $totalAnalyses) * 100, 1) . '%'
            : '0%';
        
        $recentAnalyses = Analysis::latest()
            ->take(10)
            ->get();

        return view('dashboard', compact(
            'totalAnalyses',
            'positiveCases',
            'pendingAnalyses',
            'successRate',
            'recentAnalyses'
        ));
    }
    public function render()
    {
        return view();
    }
}