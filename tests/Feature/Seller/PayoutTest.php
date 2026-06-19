<?php

namespace Tests\Feature\Seller;

use App\Jobs\ProcessSellerWithdrawalJob;
use App\Models\SellerWithdrawalRequest;
use App\Models\Shop;
use App\Models\User;
use App\Services\Payout\PayoutServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Log::shouldReceive("info")->andReturnNull();
        Log::shouldReceive("error")->andReturnNull();
    }

    public function test_can_process_withdrawal_with_mock_payout_service(): void
    {
        $this->markTestIncomplete('This test needs to be implemented with proper authentication setup');

    public function test_cannot_process_other_sellers_withdrawal(): void
    {
        $this->markTestIncomplete('This test needs to be implemented with proper authentication setup');

    public function test_payout_callback_handling(): void
    {
        $this->markTestIncomplete('This test needs to be implemented with proper authentication setup');

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
