<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Chart\BarController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Public route
Route::get('/', function () {
    return view('welcome');
});

// Dashboard routes within auth middleware
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics'])->name('dashboard.statistics');
    Route::get('/dashboard/system-status', [DashboardController::class, 'getSystemStatus'])->name('dashboard.system-status');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Analysis routes
    Route::get('/analysis/index', [AnalysisController::class, 'index'])->name('analysis.index');
    Route::get('/analysis/create', [AnalysisController::class, 'create'])->name('analysis.create');
    Route::post('/analysis', [AnalysisController::class, 'store'])->name('analysis.store');
    Route::get('/analysis/{analysis}', [AnalysisController::class, 'show'])->name('analysis.show');
    Route::get('/analysis/{analysis}/review', [AnalysisController::class, 'review'])->name('analysis.review');
    Route::put('/analysis/{analysis}/review', [AnalysisController::class, 'updateAfterReview'])->name('analysis.update-review');
    Route::get('/analysis/{analysis}/report', [AnalysisController::class, 'generateReport'])->name('analysis.report');
    Route::get('/analysis/{analysis}/image', [AnalysisController::class, 'downloadImage'])->name('analysis.image');
    Route::delete('/analysis/{analysis}', [AnalysisController::class, 'destroy'])->name('analysis.destroy');
    Route::put('/analysis/{analysis}', [AnalysisController::class, 'update'])->name('analysis.update');


    // Reports route
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/{analysis}', [ReportsController::class, 'show'])->name('reports.show');
    Route::post('/reports/{analysis}/generate', [ReportsController::class, 'generatePDF'])->name('reports.generate');
    Route::get('/reports/{analysis}/download', [ReportsController::class, 'download'])->name('reports.download');
    Route::get('/analysis/download-report', [ReportsController::class, 'downloadReport'])->name('analysis.download-report');
    Route::get('/analysis/download-report', [AnalysisController::class, 'downloadReport'])->name('analysis.download-report');

    // Chart routes
    Route::get('/chart/bar', [BarController::class, 'getAnalysisData'])->name('chart.bar');
    Route::get('/chart/analysis-data', [BarController::class, 'getAnalysisData']);


});

require __DIR__.'/auth.php';
