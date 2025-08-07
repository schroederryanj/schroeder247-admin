<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class LogsController extends Controller
{
    private const MAX_LOG_SIZE = 10 * 1024 * 1024; // 10MB limit
    private const LINES_PER_PAGE = 100;

    public function index(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            return view('logs.index', [
                'logs' => [],
                'totalLines' => 0,
                'fileSize' => 0,
                'lastModified' => null,
                'currentPage' => 1,
                'totalPages' => 1,
                'filters' => [],
            ]);
        }

        $fileSize = File::size($logPath);
        $lastModified = Carbon::createFromTimestamp(File::lastModified($logPath));
        
        // Get filter parameters
        $level = $request->get('level', 'all');
        $search = $request->get('search', '');
        $date = $request->get('date', '');
        $page = max(1, (int) $request->get('page', 1));
        
        $logs = $this->parseLogFile($logPath, $level, $search, $date, $page);
        
        return view('logs.index', [
            'logs' => $logs['entries'],
            'totalLines' => $logs['totalLines'],
            'fileSize' => $this->formatBytes($fileSize),
            'lastModified' => $lastModified,
            'currentPage' => $page,
            'totalPages' => $logs['totalPages'],
            'filters' => [
                'level' => $level,
                'search' => $search,
                'date' => $date,
            ],
        ]);
    }

    public function download()
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            abort(404, 'Log file not found');
        }

        return response()->download($logPath, 'laravel-' . date('Y-m-d') . '.log');
    }

    public function clear(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        return redirect()->route('logs.index')->with('success', 'Log file cleared successfully.');
    }

    public function api(Request $request): JsonResponse
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!File::exists($logPath)) {
            return response()->json([
                'logs' => [],
                'totalLines' => 0,
                'lastModified' => null,
            ]);
        }

        $level = $request->get('level', 'all');
        $search = $request->get('search', '');
        $date = $request->get('date', '');
        $limit = min(50, max(1, (int) $request->get('limit', 20)));
        
        $logs = $this->parseLogFile($logPath, $level, $search, $date, 1, $limit);
        
        return response()->json([
            'logs' => $logs['entries'],
            'totalLines' => $logs['totalLines'],
            'lastModified' => Carbon::createFromTimestamp(File::lastModified($logPath)),
        ]);
    }

    private function parseLogFile(string $logPath, string $level, string $search, string $date, int $page, int $limit = null): array
    {
        $perPage = $limit ?? self::LINES_PER_PAGE;
        $fileSize = File::size($logPath);
        
        if ($fileSize > self::MAX_LOG_SIZE) {
            return $this->parseLargeLogFile($logPath, $level, $search, $date, $page, $perPage);
        }
        
        $content = File::get($logPath);
        $lines = explode("\n", $content);
        $lines = array_reverse($lines); // Show newest first
        
        $entries = [];
        $currentEntry = null;
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            // Check if this is a new log entry (starts with timestamp)
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)$/', $line, $matches)) {
                // Save previous entry if exists
                if ($currentEntry) {
                    if ($this->matchesFilters($currentEntry, $level, $search, $date)) {
                        $entries[] = $currentEntry;
                    }
                }
                
                // Start new entry
                $currentEntry = [
                    'timestamp' => $matches[1],
                    'environment' => $matches[2],
                    'level' => strtoupper($matches[3]),
                    'message' => $matches[4],
                    'context' => '',
                    'full_line' => $line,
                ];
            } else {
                // This is a continuation of the previous entry
                if ($currentEntry) {
                    $currentEntry['context'] .= $line . "\n";
                    $currentEntry['full_line'] .= "\n" . $line;
                }
            }
        }
        
        // Add last entry
        if ($currentEntry && $this->matchesFilters($currentEntry, $level, $search, $date)) {
            $entries[] = $currentEntry;
        }
        
        $totalEntries = count($entries);
        $totalPages = max(1, ceil($totalEntries / $perPage));
        $offset = ($page - 1) * $perPage;
        $pagedEntries = array_slice($entries, $offset, $perPage);
        
        return [
            'entries' => $pagedEntries,
            'totalLines' => $totalEntries,
            'totalPages' => $totalPages,
        ];
    }

    private function parseLargeLogFile(string $logPath, string $level, string $search, string $date, int $page, int $perPage): array
    {
        // For large files, read only the last portion
        $handle = fopen($logPath, 'r');
        $fileSize = filesize($logPath);
        
        // Read from the end of file
        $readSize = min($fileSize, 1024 * 1024); // Read last 1MB
        fseek($handle, max(0, $fileSize - $readSize));
        
        $content = fread($handle, $readSize);
        fclose($handle);
        
        $lines = explode("\n", $content);
        $lines = array_reverse($lines);
        
        // Process similar to small files but with limited data
        $entries = [];
        $processedLines = 0;
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)$/', $line, $matches)) {
                $entry = [
                    'timestamp' => $matches[1],
                    'environment' => $matches[2], 
                    'level' => strtoupper($matches[3]),
                    'message' => $matches[4],
                    'context' => '',
                    'full_line' => $line,
                ];
                
                if ($this->matchesFilters($entry, $level, $search, $date)) {
                    $entries[] = $entry;
                }
            }
            
            $processedLines++;
            if ($processedLines >= $perPage * 10) break; // Limit processing for performance
        }
        
        $totalEntries = count($entries);
        $totalPages = max(1, ceil($totalEntries / $perPage));
        $offset = ($page - 1) * $perPage;
        $pagedEntries = array_slice($entries, $offset, $perPage);
        
        return [
            'entries' => $pagedEntries,
            'totalLines' => $totalEntries,
            'totalPages' => $totalPages,
        ];
    }

    private function matchesFilters(array $entry, string $level, string $search, string $date): bool
    {
        // Level filter
        if ($level !== 'all' && strtolower($entry['level']) !== strtolower($level)) {
            return false;
        }
        
        // Search filter
        if (!empty($search)) {
            $searchTerm = strtolower($search);
            if (strpos(strtolower($entry['message']), $searchTerm) === false && 
                strpos(strtolower($entry['context']), $searchTerm) === false) {
                return false;
            }
        }
        
        // Date filter
        if (!empty($date)) {
            if (!str_starts_with($entry['timestamp'], $date)) {
                return false;
            }
        }
        
        return true;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' B';
    }
}