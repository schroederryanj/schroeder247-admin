<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeploymentController extends Controller
{
    public function deploy(Request $request)
    {
        // Verify deployment secret
        $secret = $request->input('secret') ?? $request->header('X-Deploy-Secret');
        
        if ($secret !== config('app.deploy_secret')) {
            Log::warning('Unauthorized deployment attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        Log::info('Deployment webhook triggered');
        
        try {
            // Clear and rebuild caches
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            
            // Restart queue workers
            Artisan::call('queue:restart');
            
            Log::info('Deployment tasks completed successfully');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Deployment completed',
                'tasks' => [
                    'cache_cleared' => true,
                    'cache_rebuilt' => true,
                    'migrations_run' => true,
                    'queue_restarted' => true
                ],
                'timestamp' => now()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Deployment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Deployment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}