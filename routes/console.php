<?php

use App\Console\Commands\ProcessPendingWithdrawals;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command("inspire", function () {
    $this->comment(Inspiring::quote());
})->purpose("Display an inspiring quote");

Artisan::command("withdrawals:process-pending", function () {
    $this->call(ProcessPendingWithdrawals::class);
})->purpose("Process pending seller withdrawal requests");

Artisan::command("marketify:cleanup-orphan-images", function () {
    $this->call(CleanupOrphanProductImages::class);
})->purpose(
    "Clean up orphan product images (images without associated product)",
);
