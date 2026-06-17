<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Seller\MarkSellerOrderReadyAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $orders = $shop->orders()
            ->with(['customer', 'items.product', 'items.productVariant', 'deliveryAddress'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('search'), fn ($query) => $query->where('order_number', 'like', '%'.$request->string('search')->toString().'%'))
            ->latest()
            ->paginate(min(max($request->integer('per_page', 15), 1), 50))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Seller orders retrieved successfully.',
            'data' => [
                'orders' => OrderResource::collection($orders->getCollection()),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['customer', 'items.product', 'items.productVariant', 'deliveryAddress', 'shop']);

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => [
                'order' => OrderResource::make($order),
            ],
        ]);
    }

    public function markReady(Request $request, Order $order, MarkSellerOrderReadyAction $action): JsonResponse
    {
        $this->authorize('update', $order);

        $order = $action->execute($order);

        return response()->json([
            'success' => true,
            'message' => 'Order marked ready for pickup successfully.',
            'data' => [
                'order' => OrderResource::make($order),
            ],
        ]);
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }
}
