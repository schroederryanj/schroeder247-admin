<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Monitor: ') . $monitor->name }}
            </h2>
            <a href="{{ route('monitors.index') }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Monitors
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('monitors.update', $monitor) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="name" :value="__('Monitor Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" 
                                         :value="old('name', $monitor->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="url" :value="__('URL or IP Address')" />
                            <x-text-input id="url" class="block mt-1 w-full" type="url" name="url" 
                                         :value="old('url', $monitor->url)" required />
                            <x-input-error :messages="$errors->get('url')" class="mt-2" />
                            <p class="text-sm text-gray-600 mt-1">Enter the full URL (e.g., https://example.com) or IP address</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="type" :value="__('Monitor Type')" />
                                <select id="type" name="type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="http" {{ old('type', $monitor->type) === 'http' ? 'selected' : '' }}>HTTP</option>
                                    <option value="https" {{ old('type', $monitor->type) === 'https' ? 'selected' : '' }}>HTTPS</option>
                                    <option value="ping" {{ old('type', $monitor->type) === 'ping' ? 'selected' : '' }}>Ping</option>
                                    <option value="tcp" {{ old('type', $monitor->type) === 'tcp' ? 'selected' : '' }}>TCP Port</option>
                                </select>
                                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="check_interval" :value="__('Check Interval (minutes)')" />
                                <x-text-input id="check_interval" class="block mt-1 w-full" type="number" 
                                             name="check_interval" :value="old('check_interval', $monitor->check_interval)" 
                                             required min="1" max="1440" />
                                <x-input-error :messages="$errors->get('check_interval')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="timeout" :value="__('Timeout (seconds)')" />
                                <x-text-input id="timeout" class="block mt-1 w-full" type="number" 
                                             name="timeout" :value="old('timeout', $monitor->timeout)" 
                                             required min="5" max="60" />
                                <x-input-error :messages="$errors->get('timeout')" class="mt-2" />
                            </div>

                            <div id="port-field" class="{{ $monitor->type === 'tcp' ? '' : 'hidden' }}">
                                <x-input-label for="port" :value="__('Port Number')" />
                                <x-text-input id="port" class="block mt-1 w-full" type="number" 
                                             name="port" :value="old('port', $monitor->port)" 
                                             min="1" max="65535" />
                                <x-input-error :messages="$errors->get('port')" class="mt-2" />
                            </div>
                        </div>

                        <div id="http-fields" class="{{ in_array($monitor->type, ['http', 'https']) ? '' : 'hidden' }}">
                            <div class="mb-4">
                                <x-input-label for="expected_status_code" :value="__('Expected Status Code (optional)')" />
                                <x-text-input id="expected_status_code" class="block mt-1 w-full" type="number" 
                                             name="expected_status_code" :value="old('expected_status_code', $monitor->expected_status_code)" 
                                             min="100" max="599" />
                                <x-input-error :messages="$errors->get('expected_status_code')" class="mt-2" />
                                <p class="text-sm text-gray-600 mt-1">Leave blank to accept any 2xx status code</p>
                            </div>

                            <div class="mb-4">
                                <x-input-label for="expected_content" :value="__('Expected Content (optional)')" />
                                <textarea id="expected_content" name="expected_content" rows="3" 
                                         class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                         placeholder="Text that should be present on the page">{{ old('expected_content', $monitor->expected_content) }}</textarea>
                                <x-input-error :messages="$errors->get('expected_content')" class="mt-2" />
                                <p class="text-sm text-gray-600 mt-1">Check if specific text exists on the page</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <input id="ssl_check" name="ssl_check" type="checkbox" value="1" 
                                       {{ old('ssl_check', $monitor->ssl_check) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="ssl_check" class="ml-2 block text-sm text-gray-900">
                                    Check SSL Certificate
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input id="enabled" name="enabled" type="checkbox" value="1" 
                                       {{ old('enabled', $monitor->enabled) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="enabled" class="ml-2 block text-sm text-gray-900">
                                    Enable Monitor
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('monitors.index') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Update Monitor') }}
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
            
            typeSelect.addEventListener('change', toggleFields);
        });
    </script>
</x-app-layout>