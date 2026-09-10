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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();
            
            $table->foreignUuid('user_uuid')->nullable()->constrained('users', 'uuid')->onDelete('cascade');

            $table->foreignUuid('facility_uuid')->nullable()->constrained('facilities', 'uuid')->onDelete('cascade');
            
            $table->foreignUuid('doctor_uuid')->nullable()->constrained('doctor_profiles', 'uuid')->onDelete('cascade');

            $table->string('patient_number')->unique();

            $table->string('full_name');

            $table->string('department')->nullable();

            $table->string('contact')->nullable();

            $table->date('date_of_birth')->nullable();

            $table->string('gender')->nullable();

            $table->string('blood_group')->nullable();

            $table->string('genotype')->nullable();

     

            $table->string('email')->nullable();

            $table->text('address')->nullable();

            $table->string('diagnosis')->nullable();

            $table->text('allergies')->nullable();

            $table->text('medical_notes')->nullable();

            $table->string('status')->default('active');

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
