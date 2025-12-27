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
       // Migration: php artisan make:migration add_flutterwave_plan_id_to_plans
Schema::table('plans', function (Blueprint $table) {
    $table->string('flutterwavePlanId')->nullable(); // e.g., '12345' from Flutterwave
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            //
        });
    }
};
