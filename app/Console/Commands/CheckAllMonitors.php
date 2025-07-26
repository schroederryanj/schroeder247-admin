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
                try {
                    // Run synchronously instead of dispatching to queue to avoid queue issues
                    $job = new CheckMonitor($monitor);
                    $job->handle();
                    $dispatchCount++;
                    
                    $this->line("✓ Checked: {$monitor->name} ({$monitor->type})");
                } catch (\Exception $e) {
                    $this->error("✗ Failed to check {$monitor->name}: " . $e->getMessage());
                }
            } else {
                $this->line("- Skipped: {$monitor->name} (checked recently)");
            }
        }

        $this->info("Completed {$dispatchCount} monitor checks out of {$monitors->count()} enabled monitors.");
    }
}
