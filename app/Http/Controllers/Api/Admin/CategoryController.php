<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $categories = Category::query()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Admin categories retrieved successfully.',
            'data' => [
                'categories' => CategoryResource::collection($categories),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $category = Category::create([
            'name' => $request->string('name')->toString(),
            'slug' => $this->generateUniqueSlug(
                $request->input('slug') ?: $request->string('name')->toString()
            ),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => [
                'category' => CategoryResource::make($category),
            ],
        ], 201);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $attributes = $request->validated();

        if (array_key_exists('slug', $attributes) && filled($attributes['slug'])) {
            $attributes['slug'] = $this->generateUniqueSlug($attributes['slug'], $category->id);
        } elseif (array_key_exists('name', $attributes) && $attributes['name'] !== $category->name) {
            $attributes['slug'] = $this->generateUniqueSlug($attributes['name'], $category->id);
        }

        $category->update($attributes);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => [
                'category' => CategoryResource::make($category->fresh()),
            ],
        ]);
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }

    protected function ensureAdmin(?User $user): void
    {
        abort_unless($user && $user->role === 'admin', 403, 'Only admins can perform this action.');
        abort_unless($user->is_active, 403, 'Admin account is inactive.');
    }

    protected function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $counter = 1;

        while (Category::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
