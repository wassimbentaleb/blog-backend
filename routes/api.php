<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ReactionController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ImageController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);

// Posts (public)
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/search', [PostController::class, 'search']);
Route::get('/posts/{slug}', [PostController::class, 'show']);
Route::get('/posts/{slug}/related', [PostController::class, 'related']);
Route::get('/categories/{slug}/posts', [PostController::class, 'byCategory']);

// Comments (public read)
Route::get('/posts/{post}/comments', [CommentController::class, 'index']);

// Reactions (public read)
Route::get('/posts/{post}/reactions', [ReactionController::class, 'index']);
Route::get('/posts/{post}/reactions/stats', [ReactionController::class, 'stats']);

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Comments (authenticated users)
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Reactions (authenticated users)
    Route::post('/posts/{post}/reactions', [ReactionController::class, 'store']);
    Route::delete('/posts/{post}/reactions', [ReactionController::class, 'destroy']);

});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Dashboard Statistics
    Route::get('/stats', [DashboardController::class, 'stats']);

    // Posts Management
    Route::get('/posts', [PostController::class, 'adminIndex']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/{post}', [PostController::class, 'adminShow']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);

    // Categories Management
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

    // Comments Moderation
    Route::get('/comments', [CommentController::class, 'adminIndex']);
    Route::put('/comments/{comment}/approve', [CommentController::class, 'approve']);

    // Newsletter Management
    Route::get('/newsletter/subscribers', [NewsletterController::class, 'index']);
    Route::delete('/newsletter/subscribers/{id}', [NewsletterController::class, 'destroy']);

    // Image Upload
    Route::post('/upload-image', [ImageController::class, 'upload']);

});
