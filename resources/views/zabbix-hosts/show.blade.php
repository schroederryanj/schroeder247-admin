<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $zabbixHost->name }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $zabbixHost->host }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('zabbix-hosts.edit', $zabbixHost) }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Edit Settings
                </a>
                <a href="{{ route('zabbix-hosts.index') }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to List
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

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Status Overview -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Host Status</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Current Status</span>
                                <div class="flex items-center space-x-2">
                                    @if($zabbixHost->is_down)
                                        @php
                                            $severityColor = match ($zabbixHost->severity_level) {
                                                'disaster' => 'red-600',
                                                'high' => 'red-500',
                                                'average' => 'orange-500',
                                                'warning' => 'yellow-500',
                                                'information' => 'blue-500',
                                                default => 'gray-500',
                                            };
                                        @endphp
                                        <span class="w-3 h-3 bg-{{ $severityColor }} rounded-full"></span>
                                        <span class="text-{{ $severityColor }} font-medium">{{ strtoupper($zabbixHost->severity_level) }}</span>
                                    @elseif($zabbixHost->status === 'monitored')
                                        <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                        <span class="text-green-600 font-medium">OK</span>
                                    @else
                                        <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                                        <span class="text-gray-500 font-medium">{{ strtoupper($zabbixHost->status) }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Host ID</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $zabbixHost->zabbix_host_id }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Host Address</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ $zabbixHost->host }}</span>
                            </div>

                            @if($zabbixHost->groups)
                                <div class="flex items-start justify-between">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Groups</span>
                                    <div class="text-right">
                                        @foreach(collect($zabbixHost->groups) as $group)
                                            <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs px-2 py-1 rounded mb-1 ml-1">
                                                {{ $group['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Synced</span>
                                <span class="text-sm text-gray-900 dark:text-gray-100">
                                    @if($zabbixHost->last_synced_at)
                                        {{ $zabbixHost->last_synced_at->format('M j, Y g:i A') }}
                                    @else
                                        Never synced
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Notification Settings</h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">SMS Notifications</span>
                                <div class="flex items-center space-x-2">
                                    @if($zabbixHost->sms_notifications)
                                        <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                        <span class="text-green-600 font-medium">ENABLED</span>
                                    @else
                                        <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                                        <span class="text-gray-500 font-medium">DISABLED</span>
                                    @endif
                                </div>
                            </div>

                            @if($zabbixHost->sms_notifications && $zabbixHost->notification_phone)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Phone Number</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ $zabbixHost->notification_phone }}</span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Email Notifications</span>
                                <div class="flex items-center space-x-2">
                                    @if($zabbixHost->email_notifications)
                                        <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                        <span class="text-green-600 font-medium">ENABLED</span>
                                    @else
                                        <span class="w-3 h-3 bg-gray-400 rounded-full"></span>
                                        <span class="text-gray-500 font-medium">DISABLED</span>
                                    @endif
                                </div>
                            </div>

                            @if($zabbixHost->email_notifications && $zabbixHost->notification_email)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Email Address</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ $zabbixHost->notification_email }}</span>
                                </div>
                            @endif

                            <div class="border-t pt-4 mt-4">
                                <div class="flex items-start justify-between">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Alert Severities</span>
                                    <div class="text-right">
                                        @php
                                            $severitySettings = $zabbixHost->severity_settings ?? [];
                                            $enabledSeverities = [];
                                            foreach(['disaster', 'high', 'average', 'warning', 'information'] as $severity) {
                                                if (($severitySettings[$severity] ?? ($severity === 'disaster' || $severity === 'high'))) {
                                                    $enabledSeverities[] = $severity;
                                                }
                                            }
                                        @endphp
                                        
                                        @if(count($enabledSeverities) > 0)
                                            <div class="flex flex-wrap gap-1 justify-end">
                                                @foreach($enabledSeverities as $severity)
                                                    @php
                                                        $severityColor = match($severity) {
                                                            'disaster' => 'red-600',
                                                            'high' => 'red-500',
                                                            'average' => 'orange-500',
                                                            'warning' => 'yellow-500',
                                                            'information' => 'blue-500',
                                                        };
                                                    @endphp
                                                    <span class="inline-flex items-center space-x-1 bg-{{ $severityColor }}/10 text-{{ $severityColor }} text-xs px-2 py-1 rounded-full">
                                                        <span class="w-2 h-2 bg-{{ $severityColor }} rounded-full"></span>
                                                        <span>{{ ucfirst($severity) }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400">None configured</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Events/Issues -->
            @if($zabbixHost->activeEvents->count() > 0)
                <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Active Issues ({{ $zabbixHost->activeEvents->count() }})
                        </h3>
                        
                        <div class="space-y-3">
                            @foreach($zabbixHost->activeEvents as $event)
                                <div class="border-l-4 border-{{ $event->severity_color }}-500 pl-4 py-2">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-lg">{{ $event->severity_icon }}</span>
                                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $event->name }}</span>
                                            </div>
                                            @if($event->description)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $event->description }}</p>
                                            @endif
                                            <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                <span>Severity: {{ ucfirst($event->severity) }}</span>
                                                <span>Event ID: {{ $event->zabbix_event_id }}</span>
                                                <span>Started: {{ $event->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                        <span class="text-{{ $event->severity_color }}-600 text-sm font-medium">
                                            {{ strtoupper($event->severity) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Event History -->
            @if($zabbixHost->events->count() > 0)
                <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Event History ({{ $zabbixHost->events->count() }})
                        </h3>
                        
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @foreach($zabbixHost->events as $event)
                                <div class="border-l-4 border-{{ $event->severity_color }}-500 pl-4 py-2 bg-gray-50 dark:bg-gray-700 rounded-r">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-lg">{{ $event->severity_icon }}</span>
                                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $event->name }}</span>
                                                @if($event->status === 'problem')
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                                        ACTIVE
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                        RESOLVED
                                                    </span>
                                                @endif
                                            </div>
                                            @if($event->description)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $event->description }}</p>
                                            @endif
                                            <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                <span>Severity: {{ ucfirst($event->severity) }}</span>
                                                <span>Event ID: {{ $event->zabbix_event_id }}</span>
                                                <span>Started: {{ $event->event_time->format('M j, Y H:i') }}</span>
                                                @if($event->recovery_time)
                                                    <span>Resolved: {{ $event->recovery_time->format('M j, Y H:i') }}</span>
                                                    <span>Duration: {{ $event->event_time->diffForHumans($event->recovery_time, true) }}</span>
                                                @elseif($event->status === 'problem')
                                                    <span>Duration: {{ $event->event_time->diffForHumans() }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-{{ $event->severity_color }}-600 text-sm font-medium">
                                            {{ strtoupper($event->severity) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Interface Information -->
            @if($zabbixHost->interfaces)
                <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Host Interfaces</h3>
                        
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach(collect($zabbixHost->interfaces) as $interface)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ ucfirst($interface['type'] ?? 'Unknown') }} Interface
                                        </span>
                                        <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-2 py-1 rounded">
                                            {{ $interface['main'] ? 'Primary' : 'Secondary' }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                        <div>IP: {{ $interface['ip'] ?? 'N/A' }}</div>
                                        @if(isset($interface['port']))
                                            <div>Port: {{ $interface['port'] }}</div>
                                        @endif
                                        @if(isset($interface['dns']) && !empty($interface['dns']))
                                            <div>DNS: {{ $interface['dns'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>