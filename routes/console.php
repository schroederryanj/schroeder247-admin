<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monitor checks to run every minute
Schedule::command('monitors:check-all')->everyMinute();

// Schedule Zabbix host sync to run every 5 minutes
Schedule::command('zabbix:sync-hosts')->everyFiveMinutes();
