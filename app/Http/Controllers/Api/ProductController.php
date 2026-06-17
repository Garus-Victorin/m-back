<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $query = Product::query()
            ->with(['shop', 'category', 'images'])
            ->where('status', 'published')
            ->where('moderation_status', 'approved')
            ->where('is_active', true)
            ->whereHas('shop', fn (Builder $builder) => $builder->where('status', 'active'));

        if ($search = request()->string('search')->toString()) {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = request()->string('category')->toString()) {
            $query->whereHas('category', fn (Builder $builder) => $builder
                ->where('slug', $category)
                ->where('is_active', true));
        }

        if ($shop = request()->string('shop')->toString()) {
            $query->whereHas('shop', fn (Builder $builder) => $builder
                ->where('slug', $shop)
                ->where('status', 'active'));
        }

        match (request()->string('sort', 'latest')->toString()) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        $perPage = min(max(request()->integer('per_page', 15), 1), 50);
        $products = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
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

    public function show(Product $product): JsonResponse
    {
        $product->load(['shop', 'category', 'images']);

        if (
            $product->status !== 'published'
            || $product->moderation_status !== 'approved'
            || ! $product->is_active
            || $product->shop->status !== 'active'
        ) {
            throw new NotFoundHttpException('Product not found.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data' => [
                'product' => ProductResource::make($product),
            ],
        ]);
    }
}
