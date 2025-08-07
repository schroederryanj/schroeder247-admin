<?php

namespace App\Jobs;

use App\Models\ZabbixHost;
use App\Models\ZabbixEvent;
use App\Models\SMSConversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;
use Exception;

class ZabbixAlertJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $eventData
    ) {}

    public function handle(): void
    {
        try {
            Log::info('Processing Zabbix alert job', [
                'data_keys' => array_keys($this->eventData),
                'data_structure' => $this->getDataStructure($this->eventData)
            ]);

            // Extract data using flexible parsing
            $zabbixHostId = $this->extractHostId($this->eventData);
            $hostName = $this->extractHostName($this->eventData);
            
            if (!$zabbixHostId && !$hostName) {
                Log::warning('Zabbix alert missing host ID and name', ['data' => $this->eventData]);
                return;
            }

            // Try to find host by ID first, then by name
            $zabbixHost = null;
            if ($zabbixHostId) {
                $zabbixHost = ZabbixHost::where('zabbix_host_id', $zabbixHostId)->first();
            }
            
            if (!$zabbixHost && $hostName) {
                $zabbixHost = ZabbixHost::where('name', $hostName)
                    ->orWhere('host', $hostName)
                    ->first();
            }
            
            // For malformed webhooks, try to match any existing host as a fallback
            if (!$zabbixHost && ($zabbixHostId === null || $zabbixHostId === '') && ($hostName === null || $hostName === '')) {
                Log::warning('Webhook has no valid host identification - checking for single host fallback');
                
                $allHosts = ZabbixHost::all();
                if ($allHosts->count() === 1) {
                    $zabbixHost = $allHosts->first();
                    Log::info('Using single available Zabbix host as fallback', [
                        'fallback_host' => $zabbixHost->name,
                        'host_id' => $zabbixHost->zabbix_host_id
                    ]);
                }
            }
            
            if (!$zabbixHost) {
                Log::warning('Zabbix host not found locally, skipping alert', [
                    'hostId' => $zabbixHostId,
                    'hostName' => $hostName,
                    'webhook_data_has_nulls' => $this->hasNullValues($this->eventData),
                    'available_hosts_count' => ZabbixHost::count(),
                    'sample_data' => array_slice($this->eventData, 0, 3, true)
                ]);
                return;
            }

            $eventId = $this->extractEventId($this->eventData);
            $triggerName = $this->extractTriggerName($this->eventData);
            $severity = $this->mapSeverity($this->extractSeverity($this->eventData));
            $status = $this->extractStatus($this->eventData);
            $eventTime = $this->extractEventTime($this->eventData);

            $existingEvent = ZabbixEvent::where('zabbix_event_id', $eventId)->first();
            
            if ($status === 'ok' && $existingEvent) {
                $existingEvent->update([
                    'status' => 'ok',
                    'recovery_time' => $eventTime,
                ]);
                
                $this->sendRecoveryNotification($zabbixHost, $existingEvent);
                
            } elseif ($status === 'problem') {
                $event = ZabbixEvent::updateOrCreate(
                    ['zabbix_event_id' => $eventId],
                    [
                        'zabbix_host_id' => $zabbixHost->id,
                        'trigger_id' => $this->eventData['trigger']['triggerid'] ?? '',
                        'name' => $triggerName,
                        'description' => $this->eventData['trigger']['description'] ?? null,
                        'severity' => $severity,
                        'status' => $status,
                        'value' => $status,
                        'event_time' => $eventTime,
                        'raw_data' => $this->eventData,
                    ]
                );

                if (!$event->notification_sent) {
                    $this->sendProblemNotification($zabbixHost, $event);
                    $event->update(['notification_sent' => true]);
                }
            }

            Log::info('Zabbix alert processed', [
                'host' => $zabbixHost->name,
                'event' => $triggerName,
                'severity' => $severity,
                'status' => $status
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process Zabbix alert', [
                'error' => $e->getMessage(),
                'data' => $this->eventData
            ]);
            throw $e;
        }
    }

    private function sendProblemNotification(ZabbixHost $zabbixHost, ZabbixEvent $event): void
    {
        if ($zabbixHost->sms_notifications && $zabbixHost->notification_phone) {
            $this->sendSMSAlert($zabbixHost, $event);
        }

        if ($zabbixHost->email_notifications && $zabbixHost->notification_email) {
            $this->sendEmailAlert($zabbixHost, $event);
        }
    }

    private function sendRecoveryNotification(ZabbixHost $zabbixHost, ZabbixEvent $event): void
    {
        if ($zabbixHost->sms_notifications && $zabbixHost->notification_phone) {
            $this->sendSMSRecoveryAlert($zabbixHost, $event);
        }

        if ($zabbixHost->email_notifications && $zabbixHost->notification_email) {
            $this->sendEmailRecoveryAlert($zabbixHost, $event);
        }
    }

    private function sendSMSAlert(ZabbixHost $zabbixHost, ZabbixEvent $event): void
    {
        try {
            $severityEmoji = match ($event->severity) {
                'disaster' => '💥',
                'high' => '🔴',
                'average' => '🟠',
                'warning' => '🟡',
                'information' => '🔵',
                default => '⚪',
            };
            
            $statusText = strtoupper($event->severity) . ' ALERT';
            
            $message = "{$severityEmoji} ZABBIX {$statusText}: {$zabbixHost->name}\n\n";
            $message .= "Issue: {$event->name}\n";
            $message .= "Host: {$zabbixHost->host}\n";
            $message .= "Time: " . $event->event_time->format('M j, H:i') . "\n";
            $message .= "Severity: " . strtoupper($event->severity) . "\n\n";
            $message .= "Check your Zabbix dashboard for details.";

            $twilio = new TwilioClient(
                config('services.twilio.sid'),
                config('services.twilio.auth_token')
            );

            $twilio->messages->create($zabbixHost->notification_phone, [
                'from' => config('services.twilio.phone_number'),
                'body' => $message
            ]);

            SMSConversation::create([
                'phone_number' => $zabbixHost->notification_phone,
                'message_type' => 'outgoing',
                'content' => $message,
                'processed' => true,
                'processed_at' => now(),
            ]);

            Log::info('Zabbix SMS alert sent', [
                'host' => $zabbixHost->name,
                'phone' => $zabbixHost->notification_phone,
                'event' => $event->name
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send Zabbix SMS alert', [
                'host' => $zabbixHost->name,
                'phone' => $zabbixHost->notification_phone,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendEmailAlert(ZabbixHost $zabbixHost, ZabbixEvent $event): void
    {
        try {
            $severityEmoji = $event->severity_icon;
            $statusText = strtoupper($event->severity) . ' ALERT';
            
            $subject = "{$severityEmoji} ZABBIX {$statusText}: {$zabbixHost->name}";
            
            $message = "Zabbix Alert\n\n";
            $message .= "Host: {$zabbixHost->name} ({$zabbixHost->host})\n";
            $message .= "Issue: {$event->name}\n";
            $message .= "Severity: " . strtoupper($event->severity) . "\n";
            $message .= "Time: " . $event->event_time->format('M j, Y H:i T') . "\n\n";
            
            if ($event->description) {
                $message .= "Description: {$event->description}\n\n";
            }
            
            $message .= "Check your Zabbix dashboard for more details.\n\n";
            $message .= "This is an automated alert from your Zabbix monitoring system.";

            \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($subject, $zabbixHost) {
                $mail->to($zabbixHost->notification_email)
                     ->subject($subject)
                     ->from(config('mail.from.address', 'noreply@' . parse_url(config('app.url'), PHP_URL_HOST)), 
                            config('mail.from.name', 'Zabbix Monitor System'));
            });

            Log::info('Zabbix email alert sent', [
                'host' => $zabbixHost->name,
                'email' => $zabbixHost->notification_email,
                'event' => $event->name
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send Zabbix email alert', [
                'host' => $zabbixHost->name,
                'email' => $zabbixHost->notification_email,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendSMSRecoveryAlert(ZabbixHost $zabbixHost, ZabbixEvent $event): void
    {
        try {
            $message = "✅ ZABBIX RECOVERED: {$zabbixHost->name}\n\n";
            $message .= "Issue: {$event->name}\n";
            $message .= "Host: {$zabbixHost->host}\n";
            $message .= "Time: " . now()->format('M j, H:i') . "\n\n";
            $message .= "Problem has been resolved! 🎉";

            $twilio = new TwilioClient(
                config('services.twilio.sid'),
                config('services.twilio.auth_token')
            );

            $twilio->messages->create($zabbixHost->notification_phone, [
                'from' => config('services.twilio.phone_number'),
                'body' => $message
            ]);

            SMSConversation::create([
                'phone_number' => $zabbixHost->notification_phone,
                'message_type' => 'outgoing',
                'content' => $message,
                'processed' => true,
                'processed_at' => now(),
            ]);

            Log::info('Zabbix SMS recovery alert sent', [
                'host' => $zabbixHost->name,
                'phone' => $zabbixHost->notification_phone
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send Zabbix SMS recovery alert', [
                'host' => $zabbixHost->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendEmailRecoveryAlert(ZabbixHost $zabbixHost, ZabbixEvent $event): void
    {
        try {
            $subject = "✅ ZABBIX RECOVERED: {$zabbixHost->name}";
            
            $message = "Zabbix Recovery\n\n";
            $message .= "Great news! The issue has been resolved.\n\n";
            $message .= "Host: {$zabbixHost->name} ({$zabbixHost->host})\n";
            $message .= "Issue: {$event->name}\n";
            $message .= "Recovery Time: " . now()->format('M j, Y H:i T') . "\n\n";
            $message .= "This is an automated recovery notification from your Zabbix monitoring system.";

            \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($subject, $zabbixHost) {
                $mail->to($zabbixHost->notification_email)
                     ->subject($subject)
                     ->from(config('mail.from.address', 'noreply@' . parse_url(config('app.url'), PHP_URL_HOST)), 
                            config('mail.from.name', 'Zabbix Monitor System'));
            });

            Log::info('Zabbix email recovery alert sent', [
                'host' => $zabbixHost->name,
                'email' => $zabbixHost->notification_email
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send Zabbix email recovery alert', [
                'host' => $zabbixHost->name,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function mapSeverity(string $priority): string
    {
        return match ($priority) {
            '0' => 'not_classified',
            '1' => 'information',
            '2' => 'warning',
            '3' => 'average',
            '4' => 'high',
            '5' => 'disaster',
            default => 'not_classified',
        };
    }

    private function extractHostId(array $data): ?string
    {
        // Try various possible locations for host ID
        return $data['host']['hostid'] ?? 
               $data['hostid'] ?? 
               $data['HOST.ID'] ?? 
               $data['host_id'] ?? 
               null;
    }

    private function extractHostName(array $data): ?string
    {
        // Try various possible locations for host name
        return $data['host']['name'] ?? 
               $data['host']['host'] ?? 
               $data['hostname'] ?? 
               $data['host_name'] ?? 
               $data['HOSTNAME'] ?? 
               $data['HOST.NAME'] ?? 
               null;
    }

    private function extractEventId(array $data): string
    {
        // Try various possible locations for event ID
        return $data['event']['eventid'] ?? 
               $data['eventid'] ?? 
               $data['EVENT.ID'] ?? 
               $data['event_id'] ?? 
               uniqid('zabbix_');
    }

    private function extractTriggerName(array $data): string
    {
        // Try various possible locations for trigger name
        return $data['trigger']['name'] ?? 
               $data['trigger_name'] ?? 
               $data['TRIGGER.NAME'] ?? 
               $data['trigger']['description'] ?? 
               $data['item_name'] ?? 
               $data['ITEM.NAME'] ?? 
               'Unknown trigger';
    }

    private function extractSeverity(array $data): string
    {
        // Try various possible locations for severity/priority
        return $data['trigger']['priority'] ?? 
               $data['priority'] ?? 
               $data['severity'] ?? 
               $data['TRIGGER.SEVERITY'] ?? 
               $data['EVENT.SEVERITY'] ?? 
               '0';
    }

    private function extractStatus(array $data): string
    {
        // Try various possible locations for event status
        $value = $data['event']['value'] ?? 
                 $data['value'] ?? 
                 $data['status'] ?? 
                 $data['EVENT.VALUE'] ?? 
                 $data['trigger']['status'] ?? 
                 null;
        
        if ($value === null) {
            return 'problem'; // Default to problem if we can't determine
        }
        
        return ($value == '1' || $value === 'problem') ? 'problem' : 'ok';
    }

    private function extractEventTime(array $data): \Carbon\Carbon
    {
        // Try various possible locations for event time
        $timestamp = $data['event']['clock'] ?? 
                    $data['clock'] ?? 
                    $data['timestamp'] ?? 
                    $data['EVENT.TIME'] ?? 
                    null;
        
        // Handle malformed timestamps like "undefinedTundefined"
        if ($timestamp && is_string($timestamp) && str_contains($timestamp, 'undefined')) {
            Log::warning('Malformed timestamp detected in webhook', [
                'raw_timestamp' => $timestamp,
                'using_fallback' => 'current time'
            ]);
            return now();
        }
        
        if ($timestamp && is_numeric($timestamp)) {
            // Handle future timestamps (like 1754545305) that might be malformed
            $timestampNum = (int) $timestamp;
            if ($timestampNum > time() + (365 * 24 * 3600)) { // More than a year in the future
                Log::warning('Future timestamp detected, likely malformed', [
                    'timestamp' => $timestampNum,
                    'human_date' => date('Y-m-d H:i:s', $timestampNum),
                    'using_fallback' => 'current time'
                ]);
                return now();
            }
            return now()->createFromTimestamp($timestampNum);
        }
        
        return now();
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
    
    private function hasNullValues(array $data): bool
    {
        $nullCount = 0;
        $totalCount = 0;
        
        array_walk_recursive($data, function($value) use (&$nullCount, &$totalCount) {
            $totalCount++;
            if ($value === null || $value === '' || $value === 'null') {
                $nullCount++;
            }
        });
        
        return $totalCount > 0 && ($nullCount / $totalCount) > 0.5; // More than 50% null values
    }
}
