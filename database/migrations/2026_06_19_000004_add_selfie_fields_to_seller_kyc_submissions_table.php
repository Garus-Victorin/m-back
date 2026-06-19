<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_kyc_submissions', function (Blueprint $table) {
            $table->string('selfie_path')->nullable()->after('document_back_path');
            $table->string('selfie_mime_type')->nullable()->after('selfie_path');
            $table->integer('selfie_size')->nullable()->after('selfie_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('seller_kyc_submissions', function (Blueprint $table) {
            $table->dropColumn(['selfie_path', 'selfie_mime_type', 'selfie_size']);
        });
    }
};
