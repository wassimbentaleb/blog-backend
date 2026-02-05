<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Public: Get all approved comments for a post (nested)
    public function index(Post $post)
    {
        $comments = Comment::where('post_id', $post->id)
            ->approved()
            ->whereNull('parent_id')
            ->with(['replies' => function ($query) {
                $query->approved()->with('replies');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($comments);
    }

    // Authenticated: Create comment
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
            'author_name' => 'nullable|string|max:255',
            'author_email' => 'nullable|email|max:255',
        ]);

        $validated['post_id'] = $post->id;

        // If user is authenticated
        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
            $validated['is_approved'] = true; // Auto-approve authenticated users
        } else {
            // Guest comment
            $validated['is_approved'] = false; // Require moderation
        }

        $comment = Comment::create($validated);

        return response()->json($comment->load('user'), 201);
    }

    // Authenticated: Update own comment
    public function update(Request $request, Comment $comment)
    {
        // Check if user owns the comment
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update($validated);

        return response()->json($comment->load('user'));
    }

    // Authenticated: Delete comment (cascade deletes replies)
    public function destroy(Request $request, Comment $comment)
    {
        // Check if user owns the comment or is admin
        if ($comment->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    // Admin: Get all comments (for moderation)
    public function adminIndex(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:all,pending,approved',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Comment::with(['post', 'user']);

        // Apply multi-field search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                // Search in comment content
                $q->where('content', 'like', '%' . $search . '%')
                  // Search in guest author name
                  ->orWhere('author_name', 'like', '%' . $search . '%')
                  // Search in guest author email
                  ->orWhere('author_email', 'like', '%' . $search . '%')
                  // Search in registered user's name
                  ->orWhereHas('user', function($subQ) use ($search) {
                      $subQ->where('name', 'like', '%' . $search . '%');
                  })
                  // Search in post title
                  ->orWhereHas('post', function($subQ) use ($search) {
                      $subQ->where('title', 'like', '%' . $search . '%');
                  });
            });
        }

        // Apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->status === 'approved') {
                $query->where('is_approved', true);
            }
        }

        // Order by newest first
        $query->orderBy('created_at', 'desc');

        // Paginate with custom per_page
        $perPage = $request->input('per_page', 20);
        $comments = $query->paginate($perPage);

        return response()->json($comments);
    }

    // Admin: Approve comment
    public function approve(Comment $comment)
    {
        $comment->update(['is_approved' => true]);

        return response()->json($comment);
    }
}
