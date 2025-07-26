<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $monitor->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('monitors.edit', $monitor) }}" 
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
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
            <!-- Monitor Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
                                    <span class="text-gray-500 font-semibold text-lg">UNKNOWN</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500">Current Status</p>
                        </div>

                        <!-- Uptime Card -->
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $monitor->uptime_percentage }}%</p>
                            <p class="text-sm text-gray-500">Uptime</p>
                        </div>

                        <!-- Response Time Card -->
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-900">{{ $monitor->average_response_time }}ms</p>
                            <p class="text-sm text-gray-500">Avg Response</p>
                        </div>

                        <!-- Last Check Card -->
                        <div class="text-center">
                            <p class="text-lg font-semibold text-gray-900">
                                {{ $monitor->last_checked_at ? $monitor->last_checked_at->format('H:i') : 'Never' }}
                            </p>
                            <p class="text-sm text-gray-500">Last Checked</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitor Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Monitor Configuration</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">URL</dt>
                                    <dd class="text-sm text-gray-900 break-all">{{ $monitor->url }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                                    <dd class="text-sm text-gray-900">{{ ucfirst($monitor->type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Check Interval</dt>
                                    <dd class="text-sm text-gray-900">{{ $monitor->check_interval }} minutes</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Timeout</dt>
                                    <dd class="text-sm text-gray-900">{{ $monitor->timeout }} seconds</dd>
                                </div>
                            </dl>
                        </div>
                        <div>
                            <dl class="space-y-3">
                                @if($monitor->port)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Port</dt>
                                        <dd class="text-sm text-gray-900">{{ $monitor->port }}</dd>
                                    </div>
                                @endif
                                @if($monitor->expected_status_code)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Expected Status Code</dt>
                                        <dd class="text-sm text-gray-900">{{ $monitor->expected_status_code }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">SSL Check</dt>
                                    <dd class="text-sm text-gray-900">{{ $monitor->ssl_check ? 'Enabled' : 'Disabled' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="text-sm text-gray-900">
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
                            <dt class="text-sm font-medium text-gray-500 mb-2">Expected Content</dt>
                            <dd class="text-sm text-gray-900 bg-gray-50 p-3 rounded">{{ $monitor->expected_content }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Check Results -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Check Results</h3>
                    
                    @if($recentResults->isEmpty())
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-4xl mb-2">📊</div>
                            <p class="text-gray-500">No check results yet. Monitoring will begin shortly.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Response Time
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status Code
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Checked At
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Error
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
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
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $result->response_time ? $result->response_time . 'ms' : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $result->status_code ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
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