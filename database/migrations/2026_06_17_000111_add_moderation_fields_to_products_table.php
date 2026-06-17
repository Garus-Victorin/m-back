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
        Schema::table('products', function (Blueprint $table): void {
            $table->string('moderation_status', 30)->default('draft')->after('status');
            $table->timestamp('submitted_for_review_at')->nullable()->after('moderation_status');
            $table->foreignId('reviewed_by')->nullable()->after('submitted_for_review_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
            $table->timestamp('archived_at')->nullable()->after('rejection_reason');

            $table->index(['shop_id', 'moderation_status']);
            $table->index(['status', 'moderation_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['shop_id', 'moderation_status']);
            $table->dropIndex(['status', 'moderation_status']);
            $table->dropColumn([
                'moderation_status',
                'submitted_for_review_at',
                'reviewed_at',
                'rejection_reason',
                'archived_at',
            ]);
        });
    }
};
