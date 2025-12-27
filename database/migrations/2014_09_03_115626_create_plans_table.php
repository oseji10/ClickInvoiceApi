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
        Schema::create('plans', function (Blueprint $table) {
            $table->id('planId');
            $table->string('planName')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedBigInteger('currency')->nullable();
            $table->text('features')->nullable();
            $table->boolean('isPopular')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('currency')->references('currencyId')->on('currencies')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
