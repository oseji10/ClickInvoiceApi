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
        Schema::create('tenant_staff', function (Blueprint $table) {
            $table->id('tenantStaffId');
            $table->unsignedBigInteger('tenantId')->nullable();
            $table->unsignedBigInteger('userId')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenantId')->references('tenantId')->on('tenants')->onDelete('cascade');
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_staff');
    }
};
