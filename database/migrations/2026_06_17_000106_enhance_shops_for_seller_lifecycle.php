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
            $table->enum('status', ['draft', 'pending', 'active', 'suspended'])->default('draft')->change();
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('activated_at')->nullable()->after('submitted_at');
            $table->timestamp('suspended_at')->nullable()->after('activated_at');
            $table->text('suspension_reason')->nullable()->after('suspended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table): void {
            $table->dropColumn(['submitted_at', 'activated_at', 'suspended_at', 'suspension_reason']);
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending')->change();
        });
    }
};
