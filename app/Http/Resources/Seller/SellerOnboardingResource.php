<?php

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOnboardingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'kyc_status' => $this->kyc_status,
            'kyc_submitted_at' => $this->kyc_submitted_at?->toDateTimeString(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
