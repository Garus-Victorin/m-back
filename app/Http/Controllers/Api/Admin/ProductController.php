<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Admin\ReviewSellerProductAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewProductRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $query = Product::query()->with(['shop', 'category', 'reviewer', 'images', 'variants'])->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($moderationStatus = $request->string('moderation_status')->toString()) {
            $query->where('moderation_status', $moderationStatus);
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

        $product->load(['shop', 'category', 'reviewer', 'images', 'variants']);

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
        /** @var User $admin */
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $status = $request->input('status');
        $isPublished = $status === 'published';
        $isArchived = $status === 'archived';

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
            'status' => $status,
            'moderation_status' => $isPublished ? 'approved' : 'draft',
            'submitted_for_review_at' => $isPublished ? now() : null,
            'reviewed_by' => $isPublished ? $admin->id : null,
            'reviewed_at' => $isPublished ? now() : null,
            'rejection_reason' => null,
            'archived_at' => $isArchived ? now() : null,
            'is_active' => $isArchived ? false : $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin product created successfully.',
            'data' => [
                'product' => ProductResource::make($product->load(['shop', 'category', 'reviewer', 'images', 'variants'])),
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

        if (($attributes['status'] ?? $product->status) === 'archived') {
            $attributes['archived_at'] = $product->archived_at ?? now();
            $attributes['is_active'] = false;
            $attributes['moderation_status'] = 'draft';
        }

        if (($attributes['status'] ?? $product->status) === 'draft' && $product->status === 'archived') {
            $attributes['archived_at'] = null;
            $attributes['moderation_status'] = 'draft';
            $attributes['is_active'] = true;
        }

        if (($attributes['status'] ?? $product->status) === 'published') {
            $attributes['moderation_status'] = 'approved';
            $attributes['reviewed_by'] = $request->user()?->id;
            $attributes['reviewed_at'] = now();
            $attributes['is_active'] = true;
            $attributes['archived_at'] = null;
            $attributes['rejection_reason'] = null;
        }

        $product->update($attributes);

        return response()->json([
            'success' => true,
            'message' => 'Admin product updated successfully.',
            'data' => [
                'product' => ProductResource::make($product->fresh(['shop', 'category', 'reviewer', 'images', 'variants'])),
            ],
        ]);
    }

    public function review(
        ReviewProductRequest $request,
        Product $product,
        ReviewSellerProductAction $action,
    ): JsonResponse {
        /** @var User $admin */
        $admin = $request->user();
        $this->ensureAdmin($admin);

        $product = $action->execute($admin, $product, $request->validated(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Admin product reviewed successfully.',
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ]);
    }

    public function downloadImage(Request $request, Product $product, ProductImage $image): StreamedResponse
    {
        $this->ensureAdmin($request->user());
        abort_unless($image->product_id === $product->id, 404, 'Product image not found.');
        abort_unless(Storage::disk($image->disk)->exists($image->path), 404, 'Product image file not found.');

        return Storage::disk($image->disk)->download($image->path, basename($image->path));
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->ensureAdmin($request->user());

        $product->update([
            'status' => 'archived',
            'moderation_status' => 'draft',
            'archived_at' => now(),
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin product archived successfully.',
            'data' => [
                'product' => ProductResource::make($product->fresh(['shop', 'category', 'reviewer', 'images', 'variants'])),
            ],
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
