<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\StoreProductRequest;
use App\Http\Requests\Seller\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureSeller($user);
        $shop = $this->sellerShop($user);

        $products = $shop->products()
            ->with(['shop', 'category'])
            ->latest()
            ->paginate(min(max($request->integer('per_page', 15), 1), 50))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Seller products retrieved successfully.',
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

    public function store(StoreProductRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureSeller($user);
        $shop = $this->sellerShop($user);

        $product = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $request->input('category_id'),
            'name' => $request->string('name')->toString(),
            'slug' => $this->generateUniqueSlug($request->string('name')->toString()),
            'sku' => $request->input('sku'),
            'short_description' => $request->input('short_description'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'stock' => $request->integer('stock'),
            'status' => $request->input('status'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $product->load(['shop', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ], 201);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureSeller($user);
        $this->ensureOwnProduct($user, $product);

        $attributes = $request->validated();

        if (array_key_exists('name', $attributes) && $attributes['name'] !== $product->name) {
            $attributes['slug'] = $this->generateUniqueSlug($attributes['name'], $product->id);
        }

        $product->update($attributes);
        $product->load(['shop', 'category']);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => [
                'product' => ProductResource::make($product->fresh(['shop', 'category'])),
            ],
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureSeller($user);
        $this->ensureOwnProduct($user, $product);

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    protected function ensureSeller(User $user): void
    {
        abort_unless($user->role === 'seller', 403, 'Only sellers can perform this action.');
        abort_unless($user->is_active, 403, 'Seller account is inactive.');
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }

    protected function ensureOwnProduct(User $user, Product $product): void
    {
        abort_unless($product->shop->user_id === $user->id, 403, 'You are not allowed to manage this product.');
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
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
