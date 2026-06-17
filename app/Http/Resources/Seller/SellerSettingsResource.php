<?php

namespace App\Http\Resources\Seller;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasCompletePayoutSettings = filled($this->payout_beneficiary_name)
            && filled($this->payout_mobile_money_provider)
            && filled($this->payout_mobile_money_number);

        return [
            'shop_id' => $this->id,
            'payout_beneficiary_name' => $this->payout_beneficiary_name,
            'payout_mobile_money_provider' => $this->payout_mobile_money_provider,
            'payout_mobile_money_number' => $this->payout_mobile_money_number,
            'payouts_enabled' => (bool) $this->payouts_enabled,
            'has_complete_payout_settings' => $hasCompletePayoutSettings,
        ];
    }
}
