<?php

namespace App\Console\Commands;

use App\Jobs\SyncZabbixHostsJob;
use App\Services\ZabbixService;
use Illuminate\Console\Command;

class SyncZabbixHostsCommand extends Command
{
    protected $signature = 'zabbix:sync-hosts {--queue : Run sync in background queue}';

    protected $description = 'Synchronize Zabbix hosts with the local database';

    public function handle()
    {
        $this->info('Starting Zabbix hosts synchronization...');

        try {
            $zabbixService = new ZabbixService();
            
            $connectionTest = $zabbixService->testConnection();
            if (!$connectionTest['success']) {
                $this->error('Failed to connect to Zabbix server: ' . $connectionTest['message']);
                return 1;
            }

            $this->info('Connected to Zabbix server (v' . $connectionTest['version'] . ')');

            if ($this->option('queue')) {
                SyncZabbixHostsJob::dispatch();
                $this->info('Zabbix host sync job queued successfully!');
            } else {
                $job = new SyncZabbixHostsJob();
                $job->handle();
                $this->info('Zabbix hosts synchronized successfully!');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }
    }
}
