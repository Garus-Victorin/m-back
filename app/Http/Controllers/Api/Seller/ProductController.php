<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Actions\Seller\ReorderProductImagesAction;
use App\Actions\Seller\ReplaceProductVariantsAction;
use App\Actions\Seller\StoreProductImageAction;
use App\Actions\Seller\SubmitSellerProductForReviewAction;
use App\Actions\Seller\UpdateSellerProductStockAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ReorderProductImagesRequest;
use App\Http\Requests\Seller\ReplaceProductVariantsRequest;
use App\Http\Requests\Seller\StoreProductImageRequest;
use App\Http\Requests\Seller\StoreProductRequest;
use App\Http\Requests\Seller\UpdateProductRequest;
use App\Http\Requests\Seller\UpdateProductStockRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProductController extends Controller
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $products = $shop->products()
            ->with(['shop', 'category', 'reviewer', 'images', 'variants'])
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

    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product->load(['shop', 'category', 'reviewer', 'images', 'variants']);

        return response()->json([
            'success' => true,
            'message' => 'Seller product retrieved successfully.',
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);
        $validated = $request->validated();

        $product = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $validated['category_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'sku' => $validated['sku'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'status' => 'draft',
            'moderation_status' => 'draft',
            'is_active' => true,
        ]);

        $this->auditLog->execute(
            action: 'seller.product.created',
            actor: $user,
            target: $product,
            after: [
                'status' => $product->status,
                'moderation_status' => $product->moderation_status,
                'price' => $product->price,
                'stock' => $product->stock,
            ],
            request: $request,
        );

        $product->load(['shop', 'category', 'reviewer', 'images', 'variants']);

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
        $this->authorize('update', $product);

        $attributes = $request->validated();
        $before = [
            'name' => $product->name,
            'price' => $product->price,
            'stock' => $product->stock,
            'status' => $product->status,
            'moderation_status' => $product->moderation_status,
        ];

        if (array_key_exists('name', $attributes) && $attributes['name'] !== $product->name) {
            $attributes['slug'] = $this->generateUniqueSlug($attributes['name'], $product->id);
        }

        unset($attributes['status']);
        $attributes['status'] = 'draft';

        if ($product->moderation_status === 'approved' || $product->moderation_status === 'suspended') {
            $attributes['moderation_status'] = 'draft';
            $attributes['submitted_for_review_at'] = null;
            $attributes['reviewed_by'] = null;
            $attributes['reviewed_at'] = null;
            $attributes['rejection_reason'] = null;
            $attributes['is_active'] = true;
        }

        $product->update($attributes);

        $this->auditLog->execute(
            action: 'seller.product.updated',
            actor: $request->user(),
            target: $product,
            before: $before,
            after: [
                'name' => $product->fresh()->name,
                'price' => $product->fresh()->price,
                'stock' => $product->fresh()->stock,
                'status' => $product->fresh()->status,
                'moderation_status' => $product->fresh()->moderation_status,
            ],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => [
                'product' => ProductResource::make($product->fresh(['shop', 'category', 'reviewer', 'images', 'variants'])),
            ],
        ]);
    }

    public function submitReview(Request $request, Product $product, SubmitSellerProductForReviewAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('update', $product);

        $product = $action->execute($user, $product->loadMissing('shop', 'images', 'category', 'variants'), $request);

        return response()->json([
            'success' => true,
            'message' => 'Product submitted for review successfully.',
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ]);
    }

    public function updateStock(
        UpdateProductStockRequest $request,
        Product $product,
        UpdateSellerProductStockAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('update', $product);

        $product = $action->execute($user, $product, $request->integer('stock'), $request);

        return response()->json([
            'success' => true,
            'message' => 'Product stock updated successfully.',
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ]);
    }

    public function replaceVariants(
        ReplaceProductVariantsRequest $request,
        Product $product,
        ReplaceProductVariantsAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('update', $product);

        $product = $action->execute($user, $product, $request->validated('variants'), $request);

        return response()->json([
            'success' => true,
            'message' => 'Product variants replaced successfully.',
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ]);
    }

    public function archive(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        if ($product->status === 'archived') {
            throw new ConflictHttpException('This product is already archived.');
        }

        $before = [
            'status' => $product->status,
            'moderation_status' => $product->moderation_status,
            'archived_at' => $product->archived_at?->toISOString(),
        ];

        $product->update([
            'status' => 'archived',
            'moderation_status' => 'draft',
            'archived_at' => now(),
            'submitted_for_review_at' => null,
            'is_active' => false,
        ]);

        $this->auditLog->execute(
            action: 'seller.product.archived',
            actor: $request->user(),
            target: $product,
            before: $before,
            after: [
                'status' => $product->fresh()->status,
                'moderation_status' => $product->fresh()->moderation_status,
                'archived_at' => $product->fresh()->archived_at?->toISOString(),
            ],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Product archived successfully.',
            'data' => [
                'product' => ProductResource::make($product->fresh(['shop', 'category', 'reviewer', 'images', 'variants'])),
            ],
        ]);
    }

    public function restore(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        if ($product->status !== 'archived') {
            throw new ConflictHttpException('Only archived products can be restored.');
        }

        $product->update([
            'status' => 'draft',
            'moderation_status' => 'draft',
            'archived_at' => null,
            'is_active' => true,
            'submitted_for_review_at' => null,
        ]);

        $this->auditLog->execute(
            action: 'seller.product.restored',
            actor: $request->user(),
            target: $product,
            after: [
                'status' => $product->fresh()->status,
                'moderation_status' => $product->fresh()->moderation_status,
                'archived_at' => null,
            ],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Product restored successfully.',
            'data' => [
                'product' => ProductResource::make($product->fresh(['shop', 'category', 'reviewer', 'images', 'variants'])),
            ],
        ]);
    }

    public function storeImage(StoreProductImageRequest $request, Product $product, StoreProductImageAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('manageImages', $product);

        $image = $action->execute($user, $product, $request->file('file'), $request);

        return response()->json([
            'success' => true,
            'message' => 'Product image uploaded successfully.',
            'data' => [
                'image' => ProductImageResource::make($image),
            ],
        ], 201);
    }

    public function reorderImages(
        ReorderProductImagesRequest $request,
        Product $product,
        ReorderProductImagesAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('manageImages', $product);

        $product = $action->execute($user, $product, $request->validated('image_ids'), $request);

        return response()->json([
            'success' => true,
            'message' => 'Product images reordered successfully.',
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ]);
    }

    public function destroyImage(Request $request, Product $product, ProductImage $image): JsonResponse
    {
        $this->authorize('manageImages', $product);
        $this->ensureImageBelongsToProduct($product, $image);

        $deletedImageId = $image->id;
        $deletedPosition = $image->position;

        if (Storage::disk($image->disk)->exists($image->path)) {
            Storage::disk($image->disk)->delete($image->path);
        }

        $image->delete();

        $product->images()
            ->where('position', '>', $deletedPosition)
            ->decrement('position');

        $this->auditLog->execute(
            action: 'seller.product_image.deleted',
            actor: $request->user(),
            target: $product,
            after: [
                'product_image_id' => $deletedImageId,
                'deleted_position' => $deletedPosition,
                'remaining_images' => $product->images()->orderBy('position')->get(['id', 'position'])->toArray(),
            ],
            request: $request,
        );

        return response()->json([
            'success' => true,
            'message' => 'Product image deleted successfully.',
        ]);
    }

    public function downloadImage(Request $request, Product $product, ProductImage $image): StreamedResponse
    {
        $this->authorize('manageImages', $product);
        $this->ensureImageBelongsToProduct($product, $image);

        abort_unless(Storage::disk($image->disk)->exists($image->path), 404, 'Product image file not found.');

        return Storage::disk($image->disk)->download($image->path, basename($image->path));
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        return $this->archive($request, $product);
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }

    protected function ensureImageBelongsToProduct(Product $product, ProductImage $image): void
    {
        abort_unless($image->product_id === $product->id, 404, 'Product image not found.');
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
