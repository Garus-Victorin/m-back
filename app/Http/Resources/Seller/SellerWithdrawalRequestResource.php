<?php

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerWithdrawalRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'user_id' => $this->user_id,
            'amount_cents' => $this->amount_cents,
            'currency' => $this->currency,
            'mobile_money_provider' => $this->mobile_money_provider,
            'mobile_money_number' => $this->mobile_money_number,
            'status' => $this->status,
            'idempotency_key' => $this->idempotency_key,
            'processed_by' => $this->processed_by,
            'processed_at' => $this->processed_at,
            'failure_reason' => $this->failure_reason,
            'provider_reference' => $this->provider_reference,
            'shop' => $this->whenLoaded('shop', fn (): array => [
                'id' => $this->shop->id,
                'name' => $this->shop->name,
                'slug' => $this->shop->slug,
                'status' => $this->shop->status,
            ]),
            'user' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $this->user->role,
            ]),
            'processor' => $this->whenLoaded('processor', fn (): ?array => $this->processor ? [
                'id' => $this->processor->id,
                'name' => $this->processor->name,
                'email' => $this->processor->email,
            ] : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
