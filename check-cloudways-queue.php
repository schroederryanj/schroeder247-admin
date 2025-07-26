<?php

// Cloudways API credentials
$email = 'tech@vhdental.com';
$apiKey = 'Cm5jQnspi9WYcYNVEGM6ZIRsj1zbVJ';
$serverId = '1494122';
$appId = '5724401';

// Function to make API calls
function cloudwaysAPI($method, $endpoint, $data = []) {
    global $email, $apiKey;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudways.com/api/v1" . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, $email . ':' . $apiKey);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Check server details
echo "Checking server details...\n";
$server = cloudwaysAPI('GET', "/server/$serverId");
print_r($server);

// Check application details
echo "\n\nChecking application details...\n";
$app = cloudwaysAPI('GET', "/app/$appId");
print_r($app);

// Get supervisor status (if available through API)
echo "\n\nChecking for supervisor/queue configuration...\n";
$appSettings = cloudwaysAPI('GET', "/app/$appId/settings");
print_r($appSettings);