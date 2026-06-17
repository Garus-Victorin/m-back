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
        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('attribute_name', 100);
            $table->string('attribute_value', 150);
            $table->string('sku', 100)->nullable()->unique();
            $table->decimal('extra_price', 12, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reserved_stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
            $table->index(['product_id', 'position']);
            $table->unique(['product_id', 'attribute_name', 'attribute_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
