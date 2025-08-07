<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\SMSConversation;
use App\Models\ZabbixHost;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get monitor counts
        $monitors = Monitor::where('user_id', $user->id)->get();
        $upCount = $monitors->where('current_status', 'up')->count();
        $warningCount = $monitors->where('current_status', 'warning')->count();
        $downCount = $monitors->where('current_status', 'down')->count();
        
        // Get Zabbix hosts with active events
        $zabbixHosts = ZabbixHost::where('user_id', $user->id)
            ->with(['activeEvents' => function($query) {
                $query->orderBy('severity', 'desc')->limit(5);
            }])
            ->get();
            
        $zabbixUpCount = $zabbixHosts->where('status', 'monitored')->filter(function($host) {
            return !$host->activeEvents->count();
        })->count();
        
        $zabbixDownCount = $zabbixHosts->filter(function($host) {
            return $host->activeEvents->count() > 0;
        })->count();
        
        // Get recent SMS conversations
        $recentSMS = SMSConversation::incoming()
            ->latest()
            ->limit(5)
            ->get();
        
        // Get active monitors (limit to first 10 for dashboard)
        $activeMonitors = $monitors->take(10);
        
        // Get active Zabbix hosts with problems (limit to first 10 for dashboard)
        $activeZabbixHosts = $zabbixHosts->take(10);

        return view('dashboard', compact(
            'upCount',
            'warningCount', 
            'downCount',
            'zabbixUpCount',
            'zabbixDownCount',
            'recentSMS',
            'monitors',
            'activeMonitors',
            'zabbixHosts',
            'activeZabbixHosts'
        ));
    }
}
