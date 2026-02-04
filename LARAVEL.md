# Laravel Backend Implementation Guide for Teckblog

This document provides a complete guide to building a Laravel REST API backend for the Teckblog React frontend. Follow these steps to replace the mock localStorage API with a real database-backed Laravel application.

## Table of Contents

- [Features Covered](#features-covered)
- [Architecture Overview](#architecture-overview)
- [Laravel Project Setup](#laravel-project-setup)
- [Database Schema](#database-schema)
- [Models & Relationships](#models--relationships)
- [Authentication Setup](#authentication-setup)
- [API Routes](#api-routes)
- [Controllers Implementation](#controllers-implementation)
- [Frontend Integration](#frontend-integration)
- [File Upload Configuration](#file-upload-configuration)
- [Testing the API](#testing-the-api)
- [Email Notifications Setup](#email-notifications-setup-optional)
- [Complete Migration Checklist](#complete-migration-checklist)
- [Troubleshooting](#troubleshooting)
- [Next Steps](#next-steps)

---

## Features Covered

This comprehensive guide includes implementation for ALL features in the Teckblog frontend:

### Core Features ✅
- **Authentication System**: Register, login, logout with JWT tokens
- **Posts Management**: Full CRUD with published/draft status
- **Categories**: Organize posts by technology topics
- **Comments System**: Nested comments (3 levels) with moderation
- **Reactions System**: 5 French reaction types with statistics
- **File Upload**: Featured image upload for posts

### Enhanced Features ✅
- **Admin Dashboard Statistics**: Complete metrics for posts, comments, users, reactions
- **Newsletter Subscription**: Email subscription management with admin panel
- **Post Search**: Search posts by title, content, and excerpt
- **Related Posts**: Algorithm to show related posts by category
- **Post View Counter**: Track and display post views
- **Email Notifications**: Welcome emails, comment replies, comment approvals

### Security & Performance ✅
- **Role-Based Access Control**: Admin and user roles
- **CORS Configuration**: Secure cross-origin requests
- **API Authentication**: Laravel Sanctum for SPA
- **Admin Middleware**: Protected admin routes
- **Input Validation**: Request validation on all endpoints
- **Queue System**: Background jobs for emails

---

## Architecture Overview

### Technology Stack
- **Frontend**: React 18 + TypeScript (Already built)
- **Backend**: Laravel 10/11
- **Database**: MySQL
- **Authentication**: Laravel Sanctum (for SPA authentication)
- **Storage**: Local/S3 for images

### Communication Flow
```
React App (localhost:3000)
    ↓ HTTP Requests (axios)
Laravel API (localhost:8000/api)
    ↓ Eloquent ORM
MySQL Database
```

---

## Laravel Project Setup

### 1. Create New Laravel Project

```bash
# Navigate to your projects directory
cd C:\Users\Hero\WebstormProjects

# Create new Laravel project
composer create-project laravel/laravel teckblog-backend

# Navigate into the project
cd teckblog-backend
```

### 2. Environment Configuration

Edit `.env` file:

```env
APP_NAME=Teckblog
APP_ENV=local
APP_KEY=base64:... (auto-generated)
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=teckblog
DB_USERNAME=root
DB_PASSWORD=your_password

# Frontend URL for CORS
FRONTEND_URL=http://localhost:3000

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost
```

### 3. Create Database

```sql
CREATE DATABASE teckblog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Install Required Packages

```bash
# Laravel Sanctum for authentication
composer require laravel/sanctum

# Publish Sanctum configuration
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 5. Configure CORS

Edit `config/cors.php`:

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:3000')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

---

## Database Schema

### Migrations

#### 1. Users Table (Already exists, modify if needed)

```bash
php artisan make:migration add_role_to_users_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'user'])->default('user')->after('email');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
```

#### 2. Categories Table

```bash
php artisan make:migration create_categories_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
```

#### 3. Posts Table

```bash
php artisan make:migration create_posts_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->enum('status', ['published', 'draft'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('slug');
        });
    }

    public function down()
    {
        Schema::dropIfExists('posts');
    }
};
```

#### 4. Comments Table

```bash
php artisan make:migration create_comments_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->onDelete('cascade');
            $table->string('author_name')->nullable();
            $table->string('author_email')->nullable();
            $table->text('content');
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index(['post_id', 'parent_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('comments');
    }
};
```

#### 5. Reactions Table

```bash
php artisan make:migration create_reactions_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); // For guest reactions
            $table->enum('reaction_type', ['jadore', 'jaime', 'interessant', 'inspirant', 'utile']);
            $table->timestamps();

            // Ensure one reaction per user/session per post
            $table->unique(['post_id', 'user_id']);
            $table->index(['post_id', 'session_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reactions');
    }
};
```

#### 6. Newsletter Subscriptions Table

```bash
php artisan make:migration create_newsletter_subscriptions_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('subscribed_at')->useCurrent();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->index('email');
        });
    }

    public function down()
    {
        Schema::dropIfExists('newsletter_subscriptions');
    }
};
```

#### 7. Add Views Counter to Posts

```bash
php artisan make:migration add_views_count_to_posts_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('published_at');
            $table->index('views_count');
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};
```

### Run Migrations

```bash
php artisan migrate
```

---

## Models & Relationships

### 1. User Model

Edit `app/Models/User.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
```

### 2. Category Model

```bash
php artisan make:model Category
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    // Relationships
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // Automatically generate slug
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = \Illuminate\Support\Str::slug($value);
    }
}
```

### 3. Post Model

```bash
php artisan make:model Post
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'views_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected $with = ['category', 'user'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePopular($query)
    {
        return $query->orderBy('views_count', 'desc');
    }

    // Helper methods
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function getRelatedPosts($limit = 3)
    {
        return self::published()
            ->where('category_id', $this->category_id)
            ->where('id', '!=', $this->id)
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }

    // Auto-generate slug
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        if (!$this->slug) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }

    // Set published_at when status changes to published
    public function setStatusAttribute($value)
    {
        $this->attributes['status'] = $value;
        if ($value === 'published' && !$this->published_at) {
            $this->attributes['published_at'] = now();
        }
    }
}
```

### 4. Comment Model

```bash
php artisan make:model Comment
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'author_name',
        'author_email',
        'content',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    protected $with = ['user'];

    // Relationships
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('replies');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeParent($query)
    {
        return $query->whereNull('parent_id');
    }

    // Auto-approve logged-in user comments
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comment) {
            if ($comment->user_id) {
                $comment->is_approved = true;
            }
        });
    }
}
```

### 5. Reaction Model

```bash
php artisan make:model Reaction
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'user_id',
        'session_id',
        'reaction_type',
    ];

    // Relationships
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### 6. NewsletterSubscription Model

```bash
php artisan make:model NewsletterSubscription
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'is_active',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

---

## Authentication Setup

### 1. Configure Sanctum

Edit `app/Http/Kernel.php`:

```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### 2. Auth Controller

```bash
php artisan make:controller Api/AuthController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'role' => $request->user()->role,
        ]);
    }
}
```

### 3. Create Admin Middleware

```bash
php artisan make:middleware IsAdmin
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        return $next($request);
    }
}
```

Register in `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ... other middleware
    'admin' => \App\Http\Middleware\IsAdmin::class,
];
```

---

## API Routes

Edit `routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ReactionController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\DashboardController;

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

});
```

---

## Controllers Implementation

### 1. PostController

```bash
php artisan make:controller Api/PostController
```

```php
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
        $posts = Post::published()
            ->with(['category', 'user'])
            ->orderBy('published_at', 'desc')
            ->paginate(12);

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
        $query = Post::with(['category', 'user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $posts = $query->orderBy('created_at', 'desc')->paginate(20);

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
```

### 2. CategoryController

```bash
php artisan make:controller Api/CategoryController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Public: Get all categories
    public function index()
    {
        $categories = Category::withCount('posts')->get();
        return response()->json($categories);
    }

    // Public: Get single category
    public function show($slug)
    {
        $category = Category::where('slug', $slug)
            ->withCount('posts')
            ->firstOrFail();

        return response()->json($category);
    }

    // Admin: Create category
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    // Admin: Update category
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    // Admin: Delete category
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
```

### 3. CommentController

```bash
php artisan make:controller Api/CommentController
```

```php
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
```

### 4. ReactionController

```bash
php artisan make:controller Api/ReactionController
```

```php
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
```

### 5. NewsletterController

```bash
php artisan make:controller Api/NewsletterController
```

```php
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
```

### 6. DashboardController

```bash
php artisan make:controller Api/DashboardController
```

```php
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
```

---

## Frontend Integration

### 1. Install Axios

In your React project:

```bash
npm install axios
```

### 2. Create API Service

Create `src/services/api.ts`:

```typescript
import axios from 'axios';

const API_URL = 'http://localhost:8000/api';

// Create axios instance
const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Important for Sanctum
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle token expiration
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

### 3. Update AuthContext

Replace mock authentication in `src/context/AuthContext.tsx`:

```typescript
import React, { createContext, useState, useContext, useEffect } from 'react';
import api from '../services/api';

interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'user';
}

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  isAdmin: () => boolean;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, passwordConfirmation: string) => Promise<void>;
  logout: () => Promise<void>;
  loading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  // Check if user is logged in on mount
  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      fetchUser();
    } else {
      setLoading(false);
    }
  }, []);

  const fetchUser = async () => {
    try {
      const response = await api.get('/me');
      setUser(response.data);
    } catch (error) {
      localStorage.removeItem('auth_token');
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email: string, password: string) => {
    const response = await api.post('/login', { email, password });
    localStorage.setItem('auth_token', response.data.token);
    setUser(response.data.user);
  };

  const register = async (name: string, email: string, password: string, password_confirmation: string) => {
    const response = await api.post('/register', {
      name,
      email,
      password,
      password_confirmation,
    });
    localStorage.setItem('auth_token', response.data.token);
    setUser(response.data.user);
  };

  const logout = async () => {
    try {
      await api.post('/logout');
    } finally {
      localStorage.removeItem('auth_token');
      setUser(null);
    }
  };

  const isAdmin = () => {
    return user?.role === 'admin';
  };

  const isAuthenticated = !!user;

  return (
    <AuthContext.Provider value={{ user, isAuthenticated, isAdmin, login, register, logout, loading }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
```

### 4. Replace Mock Data Service

Create `src/services/apiData.ts` to replace `mockData.ts`:

```typescript
import api from './api';

export const apiService = {
  // Posts
  getAllPosts: async () => {
    const response = await api.get('/posts');
    return response.data.data; // Laravel pagination structure
  },

  getPostBySlug: async (slug: string) => {
    const response = await api.get(`/posts/${slug}`);
    return response.data;
  },

  getPostsByCategory: async (categorySlug: string) => {
    const response = await api.get(`/categories/${categorySlug}/posts`);
    return response.data.data;
  },

  // Admin: Posts
  getAllPostsAdmin: async () => {
    const response = await api.get('/admin/posts');
    return response.data.data;
  },

  createPost: async (postData: any) => {
    const response = await api.post('/admin/posts', postData);
    return response.data;
  },

  updatePost: async (id: number, postData: any) => {
    const response = await api.put(`/admin/posts/${id}`, postData);
    return response.data;
  },

  deletePost: async (id: number) => {
    const response = await api.delete(`/admin/posts/${id}`);
    return response.data;
  },

  // Categories
  getAllCategories: async () => {
    const response = await api.get('/categories');
    return response.data;
  },

  getCategoryBySlug: async (slug: string) => {
    const response = await api.get(`/categories/${slug}`);
    return response.data;
  },

  // Comments
  getPostComments: async (postId: number) => {
    const response = await api.get(`/posts/${postId}/comments`);
    return response.data;
  },

  addComment: async (
    postId: number,
    userId: number | null,
    content: string,
    parentId: number | null = null,
    authorName?: string,
    authorEmail?: string
  ) => {
    const response = await api.post(`/posts/${postId}/comments`, {
      content,
      parent_id: parentId,
      author_name: authorName,
      author_email: authorEmail,
    });
    return response.data;
  },

  updateComment: async (commentId: number, content: string) => {
    const response = await api.put(`/comments/${commentId}`, { content });
    return response.data;
  },

  deleteComment: async (commentId: number) => {
    const response = await api.delete(`/comments/${commentId}`);
    return response.data;
  },

  // Reactions
  getReactionStats: async (postId: number) => {
    const response = await api.get(`/posts/${postId}/reactions/stats`);
    return response.data;
  },

  addReaction: async (postId: number, userId: number | null, reactionType: string) => {
    const response = await api.post(`/posts/${postId}/reactions`, {
      reaction_type: reactionType,
    });
    return response.data;
  },

  removeReaction: async (postId: number, userId: number | null) => {
    const response = await api.delete(`/posts/${postId}/reactions`);
    return response.data;
  },

  // Search
  searchPosts: async (query: string) => {
    const response = await api.get(`/posts/search?q=${encodeURIComponent(query)}`);
    return response.data.data;
  },

  // Related Posts
  getRelatedPosts: async (slug: string) => {
    const response = await api.get(`/posts/${slug}/related`);
    return response.data;
  },

  // Newsletter
  subscribeNewsletter: async (email: string) => {
    const response = await api.post('/newsletter/subscribe', { email });
    return response.data;
  },

  unsubscribeNewsletter: async (email: string) => {
    const response = await api.post('/newsletter/unsubscribe', { email });
    return response.data;
  },

  // Admin: Dashboard Stats
  getDashboardStats: async () => {
    const response = await api.get('/admin/stats');
    return response.data;
  },

  // Admin: Newsletter Management
  getNewsletterSubscribers: async () => {
    const response = await api.get('/admin/newsletter/subscribers');
    return response.data.data;
  },

  deleteNewsletterSubscriber: async (id: number) => {
    const response = await api.delete(`/admin/newsletter/subscribers/${id}`);
    return response.data;
  },
};
```

### 5. Update Components

Replace all instances of `import { mockAPI } from '../services/mockData'` with:

```typescript
import { apiService } from '../services/apiData';
```

And replace `mockAPI` calls with `apiService` calls throughout your components.

---

## File Upload Configuration

### 1. Configure Storage

```bash
php artisan storage:link
```

### 2. Create Image Upload Controller

```bash
php artisan make:controller Api/ImageController
```

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = Str::random(40) . 'clock' . $image->getClientOriginalExtension();
            $path = $image->storeAs('images/posts', $filename, 'public');

            return response()->json([
                'url' => Storage::url($path),
                'path' => $path,
            ]);
        }

        return response()->json(['message' => 'No image provided'], 400);
    }
}
```

Add route:

```php
Route::middleware(['auth:sanctum', 'admin'])->post('/upload-image', [ImageController::class, 'upload']);
```

### 3. Frontend Image Upload

Create upload function in React:

```typescript
export const uploadImage = async (file: File): Promise<string> => {
  const formData = new FormData();
  formData.append('image', file);

  const response = await api.post('/upload-image', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  });

  return `http://localhost:8000${response.data.url}`;
};
```

---

## Database Seeders

### Create Seeders

```bash
php artisan make:seeder DatabaseSeeder
```

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@blog.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Create regular user
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@blog.com',
            'password' => Hash::make('user123'),
            'role' => 'user',
        ]);

        // Create categories
        $categories = [
            ['name' => 'Technology', 'description' => 'Latest in tech'],
            ['name' => 'Programming', 'description' => 'Coding tutorials'],
            ['name' => 'Web Development', 'description' => 'Web dev tips'],
            ['name' => 'AI & Machine Learning', 'description' => 'AI news'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        // Create sample posts
        $category = Category::first();

        Post::create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'title' => 'Getting Started with React and TypeScript',
            'slug' => 'getting-started-react-typescript',
            'excerpt' => 'Learn how to set up a React project with TypeScript',
            'content' => '<p>This is a sample blog post about React and TypeScript...</p>',
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
}
```

Run seeders:

```bash
php artisan db:seed
```

---

## Testing the API

### Start Laravel Server

```bash
php artisan serve
```

### Test Endpoints with Postman or cURL

#### 1. Register User

```bash
POST http://localhost:8000/api/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

#### 2. Login

```bash
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "email": "admin@blog.com",
  "password": "admin123"
}
```

Response:
```json
{
  "user": { ... },
  "token": "1|xxxxxxxxxxxxx"
}
```

#### 3. Get Posts

```bash
GET http://localhost:8000/api/posts
```

#### 4. Create Post (Admin)

```bash
POST http://localhost:8000/api/admin/posts
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "My First Post",
  "content": "<p>Post content here...</p>",
  "category_id": 1,
  "status": "published"
}
```

---

## Complete Migration Checklist

### Backend Setup
- [ ] Create Laravel project
- [ ] Configure database and environment
- [ ] Install Laravel Sanctum
- [ ] Run all migrations (users, categories, posts, comments, reactions, newsletter, views_count)
- [ ] Create all models with relationships (User, Category, Post, Comment, Reaction, NewsletterSubscription)
- [ ] Implement authentication with Sanctum
- [ ] Create admin middleware
- [ ] Configure CORS for React frontend

### Controllers
- [ ] Create AuthController (register, login, logout, me)
- [ ] Create PostController (CRUD, search, related posts, views increment)
- [ ] Create CategoryController (CRUD)
- [ ] Create CommentController (CRUD, moderation, approve)
- [ ] Create ReactionController (add, remove, stats)
- [ ] Create NewsletterController (subscribe, unsubscribe, admin management)
- [ ] Create DashboardController (statistics endpoint)
- [ ] Create ImageController (file upload)

### API Routes
- [ ] Define authentication routes
- [ ] Define public routes (posts, categories, comments, reactions, newsletter)
- [ ] Define authenticated user routes (create comments/reactions)
- [ ] Define admin routes (post management, moderation, dashboard stats)

### Database
- [ ] Seed database with sample data (admin user, categories, posts)
- [ ] Test all database relationships
- [ ] Verify cascade deletes work properly

### Testing Backend
- [ ] Test authentication endpoints (register, login, logout)
- [ ] Test post CRUD operations
- [ ] Test search and related posts
- [ ] Test comments with nesting
- [ ] Test reactions (add, remove, toggle)
- [ ] Test newsletter subscription
- [ ] Test admin dashboard stats
- [ ] Test image upload
- [ ] Test permissions and middleware

### Frontend Integration
- [ ] Install axios in React project
- [ ] Create API service layer (api.ts)
- [ ] Create API data service (apiData.ts) with all endpoints
- [ ] Update AuthContext to use real API
- [ ] Replace mockData imports with apiService
- [ ] Update all components to use real API
- [ ] Test authentication flow (login, logout, register)
- [ ] Test post creation and editing
- [ ] Test comments creation and moderation
- [ ] Test reactions system
- [ ] Test newsletter subscription in footer
- [ ] Test admin dashboard statistics display
- [ ] Test search functionality
- [ ] Test related posts display

### Optional Enhancements
- [ ] Configure email notifications (SMTP)
- [ ] Create notification classes (CommentReply, CommentApproved, NewsletterWelcome)
- [ ] Set up queue system for emails
- [ ] Configure file storage (S3 or local)
- [ ] Implement API rate limiting
- [ ] Add request validation rules
- [ ] Create API documentation

### Final Testing
- [ ] Test full user flow from frontend to backend
- [ ] Test error handling and validation messages
- [ ] Check all API responses match frontend expectations
- [ ] Verify CORS is working properly
- [ ] Test with multiple users simultaneously
- [ ] Check database for data integrity

---

## Troubleshooting

### CORS Issues

If you get CORS errors:

1. Ensure `config/cors.php` is properly configured
2. Check `.env` has `FRONTEND_URL=http://localhost:3000`
3. Verify `withCredentials: true` in axios config

### Authentication Issues

1. Clear browser cache and cookies
2. Check token is stored in localStorage
3. Verify Sanctum middleware is registered
4. Test with Postman first

### Database Issues

1. Check database credentials in `.env`
2. Ensure MySQL is running
3. Run `php artisan migrate:fresh --seed` to reset

---

## Email Notifications Setup (Optional)

### 1. Configure Mail Settings

Edit `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # Or your SMTP server
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@teckblog.com
MAIL_FROM_NAME="${APP_NAME}"
```

For production, use services like:
- **SendGrid**: Reliable email delivery
- **Mailgun**: Developer-friendly API
- **Amazon SES**: Cost-effective for high volume
- **Gmail**: For development only

### 2. Create Notification Classes

#### Comment Reply Notification

```bash
php artisan make:notification CommentReplyNotification
```

```php
<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CommentReplyNotification extends Notification
{
    use Queueable;

    protected $comment;
    protected $reply;

    public function __construct(Comment $comment, Comment $reply)
    {
        $this->comment = $comment;
        $this->reply = $reply;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $postUrl = env('FRONTEND_URL') . '/post/' . $this->comment->post->slug;

        return (new MailMessage)
            ->subject('New Reply to Your Comment on Teckblog')
            ->greeting('Hello ' . ($this->comment->user ? $this->comment->user->name : $this->comment->author_name) . '!')
            ->line('Someone replied to your comment on "' . $this->comment->post->title . '"')
            ->line($this->reply->content)
            ->action('View Comment', $postUrl)
            ->line('Thank you for being part of our community!');
    }
}
```

#### Comment Approved Notification

```bash
php artisan make:notification CommentApprovedNotification
```

```php
<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CommentApprovedNotification extends Notification
{
    use Queueable;

    protected $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $postUrl = env('FRONTEND_URL') . '/post/' . $this->comment->post->slug;

        return (new MailMessage)
            ->subject('Your Comment Has Been Approved')
            ->greeting('Hello!')
            ->line('Your comment on "' . $this->comment->post->title . '" has been approved and is now visible.')
            ->line('"' . $this->comment->content . '"')
            ->action('View Comment', $postUrl)
            ->line('Thank you for contributing to Teckblog!');
    }
}
```

#### Newsletter Welcome Notification

```bash
php artisan make:notification NewsletterWelcomeNotification
```

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewsletterWelcomeNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Welcome to Teckblog Newsletter!')
            ->greeting('Welcome!')
            ->line('Thank you for subscribing to the Teckblog newsletter.')
            ->line('You will now receive updates about our latest tech articles, tutorials, and insights.')
            ->action('Visit Teckblog', env('FRONTEND_URL'))
            ->line('You can unsubscribe at any time from the footer of our emails.');
    }
}
```

### 3. Trigger Notifications in Controllers

Update `CommentController`:

```php
use App\Notifications\CommentReplyNotification;
use App\Notifications\CommentApprovedNotification;

// In store method, after creating comment with parent_id
if ($comment->parent_id) {
    $parentComment = Comment::find($comment->parent_id);

    if ($parentComment->user) {
        $parentComment->user->notify(new CommentReplyNotification($parentComment, $comment));
    } elseif ($parentComment->author_email) {
        \Illuminate\Support\Facades\Notification::route('mail', $parentComment->author_email)
            ->notify(new CommentReplyNotification($parentComment, $comment));
    }
}

// In approve method
public function approve(Comment $comment)
{
    $comment->update(['is_approved' => true]);

    // Send approval notification
    if ($comment->user) {
        $comment->user->notify(new CommentApprovedNotification($comment));
    } elseif ($comment->author_email) {
        \Illuminate\Support\Facades\Notification::route('mail', $comment->author_email)
            ->notify(new CommentApprovedNotification($comment));
    }

    return response()->json($comment);
}
```

Update `NewsletterController`:

```php
use App\Notifications\NewsletterWelcomeNotification;

// In subscribe method, after creating subscription
\Illuminate\Support\Facades\Notification::route('mail', $subscription->email)
    ->notify(new NewsletterWelcomeNotification());
```

### 4. Queue Configuration (Recommended)

For better performance, send emails via queues:

```bash
# Create jobs table
php artisan queue:table
php artisan migrate

# Configure queue in .env
QUEUE_CONNECTION=database
```

Make notifications queueable by implementing `ShouldQueue`:

```php
class CommentReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;
    // ...
}
```

Run queue worker:

```bash
php artisan queue:work
```

---

## Next Steps

After completing backend integration:

1. **Deploy Backend**: Deploy to DigitalOcean, AWS, or Heroku
2. **Environment Variables**: Update frontend API URL for production
3. **File Storage**: Configure S3 or cloud storage
4. **Email Setup**: Configure email for notifications
5. **Caching**: Implement Redis caching
6. **Queue Jobs**: Set up queues for heavy tasks
7. **API Documentation**: Generate with Scribe or similar

---

**Your Laravel backend is now ready to power the Teckblog React frontend!**
