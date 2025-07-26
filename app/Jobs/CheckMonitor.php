<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorResult;
use App\Models\SMSConversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;
use Exception;

class CheckMonitor implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Monitor $monitor
    ) {}

    public function handle(): void
    {
        if (!$this->monitor->enabled) {
            return;
        }

        $startTime = \microtime(true);
        $result = [
            'monitor_id' => $this->monitor->id,
            'checked_at' => now(),
            'status' => 'down',
            'response_time' => null,
            'status_code' => null,
            'error_message' => null
        ];

        try {
            switch ($this->monitor->type) {
                case 'http':
                case 'https':
                    $this->checkHttp($result, $startTime);
                    break;
                case 'ping':
                    $this->checkPing($result, $startTime);
                    break;
                case 'tcp':
                    $this->checkTcp($result, $startTime);
                    break;
            }

            $this->monitor->update([
                'current_status' => $result['status'],
                'last_checked_at' => now()
            ]);

            MonitorResult::create($result);

            // Check if we need to send notifications
            $this->checkNotifications($result['status']);

            Log::info('Monitor check completed', [
                'monitor_id' => $this->monitor->id,
                'status' => $result['status'],
                'response_time' => $result['response_time']
            ]);

        } catch (Exception $e) {
            $result['error_message'] = $e->getMessage();
            
            $this->monitor->update([
                'current_status' => 'down',
                'last_checked_at' => now()
            ]);

            MonitorResult::create($result);

            // Check if we need to send notifications for failure
            $this->checkNotifications('down');

            Log::error('Monitor check failed', [
                'monitor_id' => $this->monitor->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkHttp(array &$result, float $startTime): void
    {
        try {
            // Add more robust HTTP options for various servers
            $response = Http::timeout($this->monitor->timeout)
                ->withOptions([
                    'verify' => false, // Don't verify SSL for problematic certificates
                    'allow_redirects' => true, // Follow redirects
                    'http_errors' => false, // Don't throw on 4xx/5xx status codes
                ])
                ->withHeaders([
                    'User-Agent' => 'SchroederMonitor/1.0',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($this->monitor->url);

            $endTime = \microtime(true);
            $result['response_time'] = \round(($endTime - $startTime) * 1000);
            $result['status_code'] = $response->status();

            // Log the response for debugging
            Log::info('HTTP check response', [
                'monitor_id' => $this->monitor->id,
                'url' => $this->monitor->url,
                'status_code' => $response->status(),
                'response_time' => $result['response_time'],
                'content_length' => \strlen($response->body()),
                'headers' => $response->headers()
            ]);

            if ($this->monitor->expected_status_code) {
                $statusOk = $response->status() === $this->monitor->expected_status_code;
            } else {
                // Consider 2xx and 3xx as successful
                $statusOk = $response->status() >= 200 && $response->status() < 400;
            }

            if ($this->monitor->expected_content) {
                $contentOk = \str_contains($response->body(), $this->monitor->expected_content);
            } else {
                $contentOk = true;
            }

            if ($this->monitor->ssl_check && $this->monitor->type === 'https') {
                $sslOk = $this->checkSsl($this->monitor->url);
            } else {
                $sslOk = true;
            }

            if ($statusOk && $contentOk && $sslOk) {
                $result['status'] = 'up';
            } elseif ($statusOk && !$contentOk) {
                $result['status'] = 'warning';
                $result['error_message'] = 'Expected content not found';
            } elseif (!$sslOk) {
                $result['status'] = 'warning';
                $result['error_message'] = 'SSL certificate issue';
            } else {
                $result['error_message'] = 'HTTP status: ' . $response->status();
            }

        } catch (\Exception $e) {
            $endTime = \microtime(true);
            $result['response_time'] = \round(($endTime - $startTime) * 1000);
            $result['error_message'] = 'HTTP request failed: ' . $e->getMessage();
            
            Log::error('HTTP check failed', [
                'monitor_id' => $this->monitor->id,
                'url' => $this->monitor->url,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkPing(array &$result, float $startTime): void
    {
        $host = \parse_url($this->monitor->url, PHP_URL_HOST) ?: $this->monitor->url;
        
        // Detect OS and use appropriate ping command
        if (PHP_OS_FAMILY === 'Windows') {
            $pingCommand = "ping -n 1 -w " . ($this->monitor->timeout * 1000) . " " . $this->escapeShellArg($host);
        } else {
            // Linux/Unix ping command
            $pingCommand = "ping -c 1 -W " . $this->monitor->timeout . " " . $this->escapeShellArg($host);
        }
        
        $pingResult = $this->execCommand($pingCommand, $output, $returnCode);
        
        $endTime = \microtime(true);
        $result['response_time'] = \round(($endTime - $startTime) * 1000);

        if ($returnCode === 0) {
            $result['status'] = 'up';
            
            // Try to extract response time from ping output
            if (\preg_match('/time=(\d+\.?\d*)\s*ms/', \implode("\n", $output), $matches)) {
                $result['response_time'] = \round(\floatval($matches[1]));
            }
        } else {
            $result['error_message'] = 'Ping failed: host unreachable';
        }
    }

    private function checkTcp(array &$result, float $startTime): void
    {
        $host = \parse_url($this->monitor->url, PHP_URL_HOST) ?: $this->monitor->url;
        $port = $this->monitor->port ?: 80;

        $connection = @\fsockopen($host, $port, $errno, $errstr, $this->monitor->timeout);
        
        $endTime = \microtime(true);
        $result['response_time'] = \round(($endTime - $startTime) * 1000);

        if ($connection) {
            $result['status'] = 'up';
            \fclose($connection);
        } else {
            $result['error_message'] = "TCP connection failed: $errstr ($errno)";
        }
    }

    private function checkSsl(string $url): bool
    {
        $parsedUrl = \parse_url($url);
        $host = $parsedUrl['host'];
        $port = $parsedUrl['port'] ?? 443;

        $context = \stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ]);

        $socket = @\stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            $this->monitor->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return false;
        }

        $cert = \stream_context_get_params($socket)['options']['ssl']['peer_certificate'];
        \fclose($socket);

        if (!$cert) {
            return false;
        }

        $certInfo = \openssl_x509_parse($cert);
        $expiryDate = $certInfo['validTo_time_t'];

        return $expiryDate > \time() + (30 * 24 * 60 * 60);
    }

    private function checkNotifications(string $currentStatus): void
    {
        // Only send notifications if monitor is down or has warnings
        if ($currentStatus === 'up') {
            return;
        }

        // Check if enough consecutive failures have occurred
        $recentFailures = $this->monitor->results()
            ->where('status', '!=', 'up')
            ->orderBy('checked_at', 'desc')
            ->limit($this->monitor->notification_threshold)
            ->count();

        if ($recentFailures < $this->monitor->notification_threshold) {
            return;
        }

        // Don't spam notifications - check if we sent one recently
        $lastNotification = $this->monitor->last_notification_sent;
        $notificationCooldown = 30; // 30 minutes between notifications

        if ($lastNotification && $lastNotification->diffInMinutes(now()) < $notificationCooldown) {
            return;
        }

        // Send SMS notification if enabled
        if ($this->monitor->sms_notifications && $this->monitor->notification_phone) {
            $this->sendSMSAlert($currentStatus);
        }

        // Send Email notification if enabled (placeholder for future implementation)
        if ($this->monitor->email_notifications && $this->monitor->notification_email) {
            $this->sendEmailAlert($currentStatus);
        }

        // Update last notification sent timestamp
        $this->monitor->update(['last_notification_sent' => now()]);
    }

    private function sendSMSAlert(string $status): void
    {
        try {
            $statusEmoji = $status === 'down' ? '❌' : '⚠️';
            $statusText = strtoupper($status);
            
            $message = "{$statusEmoji} ALERT: {$this->monitor->name} is {$statusText}\n\n";
            $message .= "URL: {$this->monitor->url}\n";
            $message .= "Time: " . now()->format('M j, H:i') . "\n";
            $message .= "Type: " . strtoupper($this->monitor->type) . "\n\n";
            $message .= "Check your dashboard for more details.";

            $twilio = new TwilioClient(
                config('services.twilio.sid'),
                config('services.twilio.auth_token')
            );

            $twilio->messages->create($this->monitor->notification_phone, [
                'from' => config('services.twilio.phone_number'),
                'body' => $message
            ]);

            // Store the outgoing notification in SMS conversations
            SMSConversation::create([
                'phone_number' => $this->monitor->notification_phone,
                'message_type' => 'outgoing',
                'content' => $message,
                'processed' => true,
                'processed_at' => now(),
            ]);

            Log::info('SMS alert sent', [
                'monitor_id' => $this->monitor->id,
                'phone' => $this->monitor->notification_phone,
                'status' => $status
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send SMS alert', [
                'monitor_id' => $this->monitor->id,
                'phone' => $this->monitor->notification_phone,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendEmailAlert(string $status): void
    {
        // TODO: Implement email notifications
        // This would use Laravel's mail system to send email alerts
        Log::info('Email notification would be sent here', [
            'monitor_id' => $this->monitor->id,
            'email' => $this->monitor->notification_email,
            'status' => $status
        ]);
    }

    /**
     * Wrapper for exec() function to avoid namespace issues
     */
    private function execCommand(string $command, array &$output = null, int &$returnCode = null): string
    {
        return \exec($command, $output, $returnCode);
    }

    /**
     * Wrapper for escapeshellarg() function to avoid namespace issues
     */
    private function escapeShellArg(string $arg): string
    {
        return \escapeshellarg($arg);
    }
}
