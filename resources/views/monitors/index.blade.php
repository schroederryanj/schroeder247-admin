<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Monitors') }}
            </h2>
            <a href="{{ route('monitors.create') }}" 
               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add Monitor
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($monitors->isEmpty())
                        <div class="text-center py-8">
                            <div class="text-gray-400 text-6xl mb-4">📊</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No monitors yet</h3>
                            <p class="text-gray-500 mb-4">Get started by adding your first monitor to track uptime.</p>
                            <a href="{{ route('monitors.create') }}" 
                               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Add Your First Monitor
                            </a>
                        </div>
                    @else
                        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($monitors as $monitor)
                                <div class="bg-gray-50 rounded-lg p-4 border">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-semibold text-lg text-gray-900">{{ $monitor->name }}</h3>
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
                                    
                                    <p class="text-gray-600 text-sm mb-2">{{ $monitor->url }}</p>
                                    <p class="text-gray-500 text-xs mb-3">
                                        Check every {{ $monitor->check_interval }} minutes • 
                                        {{ ucfirst($monitor->type) }}
                                        @if(!$monitor->enabled)
                                            • <span class="text-red-500">Disabled</span>
                                        @endif
                                    </p>
                                    
                                    <div class="flex justify-between items-center text-xs text-gray-500 mb-3">
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
        </div>
    </div>
</x-app-layout>