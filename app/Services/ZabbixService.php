<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class ZabbixService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private ?string $authToken = null;

    public function __construct()
    {
        $this->baseUrl = config('services.zabbix.url');
        $this->username = config('services.zabbix.username');
        $this->password = config('services.zabbix.password');
    }

    public function authenticate(): bool
    {
        try {
            $response = $this->makeRequest('user.login', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if (isset($response['result'])) {
                $this->authToken = $response['result'];
                Cache::put('zabbix_auth_token', $this->authToken, now()->addHours(2));
                return true;
            }

            Log::error('Zabbix authentication failed', ['response' => $response]);
            return false;

        } catch (Exception $e) {
            Log::error('Zabbix authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getAuthToken(): ?string
    {
        if (!$this->authToken) {
            $this->authToken = Cache::get('zabbix_auth_token');
            
            if (!$this->authToken) {
                if ($this->authenticate()) {
                    return $this->authToken;
                }
                return null;
            }
        }

        return $this->authToken;
    }

    public function getHosts(): array
    {
        try {
            $response = $this->makeAuthenticatedRequest('host.get', [
                'output' => ['hostid', 'host', 'name', 'status'],
                'selectInterfaces' => ['type', 'main', 'useip', 'ip', 'port'],
                'selectGroups' => ['groupid', 'name'],
                'monitored_hosts' => true,
            ]);

            return $response['result'] ?? [];

        } catch (Exception $e) {
            Log::error('Failed to fetch Zabbix hosts', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function getHost(string $hostId): ?array
    {
        try {
            $response = $this->makeAuthenticatedRequest('host.get', [
                'output' => ['hostid', 'host', 'name', 'status'],
                'selectInterfaces' => ['type', 'main', 'useip', 'ip', 'port'],
                'selectGroups' => ['groupid', 'name'],
                'hostids' => [$hostId],
            ]);

            return $response['result'][0] ?? null;

        } catch (Exception $e) {
            Log::error('Failed to fetch Zabbix host', ['hostId' => $hostId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function getEvents(string $hostId, int $limit = 50): array
    {
        try {
            $response = $this->makeAuthenticatedRequest('event.get', [
                'output' => ['eventid', 'source', 'object', 'objectid', 'acknowledged', 'clock', 'severity', 'r_eventid', 'value'],
                'select_acknowledges' => ['clock', 'alias', 'message'],
                'selectTriggers' => ['triggerid', 'description', 'priority'],
                'hostids' => [$hostId],
                'source' => 0,
                'object' => 0,
                'sortfield' => 'clock',
                'sortorder' => 'DESC',
                'limit' => $limit,
            ]);

            return $response['result'] ?? [];

        } catch (Exception $e) {
            Log::error('Failed to fetch Zabbix events', ['hostId' => $hostId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function getProblems(string $hostId = null): array
    {
        try {
            $params = [
                'output' => ['eventid', 'source', 'object', 'objectid', 'acknowledged', 'clock', 'severity', 'r_eventid'],
                'selectTriggers' => ['triggerid', 'description', 'priority'],
                'selectHosts' => ['hostid', 'name', 'host'],
                'source' => 0,
                'object' => 0,
                'problem' => true,
                'sortfield' => 'clock',
                'sortorder' => 'DESC',
                'limit' => 100,
            ];

            if ($hostId) {
                $params['hostids'] = [$hostId];
            }

            $response = $this->makeAuthenticatedRequest('problem.get', $params);
            return $response['result'] ?? [];

        } catch (Exception $e) {
            Log::error('Failed to fetch Zabbix problems', ['hostId' => $hostId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function acknowledgeEvent(string $eventId, string $message = 'Acknowledged via monitoring system'): bool
    {
        try {
            $response = $this->makeAuthenticatedRequest('event.acknowledge', [
                'eventids' => [$eventId],
                'action' => 1,
                'message' => $message,
            ]);

            return isset($response['result']['eventids']) && in_array($eventId, $response['result']['eventids']);

        } catch (Exception $e) {
            Log::error('Failed to acknowledge Zabbix event', ['eventId' => $eventId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function testConnection(): array
    {
        try {
            $response = $this->makeRequest('apiinfo.version', []);
            
            if (isset($response['result'])) {
                return [
                    'success' => true,
                    'version' => $response['result'],
                    'message' => 'Successfully connected to Zabbix server',
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get Zabbix version',
                'response' => $response,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    private function makeAuthenticatedRequest(string $method, array $params): array
    {
        $token = $this->getAuthToken();
        
        if (!$token) {
            throw new Exception('Unable to authenticate with Zabbix server');
        }

        return $this->makeRequest($method, $params, $token);
    }

    private function makeRequest(string $method, array $params, string $authToken = null): array
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => rand(1, 99999),
        ];

        if ($authToken) {
            $data['auth'] = $authToken;
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($this->baseUrl . '/api_jsonrpc.php', $data);

        if (!$response->successful()) {
            throw new Exception('HTTP request failed: ' . $response->status());
        }

        $responseData = $response->json();

        if (isset($responseData['error'])) {
            throw new Exception('Zabbix API error: ' . $responseData['error']['data']);
        }

        return $responseData;
    }

    public function logout(): bool
    {
        try {
            if ($this->authToken) {
                $this->makeAuthenticatedRequest('user.logout', []);
                $this->authToken = null;
                Cache::forget('zabbix_auth_token');
            }
            return true;

        } catch (Exception $e) {
            Log::error('Failed to logout from Zabbix', ['error' => $e->getMessage()]);
            return false;
        }
    }
}