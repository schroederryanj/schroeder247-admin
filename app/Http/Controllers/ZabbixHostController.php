<?php

namespace App\Http\Controllers;

use App\Jobs\SyncZabbixHostsJob;
use App\Models\ZabbixHost;
use App\Services\ZabbixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ZabbixHostController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $zabbixHosts = ZabbixHost::where('user_id', $user->id)
            ->with(['activeEvents' => function($query) {
                $query->orderBy('severity', 'desc')->limit(3);
            }])
            ->orderBy('name')
            ->paginate(20);

        return view('zabbix-hosts.index', compact('zabbixHosts'));
    }

    public function show(ZabbixHost $zabbixHost)
    {
        $this->authorize('view', $zabbixHost);
        
        $zabbixHost->load([
            'events' => function($query) {
                $query->orderBy('event_time', 'desc')->limit(50);
            }
        ]);

        return view('zabbix-hosts.show', compact('zabbixHost'));
    }

    public function edit(ZabbixHost $zabbixHost)
    {
        $this->authorize('update', $zabbixHost);

        return view('zabbix-hosts.edit', compact('zabbixHost'));
    }

    public function update(Request $request, ZabbixHost $zabbixHost)
    {
        $this->authorize('update', $zabbixHost);

        $validated = $request->validate([
            'sms_notifications' => 'boolean',
            'notification_phone' => 'nullable|string|max:20',
            'email_notifications' => 'boolean', 
            'notification_email' => 'nullable|email|max:255',
        ]);

        $zabbixHost->update($validated);

        return redirect()
            ->route('zabbix-hosts.index')
            ->with('success', 'Zabbix host notification settings updated successfully.');
    }

    public function sync()
    {
        try {
            $zabbixService = new ZabbixService();
            
            $connectionTest = $zabbixService->testConnection();
            if (!$connectionTest['success']) {
                return redirect()
                    ->back()
                    ->with('error', 'Failed to connect to Zabbix server: ' . $connectionTest['message']);
            }

            SyncZabbixHostsJob::dispatch(auth()->id());

            return redirect()
                ->back()
                ->with('success', 'Zabbix host synchronization started. This may take a few minutes to complete.');

        } catch (\Exception $e) {
            Log::error('Manual Zabbix sync failed', ['error' => $e->getMessage()]);
            
            return redirect()
                ->back()
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function testConnection()
    {
        try {
            $zabbixService = new ZabbixService();
            $result = $zabbixService->testConnection();

            if ($result['success']) {
                return redirect()
                    ->back()
                    ->with('success', $result['message'] . ' (Version: ' . $result['version'] . ')');
            } else {
                return redirect()
                    ->back()
                    ->with('error', $result['message']);
            }

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Connection test failed: ' . $e->getMessage());
        }
    }

    public function acknowledgeEvent(Request $request, ZabbixHost $zabbixHost)
    {
        $this->authorize('update', $zabbixHost);
        
        $validated = $request->validate([
            'event_id' => 'required|string',
            'message' => 'nullable|string|max:255',
        ]);

        try {
            $zabbixService = new ZabbixService();
            $success = $zabbixService->acknowledgeEvent(
                $validated['event_id'],
                $validated['message'] ?: 'Acknowledged via monitoring dashboard'
            );

            if ($success) {
                $event = \App\Models\ZabbixEvent::where('zabbix_event_id', $validated['event_id'])->first();
                if ($event) {
                    $event->update(['acknowledged' => true]);
                }

                return redirect()
                    ->back()
                    ->with('success', 'Event acknowledged successfully.');
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'Failed to acknowledge event in Zabbix.');
            }

        } catch (\Exception $e) {
            Log::error('Failed to acknowledge Zabbix event', [
                'event_id' => $validated['event_id'],
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Failed to acknowledge event: ' . $e->getMessage());
        }
    }
}
