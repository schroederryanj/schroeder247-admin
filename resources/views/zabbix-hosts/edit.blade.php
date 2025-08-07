<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit Zabbix Host: ') . $zabbixHost->name }}
            </h2>
            <a href="{{ route('zabbix-hosts.show', $zabbixHost) }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Host
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('zabbix-hosts.update', $zabbixHost) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Host Information (Read-only) -->
                        <div class="border-b pb-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Host Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label :value="__('Host Name')" />
                                    <div class="mt-1 block w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-600 dark:text-gray-300">
                                        {{ $zabbixHost->name }}
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Synced from Zabbix server</p>
                                </div>

                                <div>
                                    <x-input-label :value="__('Host Address')" />
                                    <div class="mt-1 block w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-600 dark:text-gray-300">
                                        {{ $zabbixHost->host }}
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Synced from Zabbix server</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-input-label :value="__('Zabbix Host ID')" />
                                <div class="mt-1 block w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-600 dark:text-gray-300">
                                    {{ $zabbixHost->zabbix_host_id }}
                                </div>
                            </div>

                            @if($zabbixHost->groups)
                                <div class="mt-4">
                                    <x-input-label :value="__('Host Groups')" />
                                    <div class="mt-1">
                                        @foreach(collect($zabbixHost->groups) as $group)
                                            <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm px-3 py-1 rounded-full mr-2 mb-2">
                                                {{ $group['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Notification Settings -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Notification Settings</h3>
                            
                            <div class="space-y-6">
                                <!-- SMS Notifications -->
                                <div class="flex items-start space-x-3">
                                    <div class="flex items-center h-5">
                                        <input id="sms_notifications" name="sms_notifications" type="checkbox" value="1" 
                                               {{ old('sms_notifications', $zabbixHost->sms_notifications) ? 'checked' : '' }}
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    </div>
                                    <div class="flex-1">
                                        <label for="sms_notifications" class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            SMS Notifications
                                        </label>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Send SMS alerts when issues are detected for this host</p>
                                        
                                        <div class="mt-3" id="sms-phone-field" style="display: {{ old('sms_notifications', $zabbixHost->sms_notifications) ? 'block' : 'none' }};">
                                            <x-input-label for="notification_phone" :value="__('Phone Number')" />
                                            <x-text-input id="notification_phone" class="block mt-1 w-full" type="tel" 
                                                         name="notification_phone" :value="old('notification_phone', $zabbixHost->notification_phone)" 
                                                         placeholder="+1234567890" />
                                            <x-input-error :messages="$errors->get('notification_phone')" class="mt-2" />
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Include country code (e.g., +1 for US)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Email Notifications -->
                                <div class="flex items-start space-x-3">
                                    <div class="flex items-center h-5">
                                        <input id="email_notifications" name="email_notifications" type="checkbox" value="1" 
                                               {{ old('email_notifications', $zabbixHost->email_notifications) ? 'checked' : '' }}
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    </div>
                                    <div class="flex-1">
                                        <label for="email_notifications" class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            Email Notifications
                                        </label>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Send email alerts when issues are detected for this host</p>
                                        
                                        <div class="mt-3" id="email-field" style="display: {{ old('email_notifications', $zabbixHost->email_notifications) ? 'block' : 'none' }};">
                                            <x-input-label for="notification_email" :value="__('Email Address')" />
                                            <x-text-input id="notification_email" class="block mt-1 w-full" type="email" 
                                                         name="notification_email" :value="old('notification_email', $zabbixHost->notification_email)" 
                                                         placeholder="you@example.com" />
                                            <x-input-error :messages="$errors->get('notification_email')" class="mt-2" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alert Severity Settings -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Alert Severity Settings</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Select which severity levels should trigger notifications</p>
                            
                            <div class="space-y-3">
                                @foreach(['disaster', 'high', 'average', 'warning', 'information'] as $severity)
                                    @php
                                        $severitySettings = old('severity_settings', $zabbixHost->severity_settings ?? []);
                                        $isChecked = $severitySettings[$severity] ?? ($severity === 'disaster' || $severity === 'high');
                                        $severityColor = match($severity) {
                                            'disaster' => 'red-600',
                                            'high' => 'red-500',
                                            'average' => 'orange-500',
                                            'warning' => 'yellow-500',
                                            'information' => 'blue-500',
                                        };
                                    @endphp
                                    <div class="flex items-center space-x-3">
                                        <input id="severity_{{ $severity }}" name="severity_settings[{{ $severity }}]" 
                                               type="checkbox" value="1" {{ $isChecked ? 'checked' : '' }}
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <div class="flex items-center space-x-2">
                                            <span class="w-3 h-3 bg-{{ $severityColor }} rounded-full"></span>
                                            <label for="severity_{{ $severity }}" class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ ucfirst($severity) }}
                                            </label>
                                        </div>
                                        @if($severity === 'disaster')
                                            <span class="text-xs text-gray-500 dark:text-gray-400">(Critical system failures)</span>
                                        @elseif($severity === 'high')
                                            <span class="text-xs text-gray-500 dark:text-gray-400">(Important issues requiring immediate attention)</span>
                                        @elseif($severity === 'average')
                                            <span class="text-xs text-gray-500 dark:text-gray-400">(Moderate issues)</span>
                                        @elseif($severity === 'warning')
                                            <span class="text-xs text-gray-500 dark:text-gray-400">(Minor issues)</span>
                                        @elseif($severity === 'information')
                                            <span class="text-xs text-gray-500 dark:text-gray-400">(Informational alerts)</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Current Status (Read-only) -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Current Status</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label :value="__('Status')" />
                                    <div class="mt-1 flex items-center space-x-2">
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

                                <div>
                                    <x-input-label :value="__('Active Issues')" />
                                    <div class="mt-1 text-gray-900 dark:text-gray-100">
                                        {{ $zabbixHost->activeEvents->count() }} issues
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-input-label :value="__('Last Synced')" />
                                <div class="mt-1 text-gray-600 dark:text-gray-300 text-sm">
                                    @if($zabbixHost->last_synced_at)
                                        {{ $zabbixHost->last_synced_at->format('M j, Y g:i A') }} 
                                        ({{ $zabbixHost->last_synced_at->diffForHumans() }})
                                    @else
                                        Never synced
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('zabbix-hosts.show', $zabbixHost) }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Update Settings') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const smsCheckbox = document.getElementById('sms_notifications');
            const emailCheckbox = document.getElementById('email_notifications');
            const smsPhoneField = document.getElementById('sms-phone-field');
            const emailField = document.getElementById('email-field');
            
            function toggleNotificationFields() {
                // Show/hide SMS phone field
                if (smsCheckbox.checked) {
                    smsPhoneField.style.display = 'block';
                } else {
                    smsPhoneField.style.display = 'none';
                }
                
                // Show/hide email field
                if (emailCheckbox.checked) {
                    emailField.style.display = 'block';
                } else {
                    emailField.style.display = 'none';
                }
            }
            
            smsCheckbox.addEventListener('change', toggleNotificationFields);
            emailCheckbox.addEventListener('change', toggleNotificationFields);
            
            toggleNotificationFields(); // Initial call
        });
    </script>
</x-app-layout>