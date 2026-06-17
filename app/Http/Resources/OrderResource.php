<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_id' => $this->customer_id,
            'shop_id' => $this->shop_id,
            'delivery_method' => $this->delivery_method,
            'otp_code' => $this->otp_code,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'delivery_fee' => $this->delivery_fee,
            'total' => $this->total,
            'platform_commission' => $this->platform_commission,
            'seller_amount' => $this->seller_amount,
            'delivery_amount' => $this->delivery_amount,
            'payment_details' => $this->payment_details,
            'paid_at' => $this->paid_at,
            'inventory_reserved_at' => $this->inventory_reserved_at,
            'inventory_committed_at' => $this->inventory_committed_at,
            'inventory_released_at' => $this->inventory_released_at,
            'prepared_at' => $this->prepared_at,
            'picked_up_at' => $this->picked_up_at,
            'delivered_at' => $this->delivered_at,
            'cancelled_at' => $this->cancelled_at,
            'cancel_reason' => $this->cancel_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ]),

            'shop' => $this->whenLoaded('shop', fn () => [
                'id' => $this->shop->id,
                'name' => $this->shop->name,
                'slug' => $this->shop->slug,
            ]),

            'delivery_address' => $this->whenLoaded('deliveryAddress', fn () => $this->deliveryAddress ? [
                'id' => $this->deliveryAddress->id,
                'name' => $this->deliveryAddress->name,
                'phone' => $this->deliveryAddress->phone,
                'address_line1' => $this->deliveryAddress->address_line1,
                'address_line2' => $this->deliveryAddress->address_line2,
                'city' => $this->deliveryAddress->city,
                'state' => $this->deliveryAddress->state,
                'postal_code' => $this->deliveryAddress->postal_code,
                'country' => $this->deliveryAddress->country,
                'latitude' => $this->deliveryAddress->latitude,
                'longitude' => $this->deliveryAddress->longitude,
            ] : null),

            'items' => $this->whenLoaded('items', fn () => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'total_price' => $item->total_price,
                    'variants' => $item->variants,
                    'product_variant' => $item->productVariant ? [
                        'id' => $item->productVariant->id,
                        'attribute_name' => $item->productVariant->attribute_name,
                        'attribute_value' => $item->productVariant->attribute_value,
                        'sku' => $item->productVariant->sku,
                    ] : null,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                        'sku' => $item->product->sku,
                    ] : null,
                ];
            })),
        ];
    }
}
