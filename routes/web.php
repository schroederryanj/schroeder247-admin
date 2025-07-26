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

// Cron job endpoint for monitor checks (secured with secret)
Route::get('/cron/check-monitors', function() {
    $secret = request()->query('secret');
    if ($secret !== config('app.deploy_secret')) {
        abort(403);
    }
    
    \Illuminate\Support\Facades\Artisan::call('monitors:check-all');
    return response()->json(['status' => 'success', 'message' => 'Monitor checks dispatched']);
})->name('cron.monitors');

// Test endpoint to debug specific URL (secured with secret)
Route::get('/test-url', function() {
    $secret = request()->query('secret');
    if ($secret !== config('app.deploy_secret')) {
        abort(403);
    }
    
    $url = request()->query('url', 'https://mail.slentertainment.com/owa');
    
    try {
        $startTime = microtime(true);
        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->withOptions([
                'verify' => false,
                'allow_redirects' => true,
                'http_errors' => false,
            ])
            ->withHeaders([
                'User-Agent' => 'SchroederMonitor/1.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);
            
        $endTime = microtime(true);
        $responseTime = round(($endTime - $startTime) * 1000);
        
        return response()->json([
            'url' => $url,
            'status_code' => $response->status(),
            'response_time' => $responseTime . 'ms',
            'headers' => $response->headers(),
            'body_length' => strlen($response->body()),
            'body_preview' => substr($response->body(), 0, 500),
            'successful' => $response->successful(),
            'redirect_count' => count($response->redirectHistory ?? [])
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'url' => $url,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('test.url');

// Manually check a specific monitor (secured with secret)
Route::get('/check-monitor/{id}', function($id) {
    $secret = request()->query('secret');
    if ($secret !== config('app.deploy_secret')) {
        abort(403);
    }
    
    $monitor = \App\Models\Monitor::find($id);
    if (!$monitor) {
        return response()->json(['error' => 'Monitor not found'], 404);
    }
    
    try {
        // Run the check synchronously
        $job = new \App\Jobs\CheckMonitor($monitor);
        $job->handle();
        
        // Get the latest result
        $latestResult = $monitor->results()->latest('checked_at')->first();
        
        return response()->json([
            'monitor' => [
                'id' => $monitor->id,
                'name' => $monitor->name,
                'url' => $monitor->url,
                'type' => $monitor->type,
                'current_status' => $monitor->fresh()->current_status,
                'last_checked_at' => $monitor->fresh()->last_checked_at,
            ],
            'latest_result' => $latestResult ? [
                'status' => $latestResult->status,
                'status_code' => $latestResult->status_code,
                'response_time' => $latestResult->response_time,
                'error_message' => $latestResult->error_message,
                'checked_at' => $latestResult->checked_at,
            ] : null
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Monitor check failed: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('check.monitor');
