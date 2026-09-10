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
        Schema::create('blood_request', function (Blueprint $table) {
                 $table->id();

                $table->uuid('uuid')->unique();

                $table->foreignUuid('blood_bank_uuid')
                    ->nullable()
                    ->constrained('blood_bank', 'uuid')
                    ->cascadeOnDelete();

                $table->foreignUuid('requester_uuid')
                    ->nullable()
                    ->constrained('users', 'uuid')
                    ->cascadeOnDelete();

                $table->foreignUuid('facility_uuid')
                    ->nullable()
                    ->constrained('facilities', 'uuid')
                    ->cascadeOnDelete();

                $table->foreignUuid('patient_uuid')
                    ->nullable()
                    ->constrained('patients', 'uuid')
                    ->cascadeOnDelete();

                $table->foreignUuid('doctor_uuid')
                    ->nullable()
                    ->constrained('doctor_profiles', 'uuid')
                    ->cascadeOnDelete();    

                $table->string('request_type')->default('patient');
                // patient
                // emergency
                // elective
                // stock_replenishment


                $table->string('full_name')->nullable();

                $table->string('priority')->default('urgent');
                // low
                // normal
                // urgent
                // emergency
                
                $table->string('component_type')->nullable();

                $table->string('unit_needed')->nullable();

                $table->string('department')->nullable();

                $table->text('note')->nullable();

                $table->string('request_number')->unique();

                $table->date('request_date')->nullable();
                
                $table->time('request_time')->nullable();

                $table->string('blood_group')->nullable();

                $table->text('clinical_reason')->nullable();

                $table->dateTime('required_at')->nullable();

                $table->string('status')->default('pending');
                // pending
                // approved
                // processing
                // partially_fulfilled
                // fulfilled
                // rejected
                // cancelled

                $table->text('rejection_reason')->nullable();

                $table->timestamps();

                
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_request');
    }
};
