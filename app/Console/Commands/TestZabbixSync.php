<?php

namespace App\Console\Commands;

use App\Services\ZabbixService;
use Illuminate\Console\Command;

class TestZabbixSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zabbix:test-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Zabbix connection and host retrieval';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Zabbix connection...');
        
        $zabbix = new ZabbixService();
        $test = $zabbix->testConnection();
        
        if ($test['success']) {
            $this->info('✓ Connection successful: ' . $test['message']);
            
            // Let's manually test the exact auth flow
            $this->info('Testing manual auth...');
            
            try {
                // Test using API token directly
                $apiToken = config('services.zabbix.api_token');
                
                if ($apiToken) {
                    $this->info('Using API token: ' . substr($apiToken, 0, 10) . '...');
                    
                    $reflection = new \ReflectionClass($zabbix);
                    $makeRequestMethod = $reflection->getMethod('makeRequest');
                    $makeRequestMethod->setAccessible(true);
                    
                    // Try to use the API token for host.get
                    $hostResponse = $makeRequestMethod->invoke($zabbix, 'host.get', [
                        'output' => ['hostid', 'host', 'name'],
                        'limit' => 5
                    ], $apiToken);
                    
                    $this->info('Host response: ' . json_encode($hostResponse));
                    
                    if (isset($hostResponse['result'])) {
                        $this->info('✓ Successfully got ' . count($hostResponse['result']) . ' hosts!');
                        foreach ($hostResponse['result'] as $host) {
                            $this->info('  - ' . $host['name'] . ' (' . $host['host'] . ')');
                        }
                    } else {
                        $this->error('✗ Host request failed: ' . ($hostResponse['error']['message'] ?? 'Unknown error'));
                    }
                } else {
                    $this->warn('No API token configured');
                }
                
            } catch (\Exception $e) {
                $this->error('Manual test failed: ' . $e->getMessage());
            }
            
            // Continue with original method
            $hosts = $zabbix->getHosts();
            $this->info('Original getHosts() returned: ' . count($hosts) . ' hosts');
            
            if (count($hosts) > 0) {
                $this->table(['Host ID', 'Name', 'Host', 'Status'], 
                    collect($hosts)->take(5)->map(function($host) {
                        return [
                            $host['hostid'],
                            $host['name'],
                            $host['host'],
                            $host['status'] == '0' ? 'Monitored' : 'Unmonitored'
                        ];
                    })->toArray()
                );
                
                // Test creating/updating a host record
                $this->info('Testing database sync for first host...');
                $firstHost = $hosts[0];
                
                $hostRecord = \App\Models\ZabbixHost::updateOrCreate(
                    ['zabbix_host_id' => $firstHost['hostid']],
                    [
                        'user_id' => \App\Models\User::first()->id,
                        'name' => $firstHost['name'] ?: $firstHost['host'],
                        'host' => $firstHost['host'],
                        'interfaces' => $firstHost['interfaces'] ?? [],
                        'status' => $firstHost['status'] == '0' ? 'monitored' : 'unmonitored',
                        'groups' => $firstHost['groups'] ?? [],
                        'monitored' => $firstHost['status'] == '0',
                        'last_synced_at' => now(),
                    ]
                );
                
                $this->info('✓ Successfully synced host: ' . $hostRecord->name);
                
            } else {
                $this->warn('No hosts found in Zabbix');
            }
        } else {
            $this->error('✗ Connection failed: ' . $test['message']);
        }
    }
}
