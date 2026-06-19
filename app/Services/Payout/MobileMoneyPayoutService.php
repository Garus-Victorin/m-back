<?php

namespace App\Services\Payout;

use App\Models\SellerWithdrawalRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobileMoneyPayoutService implements PayoutServiceInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $merchantId;

    public function __construct()
    {
        $this->baseUrl = config('services.mobile_money.base_url');
        $this->apiKey = config('services.mobile_money.api_key');
        $this->merchantId = config('services.mobile_money.merchant_id');
    }

    public function processWithdrawal(SellerWithdrawalRequest $withdrawal): array
    {
        $callbackUrl = $this->generateCallbackUrl($withdrawal);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($this->baseUrl . '/api/v1/payouts', [
            'merchant_id' => $this->merchantId,
            'transaction_id' => Str::uuid()->toString(),
            'amount' => $withdrawal->amount_cents / 100,
            'currency' => $withdrawal->currency,
            'provider' => $withdrawal->mobile_money_provider,
            'number' => $withdrawal->mobile_money_number,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'withdrawal_id' => $withdrawal->id,
                'shop_id' => $withdrawal->shop_id,
            ],
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'transaction_id' => $response->json('data.transaction_id'),
                'status' => $response->json('data.status'),
                'message' => $response->json('message'),
            ];
        }

        Log::error('Mobile Money Payout Failed', [
            'withdrawal_id' => $withdrawal->id,
            'response_status' => $response->status(),
            'response_body' => $response->body(),
        ]);

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Payout processing failed',
            'status_code' => $response->status(),
        ];
    }

    public function checkStatus(string $transactionId): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Accept' => 'application/json',
        ])->get($this->baseUrl . "/api/v1/payouts/{$transactionId}/status");

        if ($response->successful()) {
            return [
                'success' => true,
                'status' => $response->json('data.status'),
                'data' => $response->json('data'),
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Status check failed',
        ];
    }

    public function handleCallback(array $payload): array
    {
        Log::info('Mobile Money Payout Callback Received', $payload);

        return [
            'success' => true,
            'message' => 'Callback processed successfully',
        ];
    }

    public function generateCallbackUrl(SellerWithdrawalRequest $withdrawal): string
    {
        return route('api.seller.payout.callback', [
            'withdrawal' => $withdrawal->id,
            'key' => $withdrawal->idempotency_key,
        ]);
    }
}
