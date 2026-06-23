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
        Schema::dropIfExists('telemedicine_sessions');

        Schema::create('telemedicine_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('conversation_id');
            $table->text('symptoms')->nullable();
            $table->enum('status', ['active', 'ended'])->default('active');
            $table->timestamps();

            // Indexes for fast lookup
            $table->index('patient_id');
            $table->index('doctor_id');
            $table->index('conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telemedicine_sessions');
    }
};
