<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'attribute_name' => $this->attribute_name,
            'attribute_value' => $this->attribute_value,
            'sku' => $this->sku,
            'extra_price' => $this->extra_price,
            'stock' => $this->stock,
            'reserved_stock' => $this->reserved_stock,
            'available_stock' => max(0, $this->stock - $this->reserved_stock),
            'is_active' => $this->is_active,
            'position' => $this->position,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
