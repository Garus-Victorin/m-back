<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->string('payout_beneficiary_name')->nullable()->after('city');
            $table->string('payout_mobile_money_provider', 20)->nullable()->after('payout_beneficiary_name');
            $table->string('payout_mobile_money_number', 30)->nullable()->after('payout_mobile_money_provider');
            $table->boolean('payouts_enabled')->default(true)->after('payout_mobile_money_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn([
                'payout_beneficiary_name',
                'payout_mobile_money_provider',
                'payout_mobile_money_number',
                'payouts_enabled',
            ]);
        });
    }
};
