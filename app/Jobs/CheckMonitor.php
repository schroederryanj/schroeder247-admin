<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\MonitorResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $startTime = microtime(true);
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

            Log::error('Monitor check failed', [
                'monitor_id' => $this->monitor->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function checkHttp(array &$result, float $startTime): void
    {
        $response = Http::timeout($this->monitor->timeout)
            ->get($this->monitor->url);

        $endTime = microtime(true);
        $result['response_time'] = round(($endTime - $startTime) * 1000);
        $result['status_code'] = $response->status();

        if ($this->monitor->expected_status_code) {
            $statusOk = $response->status() === $this->monitor->expected_status_code;
        } else {
            $statusOk = $response->successful();
        }

        if ($this->monitor->expected_content) {
            $contentOk = str_contains($response->body(), $this->monitor->expected_content);
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
    }

    private function checkPing(array &$result, float $startTime): void
    {
        $host = parse_url($this->monitor->url, PHP_URL_HOST) ?: $this->monitor->url;
        
        $pingResult = exec("ping -n 1 -w " . ($this->monitor->timeout * 1000) . " " . escapeshellarg($host), $output, $returnCode);
        
        $endTime = microtime(true);
        $result['response_time'] = round(($endTime - $startTime) * 1000);

        if ($returnCode === 0) {
            $result['status'] = 'up';
        } else {
            $result['error_message'] = 'Ping failed: host unreachable';
        }
    }

    private function checkTcp(array &$result, float $startTime): void
    {
        $host = parse_url($this->monitor->url, PHP_URL_HOST) ?: $this->monitor->url;
        $port = $this->monitor->port ?: 80;

        $connection = @fsockopen($host, $port, $errno, $errstr, $this->monitor->timeout);
        
        $endTime = microtime(true);
        $result['response_time'] = round(($endTime - $startTime) * 1000);

        if ($connection) {
            $result['status'] = 'up';
            fclose($connection);
        } else {
            $result['error_message'] = "TCP connection failed: $errstr ($errno)";
        }
    }

    private function checkSsl(string $url): bool
    {
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];
        $port = $parsedUrl['port'] ?? 443;

        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ]);

        $socket = @stream_socket_client(
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

        $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'];
        fclose($socket);

        if (!$cert) {
            return false;
        }

        $certInfo = openssl_x509_parse($cert);
        $expiryDate = $certInfo['validTo_time_t'];

        return $expiryDate > time() + (30 * 24 * 60 * 60);
    }
}
