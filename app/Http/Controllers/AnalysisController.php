<?php

namespace App\Http\Controllers;

use App\Models\Analysis;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class AnalysisController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Show the analysis submission form
     */
    public function create()
    {
        return view('analysis.create');
    }

    /**
     * Process a new analysis request
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ]);

        try {
            DB::beginTransaction();

            // Store the image
            $imagePath = $request->file('image')->store('analyses');

            // Create analysis record
            $analysis = Analysis::create([
                'image_path' => $imagePath,
                'status' => 'processing',
                'user_id' => Auth::id(),
            ]);

            // Process the image with Gemini API
            $results = $this->geminiService->analyzeMalariaImage($imagePath);
            Log::info('Analysis results: ', $results);

            // Update analysis with results
            $analysis->update([
                'result_data' => json_encode($results),
                'confidence_score' => $results['confidence'],
                'result' => $results['detection'] ? 'positive' : 'negative',
                'status' => 'completed',
                'processed_at' => now(),
                'processing_time_ms' => time() - strtotime($analysis->created_at),
            ]);

            DB::commit();

            // Redirect based on confidence level
            if ($results['confidence'] < 70) {
                return redirect()->route('analyses.review', $analysis)
                    ->with('warning', 'Analysis completed but requires review due to low confidence.');
            }

            return redirect()->route('', $analysis)
                ->with('success', 'Analysis completed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Analysis failed: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Clean up uploaded file if exists
            if (isset($imagePath) && Storage::exists($imagePath)) {
                Storage::delete($imagePath);
            }

            // Update analysis status if record was created
            if (isset($analysis)) {
                $analysis->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }

            return back()->withErrors(['error' => 'Analysis failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Display analysis results
     */
    public function show(Analysis $analysis)
    {
        $resultData = is_array($analysis->result_data) ? $analysis->result_data : json_decode($analysis->result_data, true);
        return view('analysis.show', compact('analysis', 'resultData'));
    }

    /**
     * Display analysis that needs review
     */
    public function review(Analysis $analysis)
    {
        abort_if($analysis->confidence_score >= 70, 404);
        
        return view('analyses.review', compact('analysis'));
    }

    /**
     * Update analysis after review
     */
    public function updateAfterReview(Request $request, Analysis $analysis)
    {
        $request->validate([
            'result' => 'required|in:positive,negative',
            'notes' => 'nullable|string|max:1000',
        ]);

        $analysis->update([
            'result' => $request->result,
            'result_data' => array_merge($analysis->result_data, [
                'manual_review' => [
                    'reviewer_id' => Auth::id(),
                    'review_date' => now(),
                    'notes' => $request->notes,
                    'original_result' => $analysis->result,
                ]
            ]),
            'status' => 'completed'
        ]);

        return redirect()->route('analysis.show', $analysis)
            ->with('success', 'Analysis has been updated after review.');
    }

    /**
     * Generate report for an analysis
     */
    public function generateReport(Analysis $analysis)
    {
        try {
            // Generate PDF report
            $pdf = PDF::loadView('analyses.report', compact('analysis'));
            
            // Store the report
            $reportPath = 'reports/' . $analysis->id . '_' . time() . '.pdf';
            Storage::put($reportPath, $pdf->output());

            // Update analysis record
            $analysis->update([
                'report_generated' => true,
                'report_path' => $reportPath
            ]);

            return response()->download(storage_path('app/' . $reportPath));

        } catch (\Exception $e) {
            Log::error('Report generation failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to generate report']);
        }
    }

    /**
     * Download the analysis image
     */
    public function downloadImage(Analysis $analysis)
    {
        abort_if(!Storage::exists($analysis->image_path), 404);
        
        return response()->download(storage_path('app/' . $analysis->image_path));
    }

    /**
     * Delete an analysis
     */
    public function destroy(Analysis $analysis)
    {
        try {
            // Delete associated files
            if (Storage::exists($analysis->image_path)) {
                Storage::delete($analysis->image_path);
            }
            if ($analysis->report_path && Storage::exists($analysis->report_path)) {
                Storage::delete($analysis->report_path);
            }

            // Delete the record
            $analysis->delete();

            return redirect()->route('dashboard')
                ->with('success', 'Analysis deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete analysis: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete analysis']);
        }
    }
}