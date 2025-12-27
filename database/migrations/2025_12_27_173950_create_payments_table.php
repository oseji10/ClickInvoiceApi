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
        // Migration: php artisan make:migration create_payments_table
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('subscriptionId')->nullable();
    $table->string('flutterwaveTxRef'); // Your unique ref
    $table->string('flutterwaveTxId'); // Flutterwave's ID
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3);
    $table->enum('status', ['successful', 'failed', 'pending']);
    $table->json('responseData')->nullable();
    $table->timestamps();
    
    $table->foreign('subscriptionId')->references('subscriptionId')->on('subscriptions')->onDelete('cascade');
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
