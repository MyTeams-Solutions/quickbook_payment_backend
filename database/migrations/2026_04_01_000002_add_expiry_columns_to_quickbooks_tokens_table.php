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
        Schema::table('quickbooks_tokens', function (Blueprint $table) {
            $table->timestamp('access_token_expires_at')->nullable()->after('refresh_token');
            $table->timestamp('refresh_token_expires_at')->nullable()->after('access_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quickbooks_tokens', function (Blueprint $table) {
            $table->dropColumn(['access_token_expires_at', 'refresh_token_expires_at']);
        });
    }
};
