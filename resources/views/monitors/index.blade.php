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

            <!-- Tabs Navigation -->
            <div class="mb-6">
                <nav class="flex space-x-8">
                    <button type="button" onclick="showTab('monitors')" id="monitors-tab" 
                            class="tab-button active py-2 px-1 border-b-2 font-medium text-sm">
                        Custom Monitors ({{ $monitors->count() }})
                    </button>
                    <button type="button" onclick="showTab('zabbix')" id="zabbix-tab" 
                            class="tab-button py-2 px-1 border-b-2 font-medium text-sm">
                        Zabbix Hosts ({{ $zabbixHosts->count() }})
                    </button>
                </nav>
            </div>

            <!-- Custom Monitors Tab -->
            <div id="monitors-content" class="tab-content bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($monitors->isEmpty())
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-6xl mb-4">📊</div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No custom monitors yet</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Get started by adding your first monitor to track uptime.</p>
                            <a href="{{ route('monitors.create') }}" 
                               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Add Your First Monitor
                            </a>
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($monitors as $monitor)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ $monitor->name }}</h3>
                                        <div class="flex items-center space-x-1">
                                            @if($monitor->current_status === 'up')
                                                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                                <span class="text-green-600 text-sm font-medium">UP</span>
                                            @elseif($monitor->current_status === 'down')
                                                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                                                <span class="text-red-600 text-sm font-medium">DOWN</span>
                                            @elseif($monitor->current_status === 'warning')
                                                <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                                                <span class="text-yellow-600 text-sm font-medium">WARNING</span>
                                            @else
                                                <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                                                <span class="text-gray-500 text-sm font-medium">UNKNOWN</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-600 dark:text-gray-300 text-sm mb-2">{{ $monitor->url }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 text-xs mb-3">
                                        Check every {{ $monitor->check_interval }} minutes • 
                                        {{ ucfirst($monitor->type) }}
                                        @if(!$monitor->enabled)
                                            • <span class="text-red-500">Disabled</span>
                                        @endif
                                    </p>
                                    
                                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 mb-3">
                                        <span>Uptime: {{ $monitor->uptime_percentage }}%</span>
                                        <span>Avg: {{ $monitor->average_response_time }}ms</span>
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
                        </div>
                    @endif
                </div>
            </div>

            <!-- Zabbix Hosts Tab -->
            <div id="zabbix-content" class="tab-content hidden bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($zabbixHosts->isEmpty())
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-6xl mb-4">🖥️</div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No Zabbix hosts found</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">
                                Configure your Zabbix server connection and sync hosts to get started.
                            </p>
                            <form action="{{ route('zabbix-hosts.sync') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                    Sync Zabbix Hosts
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($zabbixHosts as $host)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-semibold text-lg text-gray-900 dark:text-gray-100">{{ $host->name }}</h3>
                                        <div class="flex items-center space-x-1">
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
                                                <span class="w-3 h-3 bg-{{ $severityColor }} rounded-full"></span>
                                                <span class="text-{{ $severityColor }} text-sm font-medium">{{ strtoupper($host->severity_level) }}</span>
                                            @elseif($host->status === 'monitored')
                                                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                                <span class="text-green-600 text-sm font-medium">OK</span>
                                            @else
                                                <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                                                <span class="text-gray-500 text-sm font-medium">{{ strtoupper($host->status) }}</span>
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
                                        <div class="mb-3">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">Active Issues:</h4>
                                            @foreach($host->activeEvents->take(2) as $event)
                                                <div class="flex items-center text-xs text-{{ $event->severity_color }} mb-1">
                                                    <span class="mr-1">{{ $event->severity_icon }}</span>
                                                    <span class="truncate">{{ $event->name }}</span>
                                                </div>
                                            @endforeach
                                            @if($host->activeEvents->count() > 2)
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    +{{ $host->activeEvents->count() - 2 }} more...
                                                </p>
                                            @endif
                                        </div>
                                    @endif
                                    
                                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400 mb-3">
                                        <span>
                                            Notifications: 
                                            @if($host->sms_notifications || $host->email_notifications)
                                                <span class="text-green-600">ON</span>
                                            @else
                                                <span class="text-gray-400">OFF</span>
                                            @endif
                                        </span>
                                        <span>
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

            <style>
            .tab-button.active {
                border-color: #3B82F6;
                color: #3B82F6;
            }
            .tab-button:not(.active) {
                border-color: transparent;
                color: #6B7280;
            }
            .tab-button:not(.active):hover {
                color: #374151;
                border-color: #D1D5DB;
            }
            </style>

            <script>
            function showTab(tabName) {
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Remove active class from all tabs
                document.querySelectorAll('.tab-button').forEach(button => {
                    button.classList.remove('active');
                });
                
                // Show selected tab content
                document.getElementById(tabName + '-content').classList.remove('hidden');
                
                // Add active class to selected tab
                document.getElementById(tabName + '-tab').classList.add('active');
            }
            </script>
        </div>
    </div>
</x-app-layout>