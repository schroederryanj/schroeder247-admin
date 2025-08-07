<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Application Logs') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('logs.download') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Download Logs
                </a>
                <form action="{{ route('logs.clear') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            onclick="return confirm('Are you sure you want to clear all logs? This cannot be undone.')"
                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        Clear Logs
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Log File Info -->
            <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Log File Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="font-medium text-gray-700 dark:text-gray-300">Total Entries</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalLines) }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="font-medium text-gray-700 dark:text-gray-300">File Size</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $fileSize }}</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                            <div class="font-medium text-gray-700 dark:text-gray-300">Last Modified</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                {{ $lastModified ? $lastModified->format('M j, Y H:i:s') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Filters</h3>
                    <form method="GET" action="{{ route('logs.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Level Filter -->
                            <div>
                                <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Log Level</label>
                                <select name="level" id="level" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <option value="all" {{ $filters['level'] === 'all' ? 'selected' : '' }}>All Levels</option>
                                    <option value="emergency" {{ $filters['level'] === 'emergency' ? 'selected' : '' }}>Emergency</option>
                                    <option value="alert" {{ $filters['level'] === 'alert' ? 'selected' : '' }}>Alert</option>
                                    <option value="critical" {{ $filters['level'] === 'critical' ? 'selected' : '' }}>Critical</option>
                                    <option value="error" {{ $filters['level'] === 'error' ? 'selected' : '' }}>Error</option>
                                    <option value="warning" {{ $filters['level'] === 'warning' ? 'selected' : '' }}>Warning</option>
                                    <option value="notice" {{ $filters['level'] === 'notice' ? 'selected' : '' }}>Notice</option>
                                    <option value="info" {{ $filters['level'] === 'info' ? 'selected' : '' }}>Info</option>
                                    <option value="debug" {{ $filters['level'] === 'debug' ? 'selected' : '' }}>Debug</option>
                                </select>
                            </div>

                            <!-- Search -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                                <input type="text" name="search" id="search" value="{{ $filters['search'] }}" 
                                       placeholder="Search in messages..."
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            </div>

                            <!-- Date Filter -->
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date</label>
                                <input type="date" name="date" id="date" value="{{ $filters['date'] }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-end space-x-2">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Filter
                                </button>
                                <a href="{{ route('logs.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs Display -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Log Entries</h3>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Page {{ $currentPage }} of {{ $totalPages }}
                        </div>
                    </div>

                    @if(empty($logs))
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-6xl mb-4">📋</div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No log entries found</h3>
                            <p class="text-gray-500 dark:text-gray-400">
                                @if(!empty($filters['search']) || !empty($filters['date']) || $filters['level'] !== 'all')
                                    Try adjusting your filters or clear them to see all entries.
                                @else
                                    The log file is empty or doesn't exist yet.
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($logs as $log)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 cursor-pointer" 
                                         onclick="toggleLogEntry('log-{{ $loop->index }}')">
                                        <div class="flex items-center space-x-3">
                                            <!-- Level Badge -->
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @switch($log['level'])
                                                    @case('ERROR')
                                                        bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                        @break
                                                    @case('WARNING')
                                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                                        @break
                                                    @case('INFO')
                                                        bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                                        @break
                                                    @case('DEBUG')
                                                        bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                                        @break
                                                    @case('CRITICAL')
                                                    @case('EMERGENCY')
                                                    @case('ALERT')
                                                        bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                                        @break
                                                    @default
                                                        bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                                @endswitch">
                                                {{ $log['level'] }}
                                            </span>
                                            
                                            <!-- Timestamp -->
                                            <span class="text-sm text-gray-600 dark:text-gray-400 font-mono">
                                                {{ $log['timestamp'] }}
                                            </span>
                                        </div>
                                        
                                        <div class="text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    
                                    <div id="log-{{ $loop->index }}" class="hidden p-4 bg-white dark:bg-gray-800">
                                        <!-- Message -->
                                        <div class="mb-3">
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message:</h4>
                                            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded font-mono text-sm break-all">
                                                {{ $log['message'] }}
                                            </div>
                                        </div>
                                        
                                        @if(!empty(trim($log['context'])))
                                            <!-- Context -->
                                            <div class="mb-3">
                                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Context:</h4>
                                                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded font-mono text-sm max-h-40 overflow-y-auto">
                                                    <pre class="whitespace-pre-wrap">{{ trim($log['context']) }}</pre>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <!-- Full Entry -->
                                        <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full Entry:</h4>
                                            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded font-mono text-xs max-h-60 overflow-y-auto">
                                                <pre class="whitespace-pre-wrap">{{ $log['full_line'] }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        @if($totalPages > 1)
                            <div class="mt-6 flex justify-between items-center">
                                <div class="text-sm text-gray-700 dark:text-gray-300">
                                    Showing page {{ $currentPage }} of {{ $totalPages }}
                                </div>
                                <div class="flex space-x-2">
                                    @if($currentPage > 1)
                                        <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage - 1]) }}" 
                                           class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                            Previous
                                        </a>
                                    @endif
                                    
                                    @if($currentPage < $totalPages)
                                        <a href="{{ request()->fullUrlWithQuery(['page' => $currentPage + 1]) }}" 
                                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                            Next
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleLogEntry(id) {
            const element = document.getElementById(id);
            const isHidden = element.classList.contains('hidden');
            
            // Close all other entries
            document.querySelectorAll('[id^="log-"]').forEach(el => {
                el.classList.add('hidden');
            });
            
            // Toggle current entry
            if (isHidden) {
                element.classList.remove('hidden');
            }
        }

        // Auto-refresh option
        function enableAutoRefresh() {
            setInterval(function() {
                // Check if we have any filters applied
                const hasFilters = new URLSearchParams(window.location.search).toString();
                if (!hasFilters) {
                    window.location.reload();
                }
            }, 30000); // Refresh every 30 seconds if no filters
        }

        // Uncomment to enable auto-refresh
        // enableAutoRefresh();
    </script>
</x-app-layout>