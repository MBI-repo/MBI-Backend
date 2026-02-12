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
        Schema::create('notifications', function (Blueprint $table) {

            $table->id();
           // Receiver (mandatory)
            $table->string('receiver_id', 36);
            $table->foreign('receiver_id')->references('uuid')->on('users')->onDelete('cascade');
            // Sender (optional: system notifications may be null)
            $table->string('sender_id', 36)->nullable();
            $table->foreign('sender_id')->references('uuid')->on('users')->onDelete('set null');
            $table->string('title')->nullable(); // e.g "Profile Update"
            $table->text('message')->nullable();
            $table->string('type'); 
            // connection, comment, update, reminder, etc.
            $table->boolean('is_read')->default(false);
            // For linking to resource (post, profile, request, etc.)
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
