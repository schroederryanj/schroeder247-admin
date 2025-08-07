<?php

namespace App\Jobs;

use App\Models\ZabbixHost;
use App\Models\User;
use App\Services\ZabbixService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncZabbixHostsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private ?int $userId = null
    ) {}

    public function handle(): void
    {
        try {
            $zabbixService = new ZabbixService();
            
            if (!$zabbixService->testConnection()['success']) {
                Log::error('Zabbix connection test failed, skipping host sync');
                return;
            }

            $zabbixHosts = $zabbixService->getHosts();
            
            if (empty($zabbixHosts)) {
                Log::warning('No hosts found in Zabbix server');
                return;
            }

            $syncedCount = 0;
            $adminUser = User::first();
            
            if (!$adminUser) {
                Log::error('No admin user found for Zabbix host sync');
                return;
            }

            foreach ($zabbixHosts as $hostData) {
                if ($this->userId && $adminUser->id !== $this->userId) {
                    $adminUser = User::find($this->userId);
                    if (!$adminUser) {
                        continue;
                    }
                }

                $existingHost = ZabbixHost::where('zabbix_host_id', $hostData['hostid'])->first();
                
                $hostRecord = ZabbixHost::updateOrCreate(
                    ['zabbix_host_id' => $hostData['hostid']],
                    [
                        'user_id' => $adminUser->id,
                        'name' => $hostData['name'] ?: $hostData['host'],
                        'host' => $hostData['host'],
                        'interfaces' => $hostData['interfaces'] ?? [],
                        'status' => $this->mapHostStatus($hostData['status']),
                        'groups' => $hostData['groups'] ?? [],
                        'monitored' => $hostData['status'] == '0',
                        'last_synced_at' => now(),
                    ]
                );

                if (!$existingHost) {
                    Log::info('New Zabbix host added', [
                        'host_id' => $hostData['hostid'],
                        'name' => $hostData['name'],
                        'host' => $hostData['host']
                    ]);
                }

                $syncedCount++;
                
                $this->syncHostEvents($zabbixService, $hostRecord, $hostData['hostid']);
            }

            $this->cleanupDeletedHosts($zabbixHosts);

            Log::info('Zabbix host sync completed', [
                'synced_hosts' => $syncedCount,
                'total_zabbix_hosts' => count($zabbixHosts)
            ]);

        } catch (Exception $e) {
            Log::error('Failed to sync Zabbix hosts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function syncHostEvents(ZabbixService $zabbixService, ZabbixHost $hostRecord, string $zabbixHostId): void
    {
        try {
            $problems = $zabbixService->getProblems($zabbixHostId);
            
            foreach ($problems as $problemData) {
                $eventId = $problemData['eventid'];
                $trigger = $problemData['triggers'][0] ?? null;
                
                if (!$trigger) {
                    continue;
                }

                $existingEvent = \App\Models\ZabbixEvent::where('zabbix_event_id', $eventId)->first();
                
                if (!$existingEvent) {
                    \App\Models\ZabbixEvent::create([
                        'zabbix_host_id' => $hostRecord->id,
                        'zabbix_event_id' => $eventId,
                        'trigger_id' => $trigger['triggerid'],
                        'name' => $trigger['description'],
                        'description' => null,
                        'severity' => $this->mapSeverity($trigger['priority']),
                        'status' => 'problem',
                        'value' => 'problem',
                        'event_time' => now()->createFromTimestamp($problemData['clock']),
                        'acknowledged' => $problemData['acknowledged'] == '1',
                        'raw_data' => $problemData,
                    ]);
                }
            }

        } catch (Exception $e) {
            Log::warning('Failed to sync events for host', [
                'host_id' => $zabbixHostId,
                'host_name' => $hostRecord->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function cleanupDeletedHosts(array $currentHosts): void
    {
        $currentHostIds = collect($currentHosts)->pluck('hostid')->toArray();
        
        $deletedHosts = ZabbixHost::whereNotIn('zabbix_host_id', $currentHostIds)->get();
        
        foreach ($deletedHosts as $deletedHost) {
            Log::info('Removing deleted Zabbix host', [
                'host_id' => $deletedHost->zabbix_host_id,
                'name' => $deletedHost->name
            ]);
            
            $deletedHost->delete();
        }
    }

    private function mapHostStatus(string $status): string
    {
        return match ($status) {
            '0' => 'monitored',
            '1' => 'unmonitored',
            default => 'unknown',
        };
    }

    private function mapSeverity(string $priority): string
    {
        return match ($priority) {
            '0' => 'not_classified',
            '1' => 'information',
            '2' => 'warning',
            '3' => 'average',
            '4' => 'high',
            '5' => 'disaster',
            default => 'not_classified',
        };
    }
}
