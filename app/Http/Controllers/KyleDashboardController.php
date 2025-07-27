<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class KyleDashboardController extends Controller
{
    public function index()
    {
        // Cache articles for 30 minutes to avoid hitting rate limits
        $articles = Cache::remember('kyle_quantum_articles', 30 * 60, function () {
            return $this->fetchQuantumComputingArticles();
        });

        return view('kyle-dashboard', [
            'articles' => $articles,
            'lastUpdated' => Cache::get('kyle_articles_last_updated', now())
        ]);
    }

    private function fetchQuantumComputingArticles()
    {
        $articles = collect();
        
        try {
            // Search multiple sources for quantum computing articles
            $sources = [
                $this->searchArxiv(),
                $this->searchNewsAPI(), 
                $this->searchReddit(),
                $this->searchHackerNews()
            ];

            foreach ($sources as $sourceArticles) {
                $articles = $articles->merge($sourceArticles);
            }

            // Sort by date and take top 25
            $articles = $articles->sortByDesc('published_at')->take(25)->values();
            
            Cache::put('kyle_articles_last_updated', now(), 60 * 60);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch quantum computing articles: ' . $e->getMessage());
            
            // Return some fallback articles if API calls fail
            $articles = $this->getFallbackArticles();
        }

        return $articles;
    }

    private function searchNewsAPI()
    {
        try {
            // Using a free news aggregator API (you might want to get a real API key)
            $response = Http::timeout(10)->get('https://newsapi.org/v2/everything', [
                'q' => 'quantum computing',
                'sortBy' => 'publishedAt',
                'pageSize' => 10,
                'apiKey' => 'demo' // This won't work in production, need real key
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['articles'] ?? [])->map(function ($article) {
                    return [
                        'title' => $article['title'],
                        'url' => $article['url'],
                        'description' => $article['description'],
                        'source' => $article['source']['name'] ?? 'News API',
                        'published_at' => $article['publishedAt'] ? \Carbon\Carbon::parse($article['publishedAt']) : now(),
                        'author' => $article['author'],
                        'image' => $article['urlToImage']
                    ];
                });
            }
        } catch (\Exception $e) {
            Log::warning('NewsAPI fetch failed: ' . $e->getMessage());
        }

        return collect();
    }

    private function searchArxiv()
    {
        try {
            // arXiv API for academic papers
            $response = Http::timeout(10)->get('http://export.arxiv.org/api/query', [
                'search_query' => 'cat:quant-ph OR all:quantum+computing',
                'sortBy' => 'submittedDate',
                'sortOrder' => 'descending',
                'max_results' => 10
            ]);

            if ($response->successful()) {
                $xml = simplexml_load_string($response->body());
                $articles = collect();
                
                foreach ($xml->entry as $entry) {
                    $articles->push([
                        'title' => (string) $entry->title,
                        'url' => (string) $entry->id,
                        'description' => substr((string) $entry->summary, 0, 200) . '...',
                        'source' => 'arXiv',
                        'published_at' => \Carbon\Carbon::parse((string) $entry->published),
                        'author' => isset($entry->author[0]) ? (string) $entry->author[0]->name : 'Unknown',
                        'image' => null
                    ]);
                }
                
                return $articles;
            }
        } catch (\Exception $e) {
            Log::warning('arXiv fetch failed: ' . $e->getMessage());
        }

        return collect();
    }

    private function searchReddit()
    {
        try {
            // Reddit API for quantum computing discussions
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'KyleDashboard/1.0'])
                ->get('https://www.reddit.com/r/QuantumComputing/hot.json', [
                    'limit' => 10
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['data']['children'] ?? [])->map(function ($post) {
                    $postData = $post['data'];
                    return [
                        'title' => $postData['title'],
                        'url' => 'https://reddit.com' . $postData['permalink'],
                        'description' => substr($postData['selftext'] ?: 'Discussion on Reddit', 0, 200) . '...',
                        'source' => 'Reddit - r/QuantumComputing',
                        'published_at' => \Carbon\Carbon::createFromTimestamp($postData['created_utc']),
                        'author' => $postData['author'],
                        'image' => $postData['thumbnail'] !== 'self' ? $postData['thumbnail'] : null
                    ];
                });
            }
        } catch (\Exception $e) {
            Log::warning('Reddit fetch failed: ' . $e->getMessage());
        }

        return collect();
    }

    private function searchHackerNews()
    {
        try {
            // Search Hacker News for quantum computing stories
            $response = Http::timeout(10)->get('https://hn.algolia.com/api/v1/search', [
                'query' => 'quantum computing',
                'tags' => 'story',
                'hitsPerPage' => 10
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return collect($data['hits'] ?? [])->map(function ($hit) {
                    return [
                        'title' => $hit['title'],
                        'url' => $hit['url'] ?: 'https://news.ycombinator.com/item?id=' . $hit['objectID'],
                        'description' => 'Discussion on Hacker News - ' . ($hit['points'] ?? 0) . ' points, ' . ($hit['num_comments'] ?? 0) . ' comments',
                        'source' => 'Hacker News',
                        'published_at' => \Carbon\Carbon::parse($hit['created_at']),
                        'author' => $hit['author'],
                        'image' => null
                    ];
                });
            }
        } catch (\Exception $e) {
            Log::warning('Hacker News fetch failed: ' . $e->getMessage());
        }

        return collect();
    }

    private function getFallbackArticles()
    {
        // Fallback articles in case APIs fail
        return collect([
            [
                'title' => 'Welcome to the Kyle Dashboard!',
                'url' => 'https://example.com',
                'description' => 'This is a special dashboard created just for Kyle to showcase quantum computing articles from around the web.',
                'source' => 'Kyle Dashboard',
                'published_at' => now(),
                'author' => 'Claude AI',
                'image' => null
            ],
            [
                'title' => 'IBM Quantum Computing Breakthrough',
                'url' => 'https://research.ibm.com/quantum',
                'description' => 'IBM continues to push the boundaries of quantum computing with new quantum processors and algorithms.',
                'source' => 'IBM Research',
                'published_at' => now()->subHours(2),
                'author' => 'IBM Team',
                'image' => null
            ],
            [
                'title' => 'Google Quantum Supremacy Update',
                'url' => 'https://quantum.google',
                'description' => 'Google provides updates on their quantum supremacy experiments and future quantum computing goals.',
                'source' => 'Google Quantum',
                'published_at' => now()->subHours(5),
                'author' => 'Google Research',
                'image' => null
            ]
        ]);
    }
}
