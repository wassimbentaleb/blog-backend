<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    // Public: Subscribe to newsletter
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid email address',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if already subscribed
        $existing = NewsletterSubscription::where('email', $request->email)->first();

        if ($existing) {
            if ($existing->is_active) {
                return response()->json([
                    'message' => 'This email is already subscribed to our newsletter.'
                ], 409);
            }

            // Reactivate subscription
            $existing->update([
                'is_active' => true,
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);

            return response()->json([
                'message' => 'Successfully resubscribed to the newsletter!',
                'subscription' => $existing
            ], 200);
        }

        // Create new subscription
        $subscription = NewsletterSubscription::create([
            'email' => $request->email,
            'is_active' => true,
            'subscribed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully subscribed to the newsletter!',
            'subscription' => $subscription
        ], 201);
    }

    // Public: Unsubscribe from newsletter
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $subscription = NewsletterSubscription::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (!$subscription) {
            return response()->json([
                'message' => 'Email not found in our subscriber list.'
            ], 404);
        }

        $subscription->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully unsubscribed from the newsletter.'
        ]);
    }

    // Admin: Get all subscribers
    public function index(Request $request)
    {
        $query = NewsletterSubscription::query();

        if ($request->has('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        $subscribers = $query->orderBy('subscribed_at', 'desc')->paginate(50);

        return response()->json($subscribers);
    }

    // Admin: Delete subscriber
    public function destroy($id)
    {
        $subscription = NewsletterSubscription::findOrFail($id);
        $subscription->delete();

        return response()->json([
            'message' => 'Subscriber deleted successfully'
        ]);
    }
}
