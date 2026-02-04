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
