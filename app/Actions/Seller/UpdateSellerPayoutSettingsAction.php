<?php

namespace App\Actions\Seller;

use App\Actions\Audit\RecordAuditLogAction;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UpdateSellerPayoutSettingsAction
{
    public function __construct(
        protected RecordAuditLogAction $auditLog,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(User $user, Shop $shop, array $attributes, ?Request $request = null): Shop
    {
        $before = $this->auditSnapshot($shop);

        $shop->fill(Arr::only($attributes, [
            'payout_beneficiary_name',
            'payout_mobile_money_provider',
            'payout_mobile_money_number',
            'payouts_enabled',
        ]));

        $this->ensureConsistentPayoutConfiguration($shop);

        $shop->save();
        $shop = $shop->fresh();

        $this->auditLog->execute(
            action: 'seller.settings.updated',
            actor: $user,
            target: $shop,
            before: $before,
            after: $this->auditSnapshot($shop),
            request: $request,
        );

        return $shop;
    }

    protected function ensureConsistentPayoutConfiguration(Shop $shop): void
    {
        if (! $shop->payouts_enabled) {
            return;
        }

        $missingFields = [];

        if (blank($shop->payout_beneficiary_name)) {
            $missingFields['payout_beneficiary_name'] = ['The payout beneficiary name is required when payouts are enabled.'];
        }

        if (blank($shop->payout_mobile_money_provider)) {
            $missingFields['payout_mobile_money_provider'] = ['The payout mobile money provider is required when payouts are enabled.'];
        }

        if (blank($shop->payout_mobile_money_number)) {
            $missingFields['payout_mobile_money_number'] = ['The payout mobile money number is required when payouts are enabled.'];
        }

        if ($missingFields !== []) {
            throw ValidationException::withMessages($missingFields);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function auditSnapshot(Shop $shop): array
    {
        return [
            'payout_beneficiary_name' => $shop->payout_beneficiary_name,
            'payout_mobile_money_provider' => $shop->payout_mobile_money_provider,
            'payout_mobile_money_number' => $shop->payout_mobile_money_number,
            'payouts_enabled' => (bool) $shop->payouts_enabled,
        ];
    }
}
