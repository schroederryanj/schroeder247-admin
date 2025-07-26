# Automated Deployment Setup with Cloudways API

This guide explains how to set up automated deployment with cache clearing using the Cloudways API and GitHub Actions.

## Overview

The automated deployment system:
1. Triggers on pushes to the main branch
2. Pulls latest code via Cloudways API
3. Calls a secure webhook to clear Laravel caches
4. Ensures route caching issues don't break deployments

## Prerequisites

- Cloudways hosting account
- GitHub repository
- Laravel application
- Cloudways API credentials

## Step 1: Get Cloudways API Credentials

1. Log into your Cloudways account
2. Go to **Settings** → **API**
3. Note your:
   - Email address
   - API Key
   - Server ID (from server list)
   - App ID (from application list)

## Step 2: Create Deployment Controller

Create `app/Http/Controllers/DeploymentController.php`:

```php
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
        
        try {
            Log::info('Starting deployment cache clearing');
            
            // Clear all caches
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            
            // Rebuild caches for production
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            
            Log::info('Deployment cache clearing completed successfully');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Deployment completed successfully',
                'timestamp' => now()->toISOString(),
                'actions' => [
                    'config:clear' => 'completed',
                    'route:clear' => 'completed', 
                    'view:clear' => 'completed',
                    'cache:clear' => 'completed',
                    'config:cache' => 'completed',
                    'route:cache' => 'completed',
                    'view:cache' => 'completed'
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Deployment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Deployment failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

## Step 3: Add Deployment Route

Add to `routes/web.php`:

```php
// Deployment webhook (secured with secret)
Route::post('/deploy', [\App\Http\Controllers\DeploymentController::class, 'deploy'])->name('deploy.webhook');
```

## Step 4: Update App Configuration

Add to `config/app.php`:

```php
/*
|--------------------------------------------------------------------------
| Deployment Secret
|--------------------------------------------------------------------------
|
| This secret is used to secure the deployment webhook endpoint.
| Make sure to set this in your production environment.
|
*/

'deploy_secret' => env('DEPLOY_SECRET', 'change-this-secret-in-production'),
```

## Step 5: Set Environment Variables

Add to your `.env` file:

```env
DEPLOY_SECRET=your-secure-random-string-here
```

Generate a secure random string for production use.

## Step 6: Create GitHub Actions Workflow

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Cloudways

on:
  push:
    branches: [ main ]
  workflow_dispatch:

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - name: Deploy via Cloudways API
      run: |
        echo "Starting deployment to Cloudways..."
        
        # Get access token
        echo "Authenticating with Cloudways..."
        AUTH_RESPONSE=$(curl -s -X POST https://api.cloudways.com/api/v1/oauth/access_token \
          -d "email=${{ secrets.CLOUDWAYS_EMAIL }}" \
          -d "api_key=${{ secrets.CLOUDWAYS_API_KEY }}")
        
        ACCESS_TOKEN=$(echo $AUTH_RESPONSE | jq -r '.access_token')
        
        if [ "$ACCESS_TOKEN" = "null" ]; then
          echo "Authentication failed: $AUTH_RESPONSE"
          exit 1
        fi
        
        echo "Authentication successful"
        
        # Trigger git pull
        echo "Triggering git pull..."
        DEPLOY_RESPONSE=$(curl -s -X POST https://api.cloudways.com/api/v1/git-pull \
          -H "Authorization: Bearer $ACCESS_TOKEN" \
          -H "Content-Type: application/json" \
          -d '{
            "server_id": "${{ secrets.CLOUDWAYS_SERVER_ID }}",
            "app_id": "${{ secrets.CLOUDWAYS_APP_ID }}",
            "deploy_path": null,
            "branch_name": "main"
          }')
        
        echo "Git pull response: $DEPLOY_RESPONSE"
        
        # Wait a moment for deployment to complete
        echo "Waiting for deployment to complete..."
        sleep 10
        
        # Clear Laravel caches via webhook
        echo "Clearing Laravel caches..."
        DEPLOY_RESPONSE=$(curl -s -X POST https://${{ secrets.APP_DOMAIN }}/deploy \
          -H "Content-Type: application/json" \
          -d '{
            "secret": "'"${DEPLOY_SECRET}"'"
          }')
        
        echo "Cache clearing response: $DEPLOY_RESPONSE"
        echo "Deployment completed successfully!"
      
      env:
        DEPLOY_SECRET: ${{ secrets.DEPLOY_SECRET }}
```

## Step 7: Configure GitHub Secrets

In your GitHub repository, go to **Settings** → **Secrets and variables** → **Actions** and add:

- `CLOUDWAYS_EMAIL`: Your Cloudways email
- `CLOUDWAYS_API_KEY`: Your Cloudways API key  
- `CLOUDWAYS_SERVER_ID`: Your server ID
- `CLOUDWAYS_APP_ID`: Your application ID
- `DEPLOY_SECRET`: Your deployment secret (same as in .env)
- `APP_DOMAIN`: Your application domain (e.g., admin.yoursite.com)

## Step 8: Test the Setup

1. Make a change to your code
2. Push to the main branch
3. Check GitHub Actions tab for workflow execution
4. Verify deployment completed successfully

## Troubleshooting

### Common Issues

**404 errors on webhook endpoint:**
- Ensure routes are registered in `bootstrap/app.php`
- Clear route cache: `php artisan route:clear`
- Check that API routes are working

**Authentication failures:**
- Verify Cloudways API credentials
- Check that email and API key are correct
- Ensure server/app IDs match your Cloudways setup

**Cache clearing failures:**
- Ensure `DEPLOY_SECRET` matches between .env and GitHub secrets
- Check Laravel logs for specific errors
- Verify webhook endpoint is accessible

### Manual Cache Clearing

If automated clearing fails, run manually:

```bash
php artisan config:clear
php artisan route:clear  
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Deployment Path Issues

If you get deployment path errors:
- Set `deploy_path` to `null` in the API call
- Cloudways will use the default path for your application
- Check your Cloudways panel for the correct application path

## Security Notes

- Keep your `DEPLOY_SECRET` secure and random
- Use HTTPS for all webhook calls
- Monitor deployment logs for unauthorized attempts
- Regularly rotate API keys and secrets

## Benefits

- **Automatic deployments**: Push to main branch and deploy automatically
- **Cache management**: Prevents route caching issues that cause 404s
- **Zero-downtime**: Deployments happen without manual intervention
- **Audit trail**: All deployments logged in GitHub Actions
- **Rollback capability**: Git history allows easy rollbacks

This setup ensures your Laravel application deploys smoothly with proper cache management, preventing common issues like route caching that can break your application after deployments.