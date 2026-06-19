<?php

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopBrandingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'logo_url' => $this->logo_url,
            'banner_url' => $this->banner_url,
            'has_logo' => (bool) $this->logo_url,
            'has_banner' => (bool) $this->banner_url,
        ];
    }
}
