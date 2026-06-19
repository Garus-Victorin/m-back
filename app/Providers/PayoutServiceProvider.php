<?php

namespace App\Providers;

use App\Services\Payout\MobileMoneyPayoutService;
use App\Services\Payout\PayoutServiceInterface;
use Illuminate\Support\ServiceProvider;

class PayoutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PayoutServiceInterface::class, function () {
            return new MobileMoneyPayoutService();
        });
    }

    public function boot(): void
    {
        //
    }
}
