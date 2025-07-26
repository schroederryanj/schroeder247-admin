<?php

/**
 * Deployment script for Laravel application
 * This script is triggered after git pull to clear caches
 */

// Only allow execution from command line or with secret key
if (php_sapi_name() !== 'cli') {
    $deploySecret = $_GET['secret'] ?? '';
    if ($deploySecret !== 'your-deploy-secret-here') {
        http_response_code(403);
        die('Forbidden');
    }
}

echo "=== Laravel Post-Deployment Script ===\n\n";

// Helper function to run artisan commands
function runArtisan($command) {
    $output = [];
    $returnCode = 0;
    
    $fullCommand = sprintf(
        'cd %s && php artisan %s 2>&1',
        escapeshellarg(dirname(__FILE__)),
        escapeshellarg($command)
    );
    
    exec($fullCommand, $output, $returnCode);
    
    $status = $returnCode === 0 ? '✓' : '✗';
    echo "{$status} php artisan {$command}\n";
    
    if (!empty($output)) {
        echo "   " . implode("\n   ", $output) . "\n";
    }
    
    return $returnCode === 0;
}

// Clear all caches
echo "Clearing caches...\n";
runArtisan('config:clear');
runArtisan('route:clear');
runArtisan('view:clear');
runArtisan('cache:clear');

echo "\nRebuilding caches...\n";
runArtisan('config:cache');
runArtisan('route:cache');
runArtisan('view:cache');

// Run migrations if needed
echo "\nRunning migrations...\n";
runArtisan('migrate --force');

// Restart queue workers
echo "\nRestarting queue workers...\n";
runArtisan('queue:restart');

echo "\n=== Deployment tasks completed ===\n";

// If accessed via web, return JSON response
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Deployment tasks completed',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}