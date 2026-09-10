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
        Schema::create('doctor_facilities', function (Blueprint $table) {
                $table->id();

                $table->uuid('uuid')->unique();

                $table->foreignUuid('doctor_uuid')->constrained('users', 'uuid')->cascadeOnDelete();
                $table->foreignUuid('facility_uuid')->constrained('facilities', 'uuid')->cascadeOnDelete();

                $table->string('position')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();

                $table->string('status')->default('active');

                $table->timestamps();

                $table->unique(['doctor_uuid', 'facility_uuid']);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_facilities');
    }
};
