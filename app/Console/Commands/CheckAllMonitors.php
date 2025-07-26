<?php

namespace App\Console\Commands;

use App\Jobs\CheckMonitor;
use App\Models\Monitor;
use Illuminate\Console\Command;

class CheckAllMonitors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitors:check-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all enabled monitors and dispatch monitoring jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitors = Monitor::where('enabled', true)->get();

        if ($monitors->isEmpty()) {
            $this->info('No enabled monitors found.');
            return;
        }

        $dispatchCount = 0;

        foreach ($monitors as $monitor) {
            $lastChecked = $monitor->last_checked_at;
            $checkInterval = $monitor->check_interval;

            if (!$lastChecked || $lastChecked->addMinutes($checkInterval)->isPast()) {
                CheckMonitor::dispatch($monitor);
                $dispatchCount++;
                
                $this->line("Dispatched check for: {$monitor->name}");
            }
        }

        $this->info("Dispatched {$dispatchCount} monitor checks out of {$monitors->count()} enabled monitors.");
    }
}
