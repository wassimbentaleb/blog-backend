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
        $query = Comment::with(['post', 'user']);

        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }

        $comments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($comments);
    }

    // Admin: Approve comment
    public function approve(Comment $comment)
    {
        $comment->update(['is_approved' => true]);

        return response()->json($comment);
    }
}
