<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $monitor->name }}
            </h2>
            <div class="flex space-x-2">
                <form action="{{ route('monitors.check-all') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Check All Monitors
                    </button>
                </form>
                <a href="{{ route('monitors.edit', $monitor) }}" 
                   class="bg-gray-50 dark:bg-gray-7000 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Edit
                </a>
                <a href="{{ route('monitors.index') }}" 
                   class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Back to Monitors
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="bg-yellow-100 dark:bg-yellow-900 border border-yellow-400 dark:border-yellow-600 text-yellow-700 dark:text-yellow-300 px-4 py-3 rounded">
                    {{ session('warning') }}
                </div>
            @endif
            <!-- Monitor Overview -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Status Card -->
                        <div class="text-center">
                            <div class="flex items-center justify-center mb-2">
                                @if($monitor->current_status === 'up')
                                    <span class="w-4 h-4 bg-green-500 rounded-full mr-2"></span>
                                    <span class="text-green-600 font-semibold text-lg">UP</span>
                                @elseif($monitor->current_status === 'down')
                                    <span class="w-4 h-4 bg-red-500 rounded-full mr-2"></span>
                                    <span class="text-red-600 font-semibold text-lg">DOWN</span>
                                @elseif($monitor->current_status === 'warning')
                                    <span class="w-4 h-4 bg-yellow-500 rounded-full mr-2"></span>
                                    <span class="text-yellow-600 font-semibold text-lg">WARNING</span>
                                @else
                                    <span class="w-4 h-4 bg-gray-400 rounded-full mr-2"></span>
                                    <span class="text-gray-500 dark:text-gray-400 font-semibold text-lg">UNKNOWN</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Current Status</p>
                        </div>

                        <!-- Uptime Card -->
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 dark:text-gray-100">{{ $monitor->uptime_percentage }}%</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Uptime</p>
                        </div>

                        <!-- Response Time Card -->
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 dark:text-gray-100">{{ $monitor->average_response_time }}ms</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Avg Response</p>
                        </div>

                        <!-- Last Check Card -->
                        <div class="text-center">
                            <p class="text-lg font-semibold text-gray-900 dark:text-gray-100 dark:text-gray-100">
                                {{ $monitor->last_checked_at ? $monitor->last_checked_at->format('H:i') : 'Never' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400">Last Checked</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitor Details -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 dark:text-gray-100 mb-4">Monitor Configuration</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">URL</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100 dark:text-gray-100 break-all">{{ $monitor->url }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 dark:text-gray-400">Type</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100 dark:text-gray-100">{{ ucfirst($monitor->type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Check Interval</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $monitor->check_interval }} minutes</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Timeout</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $monitor->timeout }} seconds</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <dl class="space-y-3">
                                @if($monitor->port)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Port</dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $monitor->port }}</dd>
                                    </div>
                                @endif
                                @if($monitor->expected_status_code)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Expected Status Code</dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $monitor->expected_status_code }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SSL Check</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $monitor->ssl_check ? 'Enabled' : 'Disabled' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                                    <dd class="text-sm text-gray-900 dark:text-gray-100">
                                        <span class="{{ $monitor->enabled ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $monitor->enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    
                    @if($monitor->expected_content)
                        <div class="mt-6">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Expected Content</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 p-3 rounded">{{ $monitor->expected_content }}</dd>
                        </div>
                    @endif

                    <!-- Notification Settings -->
                    <div class="mt-6 border-t pt-6">
                        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">Notification Settings</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SMS Alerts</dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                                            @if($monitor->sms_notifications)
                                                <span class="text-green-600">✓ Enabled</span>
                                                @if($monitor->notification_phone)
                                                    <br><span class="text-xs text-gray-500 dark:text-gray-400">{{ $monitor->notification_phone }}</span>
                                                @endif
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400">Disabled</span>
                                            @endif
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email Alerts</dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                                            @if($monitor->email_notifications)
                                                <span class="text-green-600">✓ Enabled</span>
                                                @if($monitor->notification_email)
                                                    <br><span class="text-xs text-gray-500 dark:text-gray-400">{{ $monitor->notification_email }}</span>
                                                @endif
                                            @else
                                                <span class="text-gray-500 dark:text-gray-400">Disabled</span>
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                            <div>
                                <dl class="space-y-3">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Alert Threshold</dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $monitor->notification_threshold }} failed check{{ $monitor->notification_threshold > 1 ? 's' : '' }}</dd>
                                    </div>
                                    @if($monitor->last_notification_sent)
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Alert Sent</dt>
                                            <dd class="text-sm text-gray-900 dark:text-gray-100">{{ $monitor->last_notification_sent->format('M j, Y H:i') }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Check Results -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Check Results</h3>
                    
                    @if($recentResults->isEmpty())
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-4xl mb-2">📊</div>
                            <p class="text-gray-500 dark:text-gray-400">No check results yet. Monitoring will begin shortly.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Response Time
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Status Code
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Checked At
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Error
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($recentResults as $result)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    @if($result->status === 'up')
                                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                                        <span class="text-green-600 text-sm font-medium">UP</span>
                                                    @elseif($result->status === 'down')
                                                        <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                                                        <span class="text-red-600 text-sm font-medium">DOWN</span>
                                                    @else
                                                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                                                        <span class="text-yellow-600 text-sm font-medium">WARNING</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $result->response_time ? $result->response_time . 'ms' : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                {{ $result->status_code ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ $result->checked_at->format('M d, Y H:i:s') }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-red-600 max-w-xs truncate">
                                                {{ $result->error_message ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>