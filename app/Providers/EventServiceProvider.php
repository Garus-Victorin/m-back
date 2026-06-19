<?php

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Events\OrderPaid;
use App\Listeners\OrderCancelledListener;
use App\Listeners\OrderPaidListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPaid::class => [
            OrderPaidListener::class,
        ],
        OrderCancelled::class => [
            OrderCancelledListener::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
