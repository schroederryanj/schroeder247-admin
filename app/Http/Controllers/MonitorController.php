<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\ZabbixHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MonitorController extends Controller
{
    public function index(Request $request)
    {
        $selectedTags = $request->get('tags', []);
        if (is_string($selectedTags)) {
            $selectedTags = [$selectedTags];
        }

        // Build monitor query
        $monitorQuery = Monitor::where('user_id', Auth::id());
        if (!empty($selectedTags)) {
            foreach ($selectedTags as $tag) {
                $monitorQuery->whereJsonContains('tags', $tag);
            }
        }
        $monitors = $monitorQuery->orderBy('created_at', 'desc')->get();

        // Build Zabbix hosts query
        $zabbixHostQuery = ZabbixHost::where('user_id', Auth::id())
            ->with(['activeEvents' => function($query) {
                $query->orderBy('severity', 'desc')->limit(3);
            }]);
        if (!empty($selectedTags)) {
            foreach ($selectedTags as $tag) {
                $zabbixHostQuery->whereJsonContains('tags', $tag);
            }
        }
        $zabbixHosts = $zabbixHostQuery->orderBy('name')->get();

        // Get all available tags for filter dropdown
        $allMonitorTags = Monitor::where('user_id', Auth::id())
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();
        
        $allZabbixTags = ZabbixHost::where('user_id', Auth::id())
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        $allTags = $allMonitorTags->merge($allZabbixTags)->unique()->sort()->values();

        return view('monitors.index', compact('monitors', 'zabbixHosts', 'allTags', 'selectedTags'));
    }

    public function create()
    {
        return view('monitors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => ['required', 'string', 'max:500', function ($attribute, $value, $fail) {
                // Check if it's a valid URL
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return;
                }
                
                // Check if it's a valid IP address (v4 or v6)
                if (filter_var($value, FILTER_VALIDATE_IP)) {
                    return;
                }
                
                // Check if it's a domain name without protocol
                if (preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i', $value)) {
                    return;
                }
                
                $fail('The ' . $attribute . ' must be a valid URL, IP address, or domain name.');
            }],
            'type' => ['required', Rule::in(['http', 'https', 'ping', 'tcp'])],
            'check_interval' => 'required|integer|min:1|max:1440',
            'expected_status_code' => 'nullable|integer|min:100|max:599',
            'timeout' => 'required|integer|min:5|max:60',
            'port' => 'nullable|integer|min:1|max:65535',
            'expected_content' => 'nullable|string|max:1000',
            'ssl_check' => 'boolean',
            'enabled' => 'boolean',
            'sms_notifications' => 'boolean',
            'notification_phone' => 'nullable|string|max:20',
            'email_notifications' => 'boolean',
            'notification_email' => 'nullable|email|max:255',
            'notification_threshold' => 'required|integer|min:1|max:10',
            'tags' => 'nullable|string|max:500'
        ]);

        // Process tags - convert comma-separated string to array
        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
            $validated['tags'] = array_filter($validated['tags']); // Remove empty values
        } else {
            $validated['tags'] = [];
        }

        $validated['user_id'] = Auth::id();
        $validated['current_status'] = 'unknown';

        $monitor = Monitor::create($validated);

        // Note: Initial check will happen automatically via cron job
        // Removed immediate check to avoid namespace issues during creation

        return redirect()->route('monitors.index')
            ->with('success', 'Monitor created successfully! First check will happen within a few minutes.');
    }

    public function show(Monitor $monitor)
    {
        // Check if the monitor belongs to the authenticated user
        if ($monitor->user_id !== Auth::id()) {
            abort(403);
        }
        
        $recentResults = $monitor->results()
            ->orderBy('checked_at', 'desc')
            ->limit(50)
            ->get();

        return view('monitors.show', compact('monitor', 'recentResults'));
    }

    public function edit(Monitor $monitor)
    {
        // Check if the monitor belongs to the authenticated user
        if ($monitor->user_id !== Auth::id()) {
            abort(403);
        }
        
        return view('monitors.edit', compact('monitor'));
    }

    public function update(Request $request, Monitor $monitor)
    {
        // Check if the monitor belongs to the authenticated user
        if ($monitor->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => ['required', 'string', 'max:500', function ($attribute, $value, $fail) {
                // Check if it's a valid URL
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return;
                }
                
                // Check if it's a valid IP address (v4 or v6)
                if (filter_var($value, FILTER_VALIDATE_IP)) {
                    return;
                }
                
                // Check if it's a domain name without protocol
                if (preg_match('/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/i', $value)) {
                    return;
                }
                
                $fail('The ' . $attribute . ' must be a valid URL, IP address, or domain name.');
            }],
            'type' => ['required', Rule::in(['http', 'https', 'ping', 'tcp'])],
            'check_interval' => 'required|integer|min:1|max:1440',
            'expected_status_code' => 'nullable|integer|min:100|max:599',
            'timeout' => 'required|integer|min:5|max:60',
            'port' => 'nullable|integer|min:1|max:65535',
            'expected_content' => 'nullable|string|max:1000',
            'ssl_check' => 'boolean',
            'enabled' => 'boolean',
            'sms_notifications' => 'boolean',
            'notification_phone' => 'nullable|string|max:20',
            'email_notifications' => 'boolean',
            'notification_email' => 'nullable|email|max:255',
            'notification_threshold' => 'required|integer|min:1|max:10',
            'tags' => 'nullable|string|max:500'
        ]);

        // Process tags - convert comma-separated string to array
        if (!empty($validated['tags'])) {
            $validated['tags'] = array_map('trim', explode(',', $validated['tags']));
            $validated['tags'] = array_filter($validated['tags']); // Remove empty values
        } else {
            $validated['tags'] = [];
        }

        $monitor->update($validated);

        return redirect()->route('monitors.index')
            ->with('success', 'Monitor updated successfully!');
    }

    public function destroy(Monitor $monitor)
    {
        // Check if the monitor belongs to the authenticated user
        if ($monitor->user_id !== Auth::id()) {
            abort(403);
        }
        
        $monitor->delete();

        return redirect()->route('monitors.index')
            ->with('success', 'Monitor deleted successfully!');
    }

    public function checkAll()
    {
        try {
            // Run the check-all command which handles all enabled monitors
            \Illuminate\Support\Facades\Artisan::call('monitors:check-all');
            
            // Get count of user's enabled monitors
            $userMonitorCount = Monitor::where('user_id', Auth::id())
                ->where('enabled', true)
                ->count();
            
            if ($userMonitorCount > 0) {
                return back()->with('success', "✅ Checking {$userMonitorCount} monitor(s)! Results will appear shortly.");
            } else {
                return back()->with('warning', "⚠️ No enabled monitors found to check.");
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to trigger monitor checks: ' . $e->getMessage());
        }
    }
}
