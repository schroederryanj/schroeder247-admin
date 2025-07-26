<?php

/**
 * Cloudways Deployment Script
 * This script can be triggered after PR merges to automatically deploy
 */

class CloudwaysAPI {
    private $email;
    private $apiKey;
    private $baseUrl = 'https://api.cloudways.com/api/v1';
    private $accessToken;
    
    public function __construct($email, $apiKey) {
        $this->email = $email;
        $this->apiKey = $apiKey;
    }
    
    /**
     * Authenticate and get access token
     */
    public function authenticate() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/oauth/access_token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'email' => $this->email,
            'api_key' => $this->apiKey
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            $this->accessToken = $data['access_token'] ?? null;
            return true;
        }
        
        echo "Authentication failed: " . $response . "\n";
        return false;
    }
    
    /**
     * Make authenticated API request
     */
    private function request($method, $endpoint, $data = []) {
        if (!$this->accessToken) {
            throw new Exception('Not authenticated. Call authenticate() first.');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
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
            'response' => json_decode($response, true) ?: $response
        ];
    }
    
    /**
     * Execute git pull on the application
     */
    public function gitPull($serverId, $appId, $deployPath = 'public_html') {
        echo "Executing git pull for app {$appId}...\n";
        
        $result = $this->request('POST', '/git/pull', [
            'server_id' => $serverId,
            'app_id' => $appId,
            'deploy_path' => $deployPath,
            'branch_name' => 'main'
        ]);
        
        if ($result['code'] === 200) {
            echo "✓ Git pull initiated successfully\n";
            echo "Response: " . json_encode($result['response']) . "\n";
            
            // Get operation ID to track progress
            $operationId = $result['response']['operation_id'] ?? null;
            if ($operationId) {
                $this->trackOperation($serverId, $operationId);
            }
        } else {
            echo "✗ Git pull failed (HTTP {$result['code']}): " . json_encode($result['response']) . "\n";
        }
        
        return $result;
    }
    
    /**
     * Clear Laravel cache and run migrations
     */
    public function runArtisanCommands($serverId, $appId) {
        $commands = [
            'config:cache' => 'Caching configuration',
            'route:cache' => 'Caching routes',
            'view:cache' => 'Caching views',
            'migrate --force' => 'Running migrations',
            'queue:restart' => 'Restarting queue workers'
        ];
        
        foreach ($commands as $command => $description) {
            echo "{$description}...\n";
            
            $result = $this->request('POST', '/app/manage/ssh_cmd', [
                'server_id' => $serverId,
                'app_id' => $appId,
                'command' => "cd /home/master_zgrjnvnece/applications/zgrjnvnece/public_html && php artisan {$command}"
            ]);
            
            if ($result['code'] === 200) {
                echo "✓ {$description} completed\n";
            } else {
                echo "✗ {$description} failed\n";
            }
            
            sleep(2); // Brief pause between commands
        }
    }
    
    /**
     * Track operation status
     */
    private function trackOperation($serverId, $operationId) {
        echo "Tracking operation {$operationId}...\n";
        
        $maxAttempts = 30;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            sleep(2);
            $result = $this->request('GET', "/operation/{$serverId}/{$operationId}");
            
            if ($result['code'] === 200) {
                $status = $result['response']['operation']['status'] ?? 'unknown';
                $message = $result['response']['operation']['message'] ?? '';
                
                echo "Status: {$status} - {$message}\n";
                
                if (in_array($status, ['completed', 'failed'])) {
                    break;
                }
            }
            
            $attempt++;
        }
    }
}

// Configuration
$config = [
    'email' => 'tech@vhdental.com',
    'api_key' => 'Cm5jQnspi9WYcYNVEGM6ZIRsj1zbVJ',
    'server_id' => '1494122',
    'app_id' => '5724401'
];

// Run deployment
echo "=== Cloudways Deployment Script ===\n\n";

$api = new CloudwaysAPI($config['email'], $config['api_key']);

if ($api->authenticate()) {
    echo "✓ Authentication successful\n\n";
    
    // Pull latest code
    $api->gitPull($config['server_id'], $config['app_id']);
    
    // Run Laravel commands
    echo "\nRunning Laravel commands...\n";
    $api->runArtisanCommands($config['server_id'], $config['app_id']);
    
    echo "\n✓ Deployment complete!\n";
} else {
    echo "✗ Authentication failed\n";
    exit(1);
}