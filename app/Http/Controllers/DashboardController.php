<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\SMSConversation;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get monitor counts
        $monitors = Monitor::where('user_id', $user->id)->get();
        $upCount = $monitors->where('current_status', 'up')->count();
        $warningCount = $monitors->where('current_status', 'warning')->count();
        $downCount = $monitors->where('current_status', 'down')->count();
        
        // Get recent SMS conversations
        $recentSMS = SMSConversation::incoming()
            ->latest()
            ->limit(5)
            ->get();
        
        // Get active monitors (limit to first 10 for dashboard)
        $activeMonitors = $monitors->take(10);

        return view('dashboard', compact(
            'upCount',
            'warningCount', 
            'downCount',
            'recentSMS',
            'monitors',
            'activeMonitors'
        ));
    }

    public function jamesNick()
    {
        $articles = [
            [
                'title' => 'Scientists Discover Quantum Entanglement Could Enable Faster-Than-Light Communication',
                'summary' => 'Breakthrough research shows quantum particles maintaining instant connections across vast distances, potentially revolutionizing space communication.',
                'category' => 'Science Fiction',
                'published' => '2 hours ago',
                'image' => 'https://images.unsplash.com/photo-1446776877081-d282a0f896e2?w=400&h=250&fit=crop',
                'url' => '#'
            ],
            [
                'title' => 'Studio Ghibli Announces New Anime Series Inspired by Cyberpunk Aesthetics',
                'summary' => 'The legendary animation studio reveals their latest project combining traditional hand-drawn animation with futuristic themes.',
                'category' => 'Anime', 
                'published' => '4 hours ago',
                'image' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=250&fit=crop',
                'url' => '#'
            ],
            [
                'title' => 'NewJeans Breaks Global Streaming Records with Latest Single',
                'summary' => 'The K-pop sensation continues to dominate international charts with their innovative sound and visual concepts.',
                'category' => 'K-pop',
                'published' => '6 hours ago', 
                'image' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=250&fit=crop',
                'url' => '#'
            ],
            [
                'title' => 'NASA Plans First Human Colony on Mars Using AI-Driven Architecture',
                'summary' => 'Advanced artificial intelligence will design self-sustaining habitats for the first permanent Mars settlement by 2035.',
                'category' => 'Science Fiction',
                'published' => '8 hours ago',
                'image' => 'https://images.unsplash.com/photo-1446776653964-20c1d3a81b06?w=400&h=250&fit=crop',
                'url' => '#'
            ],
            [
                'title' => 'Attack on Titan Creator Reveals Inspiration Behind the Titans',
                'summary' => 'Hajime Isayama discusses the real-world influences that shaped one of anime\'s most iconic antagonists.',
                'category' => 'Anime',
                'published' => '12 hours ago',
                'image' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=250&fit=crop',
                'url' => '#'
            ],
            [
                'title' => 'BTS Members Announce Solo Projects Exploring Different Musical Genres',
                'summary' => 'Each member will release individual albums showcasing their unique artistic vision and musical influences.',
                'category' => 'K-pop',
                'published' => '1 day ago',
                'image' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&h=250&fit=crop',
                'url' => '#'
            ],
            [
                'title' => 'Time Travel Experiment Shows Promise in Quantum Lab Setting',
                'summary' => 'Researchers successfully send quantum information backwards through time using exotic matter configurations.',
                'category' => 'Science Fiction',
                'published' => '1 day ago',
                'image' => 'https://images.unsplash.com/photo-1446776877081-d282a0f896e2?w=400&h=250&fit=crop',
                'url' => '#'
            ],
            [
                'title' => 'Demon Slayer Movie Breaks Box Office Records in International Markets',
                'summary' => 'The latest installment in the beloved anime series achieves unprecedented success worldwide.',
                'category' => 'Anime',
                'published' => '2 days ago',
                'image' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&h=250&fit=crop',
                'url' => '#'
            ]
        ];

        return view('james-nick-dashboard', compact('articles'));
    }
}
