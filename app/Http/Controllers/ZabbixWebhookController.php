<?php

namespace App\Http\Controllers;

use App\Jobs\ZabbixAlertJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Exception;

class ZabbixWebhookController extends Controller
{
    public function handleAlert(Request $request): Response
    {
        try {
            Log::info('Zabbix webhook received', ['data' => $request->all()]);

            $eventData = $request->all();
            
            if (empty($eventData)) {
                Log::warning('Empty Zabbix webhook data received');
                return response('Empty data', 400);
            }

            if (!$this->validateWebhookData($eventData)) {
                Log::warning('Invalid Zabbix webhook data structure', ['data' => $eventData]);
                return response('Invalid data structure', 400);
            }

            ZabbixAlertJob::dispatch($eventData);

            Log::info('Zabbix alert job dispatched successfully');
            return response('OK', 200);

        } catch (Exception $e) {
            Log::error('Failed to process Zabbix webhook', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response('Internal Server Error', 500);
        }
    }

    public function test(Request $request): Response
    {
        Log::info('Zabbix webhook test endpoint called', ['data' => $request->all()]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Zabbix webhook test endpoint is working',
            'timestamp' => now()->toDateTimeString(),
            'received_data' => $request->all(),
        ]);
    }

    private function validateWebhookData(array $data): bool
    {
        if (!isset($data['host']) || !isset($data['event']) || !isset($data['trigger'])) {
            return false;
        }

        if (!isset($data['host']['hostid']) || empty($data['host']['hostid'])) {
            return false;
        }

        if (!isset($data['event']['eventid']) || empty($data['event']['eventid'])) {
            return false;
        }

        if (!isset($data['trigger']['triggerid']) || empty($data['trigger']['triggerid'])) {
            return false;
        }

        return true;
    }
}
