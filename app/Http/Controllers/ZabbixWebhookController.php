<?php

namespace App\Http\Controllers;

use App\Jobs\ZabbixAlertJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class ZabbixWebhookController extends Controller
{
    public function handleAlert(Request $request)
    {
        try {
            // Comprehensive logging for debugging
            Log::info('Zabbix webhook received', [
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'content_type' => $request->header('Content-Type'),
                'raw_content' => $request->getContent(),
                'parsed_data' => $request->all(),
                'query_params' => $request->query(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $eventData = $request->all();
            
            // Handle empty data
            if (empty($eventData)) {
                // Try to parse raw content if form data is empty
                $rawContent = $request->getContent();
                if (!empty($rawContent)) {
                    Log::info('Attempting to parse raw webhook content', ['raw' => $rawContent]);
                    
                    $decodedData = json_decode($rawContent, true);
                    if (json_last_error() === JSON_ERROR_NONE && !empty($decodedData)) {
                        $eventData = $decodedData;
                        Log::info('Successfully parsed raw JSON content', ['parsed' => $eventData]);
                    } else {
                        Log::warning('Failed to parse raw content as JSON', [
                            'json_error' => json_last_error_msg(),
                            'raw_content' => $rawContent
                        ]);
                    }
                }
                
                if (empty($eventData)) {
                    Log::warning('Empty Zabbix webhook data received after all parsing attempts');
                    return response('Empty data', 400);
                }
            }

            // More lenient validation in production to avoid blocking webhooks
            $isValid = $this->validateWebhookData($eventData);
            
            if (!$isValid) {
                Log::warning('Webhook validation failed, but processing anyway for compatibility', [
                    'data' => $eventData,
                    'data_structure' => $this->getDataStructure($eventData)
                ]);
                
                // Still process the webhook even if validation fails - for debugging
                // In production, you might want to be more lenient to avoid missing alerts
            }

            ZabbixAlertJob::dispatch($eventData);

            Log::info('Zabbix alert job dispatched successfully', [
                'validation_passed' => $isValid,
                'data_keys' => array_keys($eventData)
            ]);
            
            return response('OK', 200);

        } catch (Exception $e) {
            Log::error('Failed to process Zabbix webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'raw_content' => $request->getContent(),
            ]);
            
            return response('Internal Server Error', 500);
        }
    }

    public function test(Request $request)
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
        // Log the actual data structure for debugging
        Log::info('Validating Zabbix webhook data structure', [
            'data_keys' => array_keys($data),
            'data_structure' => $this->getDataStructure($data)
        ]);

        // More flexible validation - accept various Zabbix webhook formats
        
        // Format 1: Standard Zabbix webhook with nested structure
        if (isset($data['host']) && isset($data['event']) && isset($data['trigger'])) {
            Log::info('Detected nested Zabbix webhook format');
            return $this->validateNestedFormat($data);
        }
        
        // Format 2: Flat structure with direct properties
        if (isset($data['hostid']) && isset($data['eventid'])) {
            Log::info('Detected flat Zabbix webhook format');
            return $this->validateFlatFormat($data);
        }
        
        // Format 3: Zabbix 7.x webhook with different structure
        if (isset($data['HOSTNAME']) && isset($data['EVENT.ID'])) {
            Log::info('Detected Zabbix 7.x macro-based format');
            return $this->validateMacroFormat($data);
        }
        
        // Format 4: Custom JSON payload
        if (isset($data['host_name']) || isset($data['hostname'])) {
            Log::info('Detected custom JSON webhook format');
            return $this->validateCustomFormat($data);
        }
        
        Log::warning('No recognized Zabbix webhook format detected', [
            'available_keys' => array_keys($data),
            'sample_data' => array_slice($data, 0, 5, true)
        ]);
        
        return false;
    }
    
    private function validateNestedFormat(array $data): bool
    {
        return isset($data['host']['hostid'], $data['event']['eventid']) ||
               isset($data['host']['host'], $data['event']['eventid']) ||
               isset($data['trigger']['triggerid']);
    }
    
    private function validateFlatFormat(array $data): bool
    {
        return !empty($data['hostid']) || !empty($data['host_name']) || !empty($data['hostname']);
    }
    
    private function validateMacroFormat(array $data): bool
    {
        return !empty($data['HOSTNAME']) || !empty($data['HOST.NAME']) || !empty($data['EVENT.ID']);
    }
    
    private function validateCustomFormat(array $data): bool
    {
        return !empty($data['host_name']) || !empty($data['hostname']) || !empty($data['trigger_name']);
    }
    
    private function getDataStructure(array $data, int $maxDepth = 2): array
    {
        $structure = [];
        foreach ($data as $key => $value) {
            if (is_array($value) && $maxDepth > 0) {
                $structure[$key] = $this->getDataStructure($value, $maxDepth - 1);
            } else {
                $structure[$key] = gettype($value);
            }
        }
        return $structure;
    }
}
