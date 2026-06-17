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
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('inventory_reserved_at')->nullable()->after('paid_at');
            $table->timestamp('inventory_committed_at')->nullable()->after('inventory_reserved_at');
            $table->timestamp('inventory_released_at')->nullable()->after('inventory_committed_at');
            $table->index('inventory_reserved_at');
            $table->index('inventory_committed_at');
            $table->index('inventory_released_at');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained('product_variants')->nullOnDelete();
            $table->index(['product_id', 'product_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex(['product_id', 'product_variant_id']);
            $table->dropConstrainedForeignId('product_variant_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['inventory_reserved_at']);
            $table->dropIndex(['inventory_committed_at']);
            $table->dropIndex(['inventory_released_at']);
            $table->dropColumn([
                'inventory_reserved_at',
                'inventory_committed_at',
                'inventory_released_at',
            ]);
        });
    }
};
