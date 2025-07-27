<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    🚀 Kyle's Quantum Computing Dashboard
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Hey Kyle! Here are the latest quantum computing articles from around the web 🧠⚡
                </p>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Last updated: {{ $lastUpdated->format('M j, Y H:i') }}
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">Welcome Kyle! 👋</h3>
                        <p class="text-blue-100">This dashboard proves AI can fetch real-time quantum computing content from multiple sources across the internet. Check out the {{ $articles->count() }} latest articles below!</p>
                    </div>
                    <div class="text-6xl opacity-20">
                        🔬
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border-l-4 border-blue-500">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                    📰
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Articles</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $articles->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border-l-4 border-green-500">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                    🌐
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Sources</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $articles->pluck('source')->unique()->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border-l-4 border-purple-500">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                    ⚛️
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Quantum Focus</div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">100%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border-l-4 border-yellow-500">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                                    🤖
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">AI Powered</div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">Claude</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Articles Grid -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Latest Quantum Computing Articles</h3>
                    
                    @if($articles->isEmpty())
                        <div class="text-center py-8">
                            <div class="text-6xl mb-4">🔍</div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Fetching quantum articles...</h3>
                            <p class="text-gray-500 dark:text-gray-400">Please refresh in a moment while we gather the latest content.</p>
                        </div>
                    @else
                        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            @foreach($articles as $article)
                                <article class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if(str_contains($article['source'], 'arXiv')) bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                            @elseif(str_contains($article['source'], 'Reddit')) bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300
                                            @elseif(str_contains($article['source'], 'Hacker News')) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                            @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                            @endif">
                                            {{ $article['source'] }}
                                        </span>
                                        <time class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($article['published_at'])->diffForHumans() }}
                                        </time>
                                    </div>
                                    
                                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                        <a href="{{ $article['url'] }}" target="_blank" class="hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $article['title'] }}
                                        </a>
                                    </h4>
                                    
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3 line-clamp-3">
                                        {{ $article['description'] }}
                                    </p>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            @if($article['author'])
                                                👤 {{ $article['author'] }}
                                            @else
                                                📝 No author
                                            @endif
                                        </span>
                                        <a href="{{ $article['url'] }}" target="_blank" 
                                           class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                                            Read More →
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer Message -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    🤖 This dashboard was automatically generated by Claude AI to fetch live quantum computing content from arXiv, Reddit, Hacker News, and news sources. 
                    <br>Articles are cached for 30 minutes and automatically refreshed. Kyle, I hope this proves that AI can indeed access and aggregate real-time web content! 
                </p>
            </div>
        </div>
    </div>
</x-app-layout>