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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('otherNames')->nullable();
            $table->string('email', 191)->unique();
            $table->string('phoneNumber');
            // $table->unsignedBigInteger('tenant')->nullable();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->unsignedBigInteger('role')->nullable();
             $table->unsignedBigInteger('currentPlan')->nullable();
            $table->string('status')->default('active');

            // In your users migration file
            $table->string('otp_code')->nullable();
            $table->timestamp('otp_expires_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('role')->references('roleId')->on('roles')->onDelete('cascade');
            $table->foreign('currentPlan')->references('planId')->on('plans')->onDelete('cascade');
            // $table->foreign('tenant')->references('tenantId')->on('tenants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
