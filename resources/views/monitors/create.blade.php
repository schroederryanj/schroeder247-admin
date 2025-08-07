<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Add New Monitor') }}
            </h2>
            <a href="{{ route('monitors.index') }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Monitors
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('monitors.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Monitor Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" 
                                         :value="old('name')" required autofocus placeholder="My Website" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="url" :value="__('URL or IP Address')" />
                            <x-text-input id="url" class="block mt-1 w-full" type="text" name="url" 
                                         :value="old('url')" required placeholder="https://example.com or 192.168.1.1" />
                            <x-input-error :messages="$errors->get('url')" class="mt-2" />
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Enter the full URL (e.g., https://example.com) or IP address (e.g., 192.168.1.1)</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="type" :value="__('Monitor Type')" />
                                <select id="type" name="type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="http" {{ old('type') === 'http' ? 'selected' : '' }}>HTTP</option>
                                    <option value="https" {{ old('type') === 'https' ? 'selected' : '' }}>HTTPS</option>
                                    <option value="ping" {{ old('type') === 'ping' ? 'selected' : '' }}>Ping</option>
                                    <option value="tcp" {{ old('type') === 'tcp' ? 'selected' : '' }}>TCP Port</option>
                                </select>
                                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="check_interval" :value="__('Check Interval (minutes)')" />
                                <x-text-input id="check_interval" class="block mt-1 w-full" type="number" 
                                             name="check_interval" :value="old('check_interval', 5)" 
                                             required min="1" max="1440" />
                                <x-input-error :messages="$errors->get('check_interval')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="timeout" :value="__('Timeout (seconds)')" />
                                <x-text-input id="timeout" class="block mt-1 w-full" type="number" 
                                             name="timeout" :value="old('timeout', 30)" 
                                             required min="5" max="60" />
                                <x-input-error :messages="$errors->get('timeout')" class="mt-2" />
                            </div>

                            <div id="port-field" class="hidden">
                                <x-input-label for="port" :value="__('Port Number')" />
                                <x-text-input id="port" class="block mt-1 w-full" type="number" 
                                             name="port" :value="old('port')" 
                                             min="1" max="65535" placeholder="80" />
                                <x-input-error :messages="$errors->get('port')" class="mt-2" />
                            </div>
                        </div>

                        <div id="http-fields">
                            <div class="mb-4">
                                <x-input-label for="expected_status_code" :value="__('Expected Status Code (optional)')" />
                                <x-text-input id="expected_status_code" class="block mt-1 w-full" type="number" 
                                             name="expected_status_code" :value="old('expected_status_code', 200)" 
                                             min="100" max="599" placeholder="200" />
                                <x-input-error :messages="$errors->get('expected_status_code')" class="mt-2" />
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Leave blank to accept any 2xx status code</p>
                            </div>

                            <div class="mb-4">
                                <x-input-label for="expected_content" :value="__('Expected Content (optional)')" />
                                <textarea id="expected_content" name="expected_content" rows="3" 
                                         class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                         placeholder="Text that should be present on the page">{{ old('expected_content') }}</textarea>
                                <x-input-error :messages="$errors->get('expected_content')" class="mt-2" />
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Check if specific text exists on the page</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <input id="ssl_check" name="ssl_check" type="checkbox" value="1" 
                                       {{ old('ssl_check') ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="ssl_check" class="ml-2 block text-sm text-gray-900">
                                    Check SSL Certificate
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input id="enabled" name="enabled" type="checkbox" value="1" 
                                       {{ old('enabled', true) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="enabled" class="ml-2 block text-sm text-gray-900">
                                    Enable Monitor
                                </label>
                            </div>
                        </div>

                        <!-- Notification Settings -->
                        <div class="border-t pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Notification Settings</h3>
                                <button type="button" id="use-profile-defaults" 
                                        class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-sm font-medium px-3 py-1.5 rounded-lg border border-blue-300">
                                    📋 Use Profile Defaults
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <!-- SMS Notifications -->
                                <div class="flex items-start space-x-3">
                                    <div class="flex items-center h-5">
                                        <input id="sms_notifications" name="sms_notifications" type="checkbox" value="1" 
                                               {{ old('sms_notifications') ? 'checked' : '' }}
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    </div>
                                    <div class="flex-1">
                                        <label for="sms_notifications" class="text-sm font-medium text-gray-900">
                                            SMS Notifications
                                        </label>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">Send SMS alerts when this monitor goes down</p>
                                        
                                        <div class="mt-2" id="sms-phone-field" style="display: none;">
                                            <x-input-label for="notification_phone" :value="__('Phone Number')" />
                                            <x-text-input id="notification_phone" class="block mt-1 w-full" type="tel" 
                                                         name="notification_phone" :value="old('notification_phone', auth()->user()->phone_number)" 
                                                         placeholder="+1234567890" />
                                            <x-input-error :messages="$errors->get('notification_phone')" class="mt-2" />
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Include country code (e.g., +1 for US)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Email Notifications -->
                                <div class="flex items-start space-x-3">
                                    <div class="flex items-center h-5">
                                        <input id="email_notifications" name="email_notifications" type="checkbox" value="1" 
                                               {{ old('email_notifications') ? 'checked' : '' }}
                                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    </div>
                                    <div class="flex-1">
                                        <label for="email_notifications" class="text-sm font-medium text-gray-900">
                                            Email Notifications
                                        </label>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">Send email alerts when this monitor goes down</p>
                                        
                                        <div class="mt-2" id="email-field" style="display: none;">
                                            <x-input-label for="notification_email" :value="__('Email Address')" />
                                            <x-text-input id="notification_email" class="block mt-1 w-full" type="email" 
                                                         name="notification_email" :value="old('notification_email', auth()->user()->email)" 
                                                         placeholder="you@example.com" />
                                            <x-input-error :messages="$errors->get('notification_email')" class="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Notification Threshold -->
                                <div>
                                    <x-input-label for="notification_threshold" :value="__('Failed Checks Before Alert')" />
                                    <x-text-input id="notification_threshold" class="block mt-1 w-full" type="number" 
                                                 name="notification_threshold" :value="old('notification_threshold', 1)" 
                                                 required min="1" max="10" />
                                    <x-input-error :messages="$errors->get('notification_threshold')" class="mt-2" />
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Number of consecutive failed checks before sending an alert</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('monitors.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Create Monitor') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('type');
            const portField = document.getElementById('port-field');
            const httpFields = document.getElementById('http-fields');
            const smsCheckbox = document.getElementById('sms_notifications');
            const emailCheckbox = document.getElementById('email_notifications');
            const smsPhoneField = document.getElementById('sms-phone-field');
            const emailField = document.getElementById('email-field');
            const useProfileDefaultsBtn = document.getElementById('use-profile-defaults');
            const phoneInput = document.getElementById('notification_phone');
            const emailInput = document.getElementById('notification_email');
            
            function toggleFields() {
                const selectedType = typeSelect.value;
                
                // Show/hide port field for TCP
                if (selectedType === 'tcp') {
                    portField.classList.remove('hidden');
                } else {
                    portField.classList.add('hidden');
                }
                
                // Show/hide HTTP-specific fields
                if (selectedType === 'http' || selectedType === 'https') {
                    httpFields.classList.remove('hidden');
                } else {
                    httpFields.classList.add('hidden');
                }
            }
            
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
            
            function useProfileDefaults() {
                // Set profile defaults
                const userEmail = '{{ auth()->user()->email }}';
                const userPhone = '{{ auth()->user()->phone_number ?? '' }}';
                
                // Enable notifications if user has contact info
                if (userEmail) {
                    emailCheckbox.checked = true;
                    emailInput.value = userEmail;
                }
                
                if (userPhone) {
                    smsCheckbox.checked = true;
                    phoneInput.value = userPhone;
                }
                
                // Update field visibility
                toggleNotificationFields();
                
                // Show success feedback
                useProfileDefaultsBtn.innerHTML = '✓ Applied!';
                useProfileDefaultsBtn.classList.remove('bg-blue-100', 'hover:bg-blue-200', 'text-blue-800', 'border-blue-300');
                useProfileDefaultsBtn.classList.add('bg-green-100', 'text-green-800', 'border-green-300');
                
                setTimeout(() => {
                    useProfileDefaultsBtn.innerHTML = '📋 Use Profile Defaults';
                    useProfileDefaultsBtn.classList.remove('bg-green-100', 'text-green-800', 'border-green-300');
                    useProfileDefaultsBtn.classList.add('bg-blue-100', 'hover:bg-blue-200', 'text-blue-800', 'border-blue-300');
                }, 2000);
            }
            
            typeSelect.addEventListener('change', toggleFields);
            smsCheckbox.addEventListener('change', toggleNotificationFields);
            emailCheckbox.addEventListener('change', toggleNotificationFields);
            useProfileDefaultsBtn.addEventListener('click', useProfileDefaults);
            
            toggleFields(); // Initial call
            toggleNotificationFields(); // Initial call
        });
    </script>
</x-app-layout>