<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Monitoring Dashboard') }}
            </h2>
            <div class="flex space-x-2">
                <form action="{{ route('monitors.check-all') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Check All Monitors
                    </button>
                </form>
                <form action="{{ route('zabbix-hosts.sync') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                        Sync Zabbix Hosts
                    </button>
                </form>
                <a href="{{ route('monitors.create') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Add Monitor
                </a>
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

            @if(session('warning'))
                <div class="mb-4 bg-yellow-100 dark:bg-yellow-900 border border-yellow-400 dark:border-yellow-600 text-yellow-700 dark:text-yellow-300 px-4 py-3 rounded">
                    {{ session('warning') }}
                </div>
            @endif

            <!-- Summary Stats -->
            <div class="mb-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $monitors->count() }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Custom Monitors</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ $zabbixHosts->count() }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Zabbix Hosts</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">
                            {{ $monitors->where('current_status', 'up')->count() + $zabbixHosts->where('status', 'monitored')->filter(fn($h) => !$h->is_down)->count() }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">All OK</div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">
                            {{ $monitors->whereIn('current_status', ['down', 'warning'])->count() + $zabbixHosts->filter(fn($h) => $h->is_down)->count() }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Issues</div>
                    </div>
                </div>
            </div>

            <!-- Unified Monitoring Grid -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($monitors->isEmpty() && $zabbixHosts->isEmpty())
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-6xl mb-4">📊</div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No monitoring configured</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Get started by adding custom monitors or syncing Zabbix hosts.</p>
                            <div class="flex justify-center space-x-4">
                                <a href="{{ route('monitors.create') }}" 
                                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Add Custom Monitor
                                </a>
                                <form action="{{ route('zabbix-hosts.sync') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                        Sync Zabbix Hosts
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            <!-- Custom Monitors -->
                            @foreach($monitors as $monitor)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-5 border border-gray-200 dark:border-gray-600">
                                    <!-- Header with Tag and Status -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 truncate">{{ $monitor->name }}</h3>
                                                <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap">
                                                    CUSTOM
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 ml-3">
                                            @if($monitor->current_status === 'up')
                                                <div class="w-2.5 h-2.5 bg-green-500 rounded-full"></div>
                                                <span class="text-green-600 text-sm font-medium whitespace-nowrap">UP</span>
                                            @elseif($monitor->current_status === 'down')
                                                <div class="w-2.5 h-2.5 bg-red-500 rounded-full"></div>
                                                <span class="text-red-600 text-sm font-medium whitespace-nowrap">DOWN</span>
                                            @elseif($monitor->current_status === 'warning')
                                                <div class="w-2.5 h-2.5 bg-yellow-500 rounded-full"></div>
                                                <span class="text-yellow-600 text-sm font-medium whitespace-nowrap">WARNING</span>
                                            @else
                                                <div class="w-2.5 h-2.5 bg-gray-400 rounded-full"></div>
                                                <span class="text-gray-500 text-sm font-medium whitespace-nowrap">UNKNOWN</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Details -->
                                    <div class="mb-3">
                                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-1 truncate">{{ $monitor->url }}</p>
                                        <p class="text-gray-500 dark:text-gray-400 text-xs">
                                            Check every {{ $monitor->check_interval }} minutes • {{ ucfirst($monitor->type) }}
                                            @if(!$monitor->enabled)
                                                • <span class="text-red-500 font-medium">Disabled</span>
                                            @endif
                                        </p>
                                    </div>
                                    
                                    <!-- Metrics -->
                                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        <div class="flex items-center gap-1">
                                            <span>Uptime:</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $monitor->uptime_percentage }}%</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span>Avg:</span>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $monitor->average_response_time }}ms</span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('monitors.show', $monitor) }}" 
                                           class="flex-1 bg-blue-500 hover:bg-blue-700 text-white text-center py-1 px-2 rounded text-sm">
                                            View
                                        </a>
                                        <a href="{{ route('monitors.edit', $monitor) }}" 
                                           class="flex-1 bg-gray-500 hover:bg-gray-700 text-white text-center py-1 px-2 rounded text-sm">
                                            Edit
                                        </a>
                                        <form action="{{ route('monitors.destroy', $monitor) }}" method="POST" class="flex-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    onclick="return confirm('Are you sure you want to delete this monitor?')"
                                                    class="w-full bg-red-500 hover:bg-red-700 text-white py-1 px-2 rounded text-sm">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Zabbix Hosts -->
                            @foreach($zabbixHosts as $host)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-5 border border-gray-200 dark:border-gray-600">
                                    <!-- Header with Tag and Status -->
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100 truncate">{{ $host->name }}</h3>
                                                <span class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap">
                                                    ZABBIX
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5 ml-3">
                                            @if($host->is_down)
                                                @php
                                                    $severityColor = match ($host->severity_level) {
                                                        'disaster' => 'red-600',
                                                        'high' => 'red-500',
                                                        'average' => 'orange-500',
                                                        'warning' => 'yellow-500',
                                                        'information' => 'blue-500',
                                                        default => 'gray-500',
                                                    };
                                                @endphp
                                                <div class="w-2.5 h-2.5 bg-{{ $severityColor }} rounded-full"></div>
                                                <span class="text-{{ $severityColor }} text-sm font-medium whitespace-nowrap">{{ strtoupper($host->severity_level) }}</span>
                                            @elseif($host->status === 'monitored')
                                                <div class="w-2.5 h-2.5 bg-green-500 rounded-full"></div>
                                                <span class="text-green-600 text-sm font-medium whitespace-nowrap">OK</span>
                                            @else
                                                <div class="w-2.5 h-2.5 bg-gray-400 rounded-full"></div>
                                                <span class="text-gray-500 text-sm font-medium whitespace-nowrap">{{ strtoupper($host->status) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-2">{{ $host->host }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs mb-3">
                                        Host ID: {{ $host->zabbix_host_id }}
                                        @if($host->groups)
                                            • Groups: {{ collect($host->groups)->pluck('name')->implode(', ') }}
                                        @endif
                                    </p>
                                    
                                    @if($host->activeEvents->count() > 0)
                                        <div class="mb-4 p-3 bg-red-50 dark:bg-red-950 border border-red-200 dark:border-red-800 rounded-lg">
                                            <h4 class="text-sm font-medium text-red-900 dark:text-red-100 mb-2 flex items-center gap-1">
                                                <span class="text-red-500">⚠️</span>
                                                Active Issues ({{ $host->activeEvents->count() }})
                                            </h4>
                                            @foreach($host->activeEvents->take(2) as $event)
                                                <div class="flex items-center text-xs mb-1.5 last:mb-0">
                                                    <span class="mr-2 text-base">{{ $event->severity_icon }}</span>
                                                    <span class="text-gray-800 dark:text-gray-200 truncate">{{ $event->name }}</span>
                                                </div>
                                            @endforeach
                                            @if($host->activeEvents->count() > 2)
                                                <p class="text-xs text-red-600 dark:text-red-400 mt-2 font-medium">
                                                    +{{ $host->activeEvents->count() - 2 }} more issues
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <!-- Info Row -->
                                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 mb-4">
                                        <div class="flex items-center gap-1">
                                            <span>Notifications:</span>
                                            @if($host->sms_notifications || $host->email_notifications)
                                                <div class="w-1.5 h-1.5 bg-green-500 rounded-full"></div>
                                                <span class="text-green-600 font-medium">ON</span>
                                            @else
                                                <div class="w-1.5 h-1.5 bg-gray-400 rounded-full"></div>
                                                <span class="text-gray-400">OFF</span>
                                            @endif
                                        </div>
                                        <span class="text-right">
                                            @if($host->last_synced_at)
                                                Synced {{ $host->last_synced_at->diffForHumans() }}
                                            @else
                                                Never synced
                                            @endif
                                        </span>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <a href="{{ route('zabbix-hosts.show', $host) }}" 
                                           class="flex-1 bg-blue-500 hover:bg-blue-700 text-white text-center py-1 px-2 rounded text-sm">
                                            View
                                        </a>
                                        <a href="{{ route('zabbix-hosts.edit', $host) }}" 
                                           class="flex-1 bg-gray-500 hover:bg-gray-700 text-white text-center py-1 px-2 rounded text-sm">
                                            Settings
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>