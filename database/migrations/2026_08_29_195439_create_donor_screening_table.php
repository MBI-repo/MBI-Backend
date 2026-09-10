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
        Schema::create('donor_screening', function (Blueprint $table) {

            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignUuid('donor_uuid')
                ->constrained('donors', 'uuid')
                ->cascadeOnDelete();

            $table->dateTime('screening_date');

            /*
            |--------------------------------------------------------------------------
            | Age & Weight Check
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger('age')->nullable();

            $table->decimal('weight', 5, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Blood Pressure Check
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('systolic_bp')->nullable();

            $table->unsignedSmallInteger('diastolic_bp')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Infectious Disease Risk Assessment
            |--------------------------------------------------------------------------
            */

            $table->boolean('recent_fever_or_infection')->nullable();

            $table->boolean('infectious_disease_exposure')->nullable();

            $table->boolean('recent_tattoo_or_piercing')->nullable();

            $table->boolean('high_risk_travel_history')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Medical History Review
            |--------------------------------------------------------------------------
            */

            $table->boolean('existing_medical_condition')->nullable();

            $table->boolean('recent_surgery')->nullable();

            $table->boolean('current_medication')->nullable();

            $table->boolean('feeling_unwell')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Eligibility Decision
            |--------------------------------------------------------------------------
            */

            $table->string('eligibility')->default('pending');

            $table->text('deferral_reason')->nullable();

            $table->date('deferred_until')->nullable();

            $table->text('notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Screening Staff
            |--------------------------------------------------------------------------
            */

            $table->foreignUuid('screened_by_uuid')
                ->nullable()
                ->constrained('users', 'uuid')
                ->nullOnDelete();

            $table->timestamps();
        });
                    
                

                
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donor_screening');
    }
};
