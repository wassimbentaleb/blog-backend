<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    // Public: Get all published posts
    public function index(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Get per_page parameter, default to 9 for homepage grid (3x3)
        $perPage = $request->input('per_page', 9);

        $posts = Post::published()
            ->with(['category', 'user'])
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);

        return response()->json($posts);
    }

    // Public: Get single post by slug
    public function show($slug)
    {
        $post = Post::where('slug', $slug)
            ->published()
            ->with(['category', 'user'])
            ->firstOrFail();

        // Increment view count
        $post->incrementViews();

        return response()->json($post);
    }

    // Public: Search posts
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([
                'data' => [],
                'message' => 'Search query is required'
            ], 400);
        }

        $posts = Post::published()
            ->where(function($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                  ->orWhere('content', 'like', '%' . $query . '%')
                  ->orWhere('excerpt', 'like', '%' . $query . '%');
            })
            ->with(['category', 'user'])
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return response()->json($posts);
    }

    // Public: Get related posts
    public function related($slug)
    {
        $post = Post::where('slug', $slug)->published()->firstOrFail();
        $relatedPosts = $post->getRelatedPosts(3);

        return response()->json($relatedPosts);
    }

    // Public: Get posts by category
    public function byCategory($categorySlug)
    {
        $posts = Post::published()
            ->whereHas('category', function ($query) use ($categorySlug) {
                $query->where('slug', $categorySlug);
            })
            ->with(['category', 'user'])
            ->orderBy('published_at', 'desc')
            ->paginate(12);

        return response()->json($posts);
    }

    // Admin: Get all posts (including drafts)
    public function adminIndex(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:all,published,draft',
            'category' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Post::with(['category', 'user']);

        // Apply search filter
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Apply category filter
        if ($request->filled('category') && $request->category !== 'all') {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // Order by newest first
        $query->orderBy('created_at', 'desc');

        // Paginate with custom per_page
        $perPage = $request->input('per_page', 10);
        $posts = $query->paginate($perPage);

        return response()->json($posts);
    }

    // Admin: Get single post (including drafts)
    public function adminShow(Post $post)
    {
        return response()->json($post->load(['category', 'user']));
    }

    // Admin: Create post
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:posts,slug',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|string',
            'status' => 'required|in:published,draft',
        ]);

        // Auto-generate slug if not provided
        if (!isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $validated['user_id'] = $request->user()->id;

        $post = Post::create($validated);

        return response()->json($post->load(['category', 'user']), 201);
    }

    // Admin: Update post
    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:posts,slug,' . $post->id,
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image' => 'nullable|string',
            'status' => 'required|in:published,draft',
        ]);

        $post->update($validated);

        return response()->json($post->load(['category', 'user']));
    }

    // Admin: Delete post
    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
