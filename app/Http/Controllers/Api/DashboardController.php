<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Category;
use App\Models\Comment;
use App\Models\User;
use App\Models\Reaction;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Admin: Get dashboard statistics
    public function stats()
    {
        $stats = [
            // Posts Statistics
            'posts' => [
                'total' => Post::count(),
                'published' => Post::where('status', 'published')->count(),
                'drafts' => Post::where('status', 'draft')->count(),
                'this_month' => Post::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ],

            // Categories
            'categories' => [
                'total' => Category::count(),
            ],

            // Comments
            'comments' => [
                'total' => Comment::count(),
                'pending' => Comment::where('is_approved', false)->count(),
                'approved' => Comment::where('is_approved', true)->count(),
                'recent' => Comment::with(['post', 'user'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
            ],

            // Users
            'users' => [
                'total' => User::count(),
                'admins' => User::where('role', 'admin')->count(),
                'regular' => User::where('role', 'user')->count(),
            ],

            // Reactions
            'reactions' => [
                'total' => Reaction::count(),
                'by_type' => Reaction::selectRaw('reaction_type, COUNT(*) as count')
                    ->groupBy('reaction_type')
                    ->pluck('count', 'reaction_type'),
            ],

            // Newsletter
            'newsletter' => [
                'subscribers' => NewsletterSubscription::where('is_active', true)->count(),
                'total' => NewsletterSubscription::count(),
            ],

            // Most Popular Posts
            'popular_posts' => Post::published()
                ->orderBy('views_count', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'views_count', 'published_at']),

            // Recent Activity
            'recent_posts' => Post::with(['category', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }
}
