<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    // Public: Get all reactions for a post
    public function index(Post $post)
    {
        $reactions = Reaction::where('post_id', $post->id)->get();

        return response()->json($reactions);
    }

    // Public: Get reaction statistics
    public function stats(Post $post)
    {
        $stats = [
            'jadore' => 0,
            'jaime' => 0,
            'interessant' => 0,
            'inspirant' => 0,
            'utile' => 0,
            'total' => 0,
        ];

        $reactions = Reaction::where('post_id', $post->id)
            ->selectRaw('reaction_type, COUNT(*) as count')
            ->groupBy('reaction_type')
            ->get();

        foreach ($reactions as $reaction) {
            $stats[$reaction->reaction_type] = $reaction->count;
            $stats['total'] += $reaction->count;
        }

        return response()->json($stats);
    }

    // Authenticated: Add or update reaction
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'reaction_type' => 'required|in:jadore,jaime,interessant,inspirant,utile',
        ]);

        // Remove existing reaction if any
        Reaction::where('post_id', $post->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        // Create new reaction
        $reaction = Reaction::create([
            'post_id' => $post->id,
            'user_id' => $request->user()->id,
            'reaction_type' => $validated['reaction_type'],
        ]);

        return response()->json($reaction, 201);
    }

    // Authenticated: Remove reaction
    public function destroy(Request $request, Post $post)
    {
        Reaction::where('post_id', $post->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Reaction removed successfully']);
    }
}
