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
       // Migration: php artisan make:migration create_subscriptions_table
Schema::create('subscriptions', function (Blueprint $table) {
    $table->id('subscriptionId');
    $table->unsignedBigInteger('userId')->nullable();
    $table->unsignedBigInteger('planId')->nullable();
    $table->string('flutterwaveSubscriptionId')->nullable(); // From Flutterwave after activation
    $table->enum('status', ['active', 'pending', 'cancelled', 'failed'])->default('pending');
    $table->timestamp('startDate')->nullable();
    $table->timestamp('nextBillingDate')->nullable();
    $table->timestamp('endDate')->nullable(); // For cancellations
    $table->json('metadata')->nullable(); // Store extra Flutterwave data (e.g., card token)
    $table->timestamps();
    
    
            $table->foreign('planId')->references('planId')->on('plans')->onDelete('cascade');
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');


});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
