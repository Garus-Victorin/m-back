<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $request->user()?->role;
        $downloadUrl = match ($role) {
            'seller' => route('seller.products.images.download', ['product' => $this->product_id, 'image' => $this->id], false),
            'admin' => route('admin.products.images.download', ['product' => $this->product_id, 'image' => $this->id], false),
            default => null,
        };

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'disk' => $this->disk,
            'path' => $this->path,
            'position' => $this->position,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'download_url' => $downloadUrl,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
