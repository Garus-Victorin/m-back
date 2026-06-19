<?php

namespace App\Http\Controllers\Api\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Actions\Seller\MarkSellerOrderReadyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\CancelSellerOrderRequest;
use App\Http\Resources\Seller\SellerOrderResource;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $orders = $shop
            ->orders()
            ->with(["items.product", "deliveryAddress"])
            ->latest()
            ->paginate(min(max($request->integer("per_page", 15), 1), 50))
            ->withQueryString();

        return response()->json([
            "success" => true,
            "message" => "Seller orders retrieved successfully.",
            "data" => [
                "orders" => SellerOrderResource::collection(
                    $orders->getCollection(),
                ),
                "pagination" => [
                    "current_page" => $orders->currentPage(),
                    "last_page" => $orders->lastPage(),
                    "per_page" => $orders->perPage(),
                    "total" => $orders->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $this->authorize("view", $order);

        $order->load(["items.product", "deliveryAddress"]);

        return response()->json([
            "success" => true,
            "message" => "Seller order retrieved successfully.",
            "data" => [
                "order" => SellerOrderResource::make($order),
            ],
        ]);
    }

    public function markReady(
        Request $request,
        Order $order,
        MarkSellerOrderReadyAction $action,
        RecordAuditLogAction $auditLog,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $this->authorize("update", $order);

        $result = $action->execute($order, $user);

        if ($result["success"]) {
            $auditLog->execute(
                action: "seller.order.marked_ready",
                actor: $user,
                target: $order,
                after: ["status" => $order->refresh()->status],
                request: $request,
            );

            return response()->json([
                "success" => true,
                "message" => "Order marked as ready successfully.",
                "data" => ["order" => SellerOrderResource::make($order)],
            ]);
        }

        return response()->json(
            [
                "success" => false,
                "message" =>
                    $result["error"] ?? "Failed to mark order as ready.",
            ],
            409,
        );
    }

    public function cancelRequest(
        CancelSellerOrderRequest $request,
        Order $order,
        RecordAuditLogAction $auditLog,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $this->authorize("update", $order);

        // Vérifier que la commande peut être annulée
        if (!in_array($order->status, ["pending", "processing", "paid"])) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "This order cannot be cancelled at this stage.",
                    "code" => "ORDER_CANCELLATION_NOT_ALLOWED",
                ],
                409,
            );
        }

        // Mettre à jour le statut et enregistrer la raison
        $order->update([
            "status" => "cancel_requested",
            "cancel_requested_by" => "seller",
            "cancel_requested_at" => now(),
            "cancel_reason" => $request->input(
                "reason",
                "Seller requested cancellation",
            ),
        ]);

        $auditLog->execute(
            action: "seller.order.cancel_requested",
            actor: $user,
            target: $order,
            after: [
                "status" => $order->status,
                "cancel_reason" => $order->cancel_reason,
            ],
            request: $request,
        );

        Log::info("Seller order cancellation requested", [
            "order_id" => $order->id,
            "shop_id" => $shop->id,
            "user_id" => $user->id,
            "reason" => $order->cancel_reason,
        ]);

        return response()->json([
            "success" => true,
            "message" =>
                "Order cancellation requested successfully. Waiting for admin review.",
            "data" => ["order" => SellerOrderResource::make($order)],
        ]);
    }

    public function packingSlip(Request $request, Order $order): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $shop = $this->sellerShop($user);

        $this->authorize("view", $order);

        $order->load(["items.product", "deliveryAddress", "shop"]);

        // Générer le bon de préparation au format simple
        $packingSlip = [
            "order" => [
                "id" => $order->id,
                "reference" => $order->reference,
                "created_at" => $order->created_at->format("Y-m-d H:i:s"),
                "status" => $order->status,
            ],
            "shop" => [
                "name" => $order->shop->name,
                "email" => $order->shop->email,
                "phone" => $order->shop->phone,
            ],
            "customer" => [
                "name" => $order->deliveryAddress->full_name,
                "phone" => $order->deliveryAddress->phone,
                "email" => $order->customer_email,
            ],
            "delivery_address" => [
                "address_line_1" => $order->deliveryAddress->address_line_1,
                "address_line_2" => $order->deliveryAddress->address_line_2,
                "city" => $order->deliveryAddress->city,
                "state" => $order->deliveryAddress->state,
                "country" => $order->deliveryAddress->country,
                "postal_code" => $order->deliveryAddress->postal_code,
            ],
            "items" => $order->items->map(function ($item) {
                return [
                    "product_name" => $item->product->name,
                    "variant" => $item->variant_name ?? "N/A",
                    "sku" => $item->sku,
                    "quantity" => $item->quantity,
                    "unit_price" => $item->unit_price_cents / 100,
                    "total_price" => $item->total_price_cents / 100,
                ];
            }),
            "totals" => [
                "subtotal" => $order->subtotal_cents / 100,
                "delivery_fee" => $order->delivery_fee_cents / 100,
                "total" => $order->total_cents / 100,
                "currency" => $order->currency,
            ],
        ];

        $auditLog->execute(
            action: "seller.order.packing_slip_generated",
            actor: $user,
            target: $order,
            request: $request,
        );

        return response()->json([
            "success" => true,
            "message" => "Packing slip generated successfully.",
            "data" => [
                "packing_slip" => $packingSlip,
                "format" => "json",
                "generated_at" => now()->toDateTimeString(),
            ],
        ]);
    }

    protected function sellerShop(User $user): Shop
    {
        return $user->shop()->firstOrFail();
    }
}
