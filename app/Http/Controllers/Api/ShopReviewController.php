<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShopReviewRequest;
use App\Http\Requests\UpdateShopReviewRequest;
use App\Http\Resources\ShopReviewResource;
use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopReview;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopReviewController extends Controller
{
    public function index(Request $request, Shop $shop): JsonResponse
    {
        $reviews = ShopReview::query()
            ->where('shop_id', $shop->id)
            ->where('is_approved', true)
            ->with(['customer', 'order'])
            ->latest()
            ->paginate(min(max($request->integer('per_page', 15), 1), 50))
            ->withQueryString();

        $averageRating = ShopReview::where('shop_id', $shop->id)
            ->where('is_approved', true)
            ->avg('rating');

        return response()->json([
            'success' => true,
            'message' => 'Shop reviews retrieved successfully.',
            'data' => [
                'reviews' => ShopReviewResource::collection($reviews->getCollection()),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
                'summary' => [
                    'average_rating' => round($averageRating, 1),
                    'total_reviews' => $reviews->total(),
                ],
            ],
        ]);
    }

    public function store(StoreShopReviewRequest $request, Order $order): JsonResponse
    {
        // Vérifier que la commande appartient au shop du seller
        if ($order->shop_id !== $request->user()->shop->id) {
            return response()->json([
                'success' => false,
                'message' => 'This order does not belong to your shop.',
                'code' => 'UNAUTHORIZED',
            ], 403);
        }

        // Vérifier que la commande est complète
        if ($order->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Only completed orders can be reviewed.',
                'code' => 'ORDER_NOT_COMPLETED',
            ], 409);
        }

        // Vérifier que le client n'a pas déjà laissé un avis
        $existingReview = ShopReview::where('order_id', $order->id)
            ->where('customer_id', $order->customer_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this order.',
                'code' => 'REVIEW_ALREADY_EXISTS',
            ], 409);
        }

        // Créer l'avis
        $review = ShopReview::createForOrder(
            $order,
            $request->input('rating'),
            $request->input('comment'),
            $request->input('images', [])
        );

        Log::info('Shop review created', [
            'review_id' => $review->id,
            'shop_id' => $order->shop_id,
            'order_id' => $order->id,
            'rating' => $review->rating,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shop review created successfully. Waiting for approval.',
            'data' => [
                'review' => ShopReviewResource::make($review),
            ],
        ], 201);
    }

    public function show(Shop $shop, ShopReview $review): JsonResponse
    {
        // Vérifier que l'avis appartient au shop
        if ($review->shop_id !== $shop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found for this shop.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Shop review retrieved successfully.',
            'data' => [
                'review' => ShopReviewResource::make($review),
            ],
        ]);
    }

    public function update(UpdateShopReviewRequest $request, Shop $shop, ShopReview $review): JsonResponse
    {
        // Vérifier que l'avis appartient au shop
        if ($review->shop_id !== $shop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found for this shop.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $review->update([
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
            'images' => $request->input('images', []),
        ]);

        Log::info('Shop review updated', [
            'review_id' => $review->id,
            'shop_id' => $shop->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shop review updated successfully.',
            'data' => [
                'review' => ShopReviewResource::make($review),
            ],
        ]);
    }

    public function destroy(Shop $shop, ShopReview $review): JsonResponse
    {
        // Vérifier que l'avis appartient au shop
        if ($review->shop_id !== $shop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found for this shop.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $review->delete();

        Log::info('Shop review deleted', [
            'review_id' => $review->id,
            'shop_id' => $shop->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shop review deleted successfully.',
        ]);
    }

    public function approve(Shop $shop, ShopReview $review, Request $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        // Vérifier que l'avis appartient au shop
        if ($review->shop_id !== $shop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found for this shop.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $review->approve($admin);

        Log::info('Shop review approved', [
            'review_id' => $review->id,
            'shop_id' => $shop->id,
            'admin_id' => $admin->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shop review approved successfully.',
            'data' => [
                'review' => ShopReviewResource::make($review),
            ],
        ]);
    }

    public function reject(Shop $shop, ShopReview $review, Request $request): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        // Vérifier que l'avis appartient au shop
        if ($review->shop_id !== $shop->id) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found for this shop.',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $review->reject();

        Log::info('Shop review rejected', [
            'review_id' => $review->id,
            'shop_id' => $shop->id,
            'admin_id' => $admin->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shop review rejected successfully.',
            'data' => [
                'review' => ShopReviewResource::make($review),
            ],
        ]);
    }
}
