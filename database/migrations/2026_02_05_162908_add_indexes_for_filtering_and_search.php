<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to posts table
        Schema::table('posts', function (Blueprint $table) {
            // Already has index on slug from original migration
            // Add index for status filtering
            if (!$this->indexExists('posts', 'posts_status_index')) {
                $table->index('status', 'posts_status_index');
            }
            // Add index for created_at sorting
            if (!$this->indexExists('posts', 'posts_created_at_index')) {
                $table->index('created_at', 'posts_created_at_index');
            }
            // Note: category_id already has foreign key index
            // Note: title search will use full table scan unless we use FULLTEXT
        });

        // Add indexes to comments table
        Schema::table('comments', function (Blueprint $table) {
            // Add index for is_approved filtering
            if (!$this->indexExists('comments', 'comments_is_approved_index')) {
                $table->index('is_approved', 'comments_is_approved_index');
            }
            // Add index for created_at sorting
            if (!$this->indexExists('comments', 'comments_created_at_index')) {
                $table->index('created_at', 'comments_created_at_index');
            }
            // Add index for author_name search
            if (!$this->indexExists('comments', 'comments_author_name_index')) {
                $table->index('author_name', 'comments_author_name_index');
            }
            // Note: post_id and user_id already have foreign key indexes
        });

        // Add indexes to categories table
        Schema::table('categories', function (Blueprint $table) {
            // Add index for name filtering
            if (!$this->indexExists('categories', 'categories_name_index')) {
                $table->index('name', 'categories_name_index');
            }
            // slug already has unique index from original migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from posts table
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_status_index');
            $table->dropIndex('posts_created_at_index');
        });

        // Remove indexes from comments table
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_is_approved_index');
            $table->dropIndex('comments_created_at_index');
            $table->dropIndex('comments_author_name_index');
        });

        // Remove indexes from categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_name_index');
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $indexData) {
            if ($indexData['name'] === $index) {
                return true;
            }
        }
        return false;
    }
};
