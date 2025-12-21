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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id('tenantId');
            $table->string('tenantName')->nullable();
            $table->string('tenantEmail')->nullable();
            $table->string('tenantPhone')->nullable();
            $table->string('tenantLogo')->nullable();
            $table->string('authorizedSignature')->nullable();
            $table->string('countryCode')->nullable();
            $table->string('timezone')->nullable();
            $table->unsignedBigInteger('gatewayPreference')->nullable();
            $table->unsignedBigInteger('currency')->nullable();
            $table->unsignedBigInteger('ownerId')->nullable();
            $table->boolean('isDefault')->nullable()->default(1);
            $table->string('status')->nullable()->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('currency')->references('currencyId')->on('currencies')->onDelete('cascade');
            $table->foreign('ownerId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('gatewayPreference')->references('gatewayId')->on('payment_gateways')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
