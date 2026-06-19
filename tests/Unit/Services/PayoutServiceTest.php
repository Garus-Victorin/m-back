<?php

namespace Tests\Unit\Services;

use App\Services\Payout\MobileMoneyPayoutService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PayoutServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_instantiate_payout_service(): void
    {
        $this->markTestIncomplete('This test needs proper config setup');

    public function test_generates_callback_url(): void
    {
        $this->markTestIncomplete('This test needs proper config setup');

    public function test_process_withdrawal_makes_http_request(): void
    {
        $this->markTestIncomplete('This test needs proper config setup');
}
