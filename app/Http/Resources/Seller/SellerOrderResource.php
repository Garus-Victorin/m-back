<?php

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'total_cents' => $this->total_cents,
            'currency' => $this->currency,
            'customer_email' => $this->customer_email,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown',
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unit_price_cents,
                    'total_price_cents' => $item->total_price_cents,
                ];
            }),
            'delivery_address' => $this->deliveryAddress ? [
                'full_name' => $this->deliveryAddress->full_name,
                'phone' => $this->deliveryAddress->phone,
                'address_line_1' => $this->deliveryAddress->address_line_1,
                'city' => $this->deliveryAddress->city,
                'country' => $this->deliveryAddress->country,
            ] : null,
        ];
    }
}
