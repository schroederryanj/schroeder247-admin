<?php

require_once 'cloudways-deploy.php';

echo "=== Testing Cloudways Git Pull API ===\n\n";

$config = [
    'email' => 'tech@vhdental.com',
    'api_key' => 'Cm5jQnspi9WYcYNVEGM6ZIRsj1zbVJ',
    'server_id' => '1494122',
    'app_id' => '5724401'
];

$api = new CloudwaysAPI($config['email'], $config['api_key']);

if ($api->authenticate()) {
    echo "✓ Authentication successful\n\n";
    
    echo "Attempting git pull with these parameters:\n";
    echo "- Server ID: {$config['server_id']}\n";
    echo "- App ID: {$config['app_id']}\n";
    echo "- Deploy Path: public_html\n";
    echo "- Branch: main\n\n";
    
    // Attempt git pull
    $result = $api->gitPull($config['server_id'], $config['app_id']);
    
    echo "\n--- Git Pull Result ---\n";
    echo "HTTP Code: " . $result['code'] . "\n";
    echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
    
} else {
    echo "✗ Authentication failed\n";
    exit(1);
}