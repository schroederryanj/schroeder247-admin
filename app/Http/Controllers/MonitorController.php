<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MonitorController extends Controller
{
    public function index()
    {
        $monitors = Monitor::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('monitors.index', compact('monitors'));
    }

    public function create()
    {
        return view('monitors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
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
            'notification_threshold' => 'required|integer|min:1|max:10'
        ]);

        $validated['user_id'] = Auth::id();
        $validated['current_status'] = 'unknown';

        Monitor::create($validated);

        return redirect()->route('monitors.index')
            ->with('success', 'Monitor created successfully!');
    }

    public function show(Monitor $monitor)
    {
        $this->authorize('view', $monitor);
        
        $recentResults = $monitor->results()
            ->orderBy('checked_at', 'desc')
            ->limit(50)
            ->get();

        return view('monitors.show', compact('monitor', 'recentResults'));
    }

    public function edit(Monitor $monitor)
    {
        $this->authorize('update', $monitor);
        
        return view('monitors.edit', compact('monitor'));
    }

    public function update(Request $request, Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
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
            'notification_threshold' => 'required|integer|min:1|max:10'
        ]);

        $monitor->update($validated);

        return redirect()->route('monitors.index')
            ->with('success', 'Monitor updated successfully!');
    }

    public function destroy(Monitor $monitor)
    {
        $this->authorize('delete', $monitor);
        
        $monitor->delete();

        return redirect()->route('monitors.index')
            ->with('success', 'Monitor deleted successfully!');
    }
}
