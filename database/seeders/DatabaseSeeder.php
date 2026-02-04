<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@teckblog.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);

        // Create regular user
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'user@teckblog.com',
            'password' => Hash::make('user123'),
            'role' => 'user',
        ]);

        // Create categories
        $categories = [
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Latest technology news and trends'
            ],
            [
                'name' => 'Programming',
                'slug' => 'programming',
                'description' => 'Coding tutorials and best practices'
            ],
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Frontend and backend web development'
            ],
            [
                'name' => 'AI & Machine Learning',
                'slug' => 'ai-machine-learning',
                'description' => 'Artificial Intelligence and ML insights'
            ],
            [
                'name' => 'DevOps',
                'slug' => 'devops',
                'description' => 'DevOps tools and practices'
            ],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        // Create sample posts
        $techCategory = Category::where('slug', 'technology')->first();
        $progCategory = Category::where('slug', 'programming')->first();
        $webCategory = Category::where('slug', 'web-development')->first();

        Post::create([
            'user_id' => $admin->id,
            'category_id' => $progCategory->id,
            'title' => 'Getting Started with React and TypeScript',
            'slug' => 'getting-started-react-typescript',
            'excerpt' => 'Learn how to set up a React project with TypeScript from scratch.',
            'content' => '<h2>Introduction to React with TypeScript</h2><p>React and TypeScript is a powerful combination for building modern web applications. In this tutorial, we will explore how to set up a React project with TypeScript and understand the benefits of type safety.</p><h3>Why TypeScript?</h3><p>TypeScript provides static typing, better IDE support, and helps catch errors during development rather than runtime.</p><h3>Getting Started</h3><p>First, create a new React app with TypeScript template:</p><pre><code>npx create-react-app my-app --template typescript</code></pre><p>This will set up a new React project with TypeScript configuration out of the box.</p>',
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'views_count' => 150,
        ]);

        Post::create([
            'user_id' => $admin->id,
            'category_id' => $webCategory->id,
            'title' => 'Building RESTful APIs with Laravel',
            'slug' => 'building-restful-apis-laravel',
            'excerpt' => 'A comprehensive guide to creating RESTful APIs using Laravel framework.',
            'content' => '<h2>Laravel API Development</h2><p>Laravel is an excellent framework for building RESTful APIs. In this guide, we will cover the essential concepts and best practices for API development.</p><h3>Setting Up Routes</h3><p>Laravel provides a clean way to define API routes in the routes/api.php file. All routes defined here are automatically prefixed with /api.</p><h3>Controllers and Resources</h3><p>Use resource controllers to handle CRUD operations efficiently. Laravel also provides API resources for transforming models into JSON responses.</p>',
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'views_count' => 230,
        ]);

        Post::create([
            'user_id' => $user->id,
            'category_id' => $techCategory->id,
            'title' => 'The Future of Artificial Intelligence',
            'slug' => 'future-artificial-intelligence',
            'excerpt' => 'Exploring the trends and predictions for AI in the coming years.',
            'content' => '<h2>AI: The Next Frontier</h2><p>Artificial Intelligence is rapidly evolving and transforming every industry. From healthcare to finance, AI is making significant impacts.</p><h3>Current Trends</h3><p>Machine learning models are becoming more sophisticated, with large language models leading the way. GPT, BERT, and other transformer-based models are revolutionizing natural language processing.</p><h3>Future Predictions</h3><p>Experts predict that AI will become even more integrated into our daily lives, with advancements in autonomous systems, personalized medicine, and creative AI applications.</p>',
            'status' => 'published',
            'published_at' => now()->subDays(1),
            'views_count' => 89,
        ]);

        Post::create([
            'user_id' => $admin->id,
            'category_id' => $progCategory->id,
            'title' => 'Understanding Async/Await in JavaScript',
            'slug' => 'understanding-async-await-javascript',
            'excerpt' => 'Master asynchronous JavaScript with async/await syntax.',
            'content' => '<h2>Async/Await Explained</h2><p>Asynchronous programming is essential in JavaScript, especially when dealing with API calls, file operations, or any time-consuming tasks.</p><h3>What is Async/Await?</h3><p>Async/await is syntactic sugar built on top of Promises, making asynchronous code easier to write and read.</p><pre><code>async function fetchData() {\n  const response = await fetch(\'https://api.example.com/data\');\n  const data = await response.json();\n  return data;\n}</code></pre><h3>Error Handling</h3><p>Use try/catch blocks to handle errors gracefully in async functions.</p>',
            'status' => 'published',
            'published_at' => now(),
            'views_count' => 45,
        ]);

        Post::create([
            'user_id' => $admin->id,
            'category_id' => $webCategory->id,
            'title' => 'CSS Grid vs Flexbox: When to Use Each',
            'slug' => 'css-grid-vs-flexbox',
            'excerpt' => 'A practical comparison of CSS Grid and Flexbox layout systems.',
            'content' => '<h2>Layout Systems Compared</h2><p>Both CSS Grid and Flexbox are powerful layout tools, but they excel in different scenarios.</p><h3>When to Use Flexbox</h3><p>Flexbox is ideal for one-dimensional layouts - either rows or columns. Use it for navigation bars, card layouts, or centering content.</p><h3>When to Use Grid</h3><p>CSS Grid shines in two-dimensional layouts where you need to control both rows and columns simultaneously. Perfect for page layouts, photo galleries, and complex designs.</p>',
            'status' => 'draft',
            'published_at' => null,
            'views_count' => 0,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin credentials: admin@teckblog.com / admin123');
        $this->command->info('User credentials: user@teckblog.com / user123');
    }
}
