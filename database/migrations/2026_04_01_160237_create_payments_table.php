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
        // php artisan make:migration create_payments_table
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('charge_id')->unique();   // MT7190385635
    $table->string('status');                // CAPTURED
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3);
    $table->string('card_type')->nullable(); // Visa
    $table->string('card_last4', 4)->nullable();
    $table->string('auth_code')->nullable(); // tst940
    $table->string('token')->nullable();
    $table->string('environment')->default('sandbox');
    $table->timestamp('captured_at')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
