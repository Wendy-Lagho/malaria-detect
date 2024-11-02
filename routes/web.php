<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DashboardController;
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

    // Reports route
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
});

// Logout route
Route::get('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('logout');

require __DIR__.'/auth.php';
