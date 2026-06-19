<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->string('status')->default('open');
            $table->foreignId('last_message_id')->nullable()->constrained('seller_messages')->nullOnDelete();
            $table->integer('seller_unread_count')->default(0);
            $table->integer('customer_unread_count')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_conversations');
    }
};
