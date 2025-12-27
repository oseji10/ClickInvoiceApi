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
        Schema::create('customers', function (Blueprint $table) {
            $table->id('customerId');
            $table->unsignedBigInteger('tenantId')->nullable();
            $table->string('customerName')->nullable();
            $table->string('customerAddress')->nullable();
            $table->string('customerEmail')->nullable();
            $table->string('customerPhone')->nullable();
            $table->timestamps();

            $table->foreign('tenantId')->references('tenantId')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
