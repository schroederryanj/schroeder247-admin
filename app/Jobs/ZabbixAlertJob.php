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
            $zabbixHostId = $this->eventData['host']['hostid'] ?? null;
            
            if (!$zabbixHostId) {
                Log::warning('Zabbix alert missing host ID', ['data' => $this->eventData]);
                return;
            }

            $zabbixHost = ZabbixHost::where('zabbix_host_id', $zabbixHostId)->first();
            
            if (!$zabbixHost) {
                Log::info('Zabbix host not found locally, skipping alert', ['hostId' => $zabbixHostId]);
                return;
            }

            $eventId = $this->eventData['event']['eventid'] ?? uniqid('zabbix_');
            $triggerName = $this->eventData['trigger']['name'] ?? 'Unknown trigger';
            $severity = $this->mapSeverity($this->eventData['trigger']['priority'] ?? '0');
            $status = $this->eventData['event']['value'] == '1' ? 'problem' : 'ok';
            $eventTime = isset($this->eventData['event']['clock']) ? 
                now()->createFromTimestamp($this->eventData['event']['clock']) : now();

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
}
