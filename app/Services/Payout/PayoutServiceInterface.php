<?php

namespace App\Services\Payout;

use App\Models\SellerWithdrawalRequest;

interface PayoutServiceInterface
{
    public function processWithdrawal(SellerWithdrawalRequest $withdrawal): array;

    public function checkStatus(string $transactionId): array;

    public function handleCallback(array $payload): array;

    public function generateCallbackUrl(SellerWithdrawalRequest $withdrawal): string;
}
