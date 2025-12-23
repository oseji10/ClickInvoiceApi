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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id('invoiceNumber');
            $table->string('invoiceId')->unique();
            $table->string('userGeneratedInvoiceId')->nullable();
            $table->string('projectName')->nullable();
            $table->date('invoiceDate')->nullable();
            $table->date('dueDate')->nullable();
            $table->string('invoicePassword')->nullable();
            $table->string('notes')->nullable();
            $table->unsignedBigInteger('currency')->nullable();
            $table->string('amountPaid')->nullable();
            $table->string('balanceDue')->nullable();
            $table->string('accountName')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('bank')->nullable();
            $table->string('taxPercentage')->nullable();
            $table->string('receiptId')->nullable();
            $table->unsignedBigInteger('tenantId')->nullable();
            $table->unsignedBigInteger('createdBy')->nullable();
            $table->timestamps();


            $table->foreign('tenantId')->references('tenantId')->on('tenants')->onDelete('cascade');
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('currency')->references('currencyId')->on('currencies')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
