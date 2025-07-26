<?php

/**
 * Verify Cloudways Server and App IDs
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
    
    private function request($method, $endpoint) {
        if (!$this->accessToken) {
            throw new Exception('Not authenticated. Call authenticate() first.');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'code' => $httpCode,
            'response' => json_decode($response, true) ?: $response
        ];
    }
    
    public function listServers() {
        echo "=== Your Cloudways Servers and Apps ===\n";
        $result = $this->request('GET', '/server');
        
        if ($result['code'] === 200 && isset($result['response']['servers'])) {
            foreach ($result['response']['servers'] as $server) {
                echo "Server ID: {$server['id']}\n";
                echo "Label: {$server['label']}\n";
                echo "Public IP: {$server['public_ip']}\n";
                echo "Status: {$server['status']}\n";
                echo "Platform: {$server['platform']}\n";
                
                // List apps on this server
                if (isset($server['apps']) && is_array($server['apps'])) {
                    echo "Apps on this server:\n";
                    foreach ($server['apps'] as $app) {
                        echo "  - App ID: {$app['id']}\n";
                        echo "    Label: {$app['label']}\n";
                        echo "    Application: {$app['application']}\n";
                        echo "    Domain: " . (isset($app['cname']) ? $app['cname'] : 'N/A') . "\n";
                        echo "    Project Path: " . (isset($app['project_path']) ? $app['project_path'] : 'N/A') . "\n";
                        echo "    ---\n";
                    }
                } else {
                    echo "No apps found on this server\n";
                }
                echo "==================\n";
            }
        } else {
            echo "Failed to get servers: " . json_encode($result) . "\n";
        }
    }
    
    public function listApps($serverId) {
        echo "\n=== Apps on Server {$serverId} ===\n";
        $result = $this->request('GET', "/app");
        
        if ($result['code'] === 200 && isset($result['response']['apps'])) {
            foreach ($result['response']['apps'] as $app) {
                if ($app['server_id'] == $serverId) {
                    echo "App ID: {$app['id']}\n";
                    echo "Label: {$app['label']}\n";
                    echo "Application: {$app['application']}\n";
                    echo "Domain: {$app['cname']}\n";
                    echo "Project Path: {$app['project_path']}\n";
                    echo "---\n";
                }
            }
        } else {
            echo "Failed to get apps: " . json_encode($result) . "\n";
        }
    }
}

// Configuration
$config = [
    'email' => 'tech@vhdental.com',
    'api_key' => 'Cm5jQnspi9WYcYNVEGM6ZIRsj1zbVJ'
];

echo "=== Cloudways ID Verification ===\n\n";

$api = new CloudwaysAPI($config['email'], $config['api_key']);

if ($api->authenticate()) {
    echo "✓ Authentication successful\n\n";
    
    // List all servers and their apps
    $api->listServers();
    
} else {
    echo "✗ Authentication failed\n";
    exit(1);
}