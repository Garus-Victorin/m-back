<?php

namespace App\Jobs;

use App\Actions\Seller\ProcessSellerWithdrawalAction;
use App\Models\SellerWithdrawalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessSellerWithdrawalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected SellerWithdrawalRequest $withdrawal,
    ) {
    }

    public function handle(ProcessSellerWithdrawalAction $action): void
    {
        $action->execute($this->withdrawal);
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }

    public function maxExceptions(): int
    {
        return 3;
    }
}
