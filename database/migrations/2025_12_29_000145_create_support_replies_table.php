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
        Schema::create('support_replies', function (Blueprint $table) {
            $table->id('replyId');
            $table->unsignedBigInteger('ticketId')->nullable();
            $table->unsignedBigInteger('userId')->nullable();
            $table->text('message');
            $table->boolean('is_admin')->default(false); // True if reply from support
            $table->timestamps();

            $table->foreign('ticketId')->references('ticketId')->on('support_tickets')->onDelete('cascade');
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_replies');
    }
};
