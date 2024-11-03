<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ReportsController extends Controller
{
    /**
     * Display a listing of reports
     */
    public function index()
    {
    $reports = Analysis::where('user_id', Auth::id())
        ->orderBy('created_at', 'desc')
        ->paginate(10); 

    $statistics = [
        'total_reports' => Analysis::where('user_id', Auth::id())
            ->where('report_generated', true)
            ->count(),
        'positive_cases' => Analysis::where('user_id', Auth::id())
            ->where('result', 'positive')
            ->where('report_generated', true)
            ->count(),
        'negative_cases' => Analysis::where('user_id', Auth::id())
            ->where('result', 'negative')
            ->where('report_generated', true)
            ->count(),
    ];

    return view('reports.index', compact('reports', 'statistics'));
    }

    /**
     * Show individual report
     */
    public function show(Analysis $analysis)
    {
        abort_if($analysis->user_id !== Auth::id(), 403);
        return view('reports.show', compact('analysis'));
    }

    /**
     * Generate PDF report
     */
    public function generatePDF(Analysis $analysis)
    {
    try {
        abort_if($analysis->user_id !== Auth::id(), 403);

        // Decode the result_data if it exists
        if ($analysis->result_data) {
            $analysis->result_data = json_decode($analysis->result_data, true);
        }

        $data = [
            'analysis' => $analysis,
            'user' => Auth::user(),
            'generated_at' => now()
        ];

        $pdf = PDF::loadView('reports.pdf', $data);
        
        // Generate unique filename
        $filename = 'report_' . $analysis->id . '_' . time() . '.pdf';
        $path = 'reports/' . $filename;
        
        // Store PDF
        Storage::put($path, $pdf->output());
        
        // Update analysis record
        $analysis->update([
            'report_generated' => true,
            'report_path' => $path
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'download_url' => route('reports.download', $analysis)
        ]);

    } catch (\Exception $e) {
        Log::error('Report generation failed: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Failed to generate report: ' . $e->getMessage()
        ], 500);
    }
    }

    /**
     * Download existing report
     */
    public function download(Analysis $analysis)
    {
        abort_if($analysis->user_id !== Auth::id(), 403);
        abort_if(!$analysis->report_generated || !$analysis->report_path, 404);
        
        return Storage::download($analysis->report_path, 'report_' . $analysis->id . '.pdf');
    }
}