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
            $table->uuid('user_uuid')->unique();
            $table->boolean('notify_network')->default(true);
            $table->boolean('notify_messages')->default(true);
            $table->boolean('notify_events')->default(true);
            $table->boolean('notify_system')->default(true);
            $table->enum('frequency', ['instant', 'daily', 'weekly'])->default('instant');
            $table->timestamps();
            $table->foreign('user_uuid')->references('uuid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
