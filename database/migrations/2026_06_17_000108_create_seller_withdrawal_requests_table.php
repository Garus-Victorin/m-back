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
        Schema::create('seller_withdrawal_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('XOF');
            $table->string('mobile_money_provider', 20);
            $table->string('mobile_money_number', 30);
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'rejected'])->default('pending');
            $table->string('idempotency_key', 100)->unique();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('provider_reference')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_withdrawal_requests');
    }
};
