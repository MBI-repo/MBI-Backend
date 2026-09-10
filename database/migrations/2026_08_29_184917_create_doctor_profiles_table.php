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
        Schema::create('doctor_profiles', function (Blueprint $table) {

            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignUuId('user_uuid')->unique()->constrained('users','uuid')->cascadeOnDelete();

            $table->string('title')->nullable();

            $table->string('license_number')->nullable()->unique();

            $table->string('specialization')->nullable();

            $table->string('qualification')->nullable();

            $table->string('years_of_experience')->nullable();

            $table->string('medical_council')->nullable();

            $table->string('license_document')->nullable();

            $table->string('identification_document')->nullable();

            $table->text('address')->nullable();

            $table->string('state')->nullable();

            $table->string('country')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
