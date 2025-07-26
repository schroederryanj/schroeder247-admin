<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SMSController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::resource('monitors', MonitorController::class);
});

require __DIR__.'/auth.php';

// Simple test route to debug routing issues
Route::get('/test-simple', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Simple route works',
        'timestamp' => now(),
        'app_url' => config('app.url')
    ]);
});

// SMS test route (simplified)
Route::get('/sms/test', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'SMS webhook is reachable',
        'timestamp' => now(),
        'twilio_configured' => config('services.twilio.sid') ? 'Yes' : 'No',
        'queue_driver' => config('queue.default')
    ]);
});

// SMS webhook endpoint
Route::post('/sms/webhook', [SMSController::class, 'handleIncomingMessage'])->name('sms.webhook');

// Deployment webhook (secured with secret)
Route::post('/deploy', [\App\Http\Controllers\DeploymentController::class, 'deploy'])->name('deploy.webhook');
