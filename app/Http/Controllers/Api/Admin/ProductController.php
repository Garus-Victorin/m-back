<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $query = Product::query()->with(['shop', 'category'])->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($shopId = $request->integer('shop_id')) {
            $query->where('shop_id', $shopId);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $products = $query
            ->paginate(min(max($request->integer('per_page', 15), 1), 50))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Admin products retrieved successfully.',
            'data' => [
                'products' => ProductResource::collection($products->getCollection()),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $product->load(['shop', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Admin product retrieved successfully.',
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $product = Product::create([
            'shop_id' => $request->integer('shop_id'),
            'category_id' => $request->input('category_id'),
            'name' => $request->string('name')->toString(),
            'slug' => $this->generateUniqueSlug(
                $request->input('slug') ?: $request->string('name')->toString()
            ),
            'sku' => $request->input('sku'),
            'short_description' => $request->input('short_description'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'stock' => $request->integer('stock'),
            'status' => $request->input('status'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin product created successfully.',
            'data' => [
                'product' => ProductResource::make($product->load(['shop', 'category'])),
            ],
        ], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $attributes = $request->validated();

        if (array_key_exists('slug', $attributes) && filled($attributes['slug'])) {
            $attributes['slug'] = $this->generateUniqueSlug($attributes['slug'], $product->id);
        } elseif (array_key_exists('name', $attributes) && $attributes['name'] !== $product->name) {
            $attributes['slug'] = $this->generateUniqueSlug($attributes['name'], $product->id);
        }

        $product->update($attributes);

        return response()->json([
            'success' => true,
            'message' => 'Admin product updated successfully.',
            'data' => [
                'product' => ProductResource::make($product->fresh(['shop', 'category'])),
            ],
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Admin product deleted successfully.',
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

        while (Product::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
