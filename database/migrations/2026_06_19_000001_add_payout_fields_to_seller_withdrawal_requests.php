<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_withdrawal_requests', function (Blueprint $table) {
            $table->string('provider_transaction_id')->nullable()->after('provider_reference');
            $table->string('provider_status')->nullable()->after('provider_transaction_id');
            $table->text('provider_response')->nullable()->after('provider_status');
            $table->timestamp('provider_processed_at')->nullable()->after('provider_response');
            $table->string('callback_url')->nullable()->after('provider_processed_at');
            $table->integer('retry_count')->default(0)->after('callback_url');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count');
        });
    }

    public function down(): void
    {
        Schema::table('seller_withdrawal_requests', function (Blueprint $table) {
            $table->dropColumn([
                'provider_transaction_id',
                'provider_status',
                'provider_response',
                'provider_processed_at',
                'callback_url',
                'retry_count',
                'next_retry_at',
            ]);
        });
    }
};
