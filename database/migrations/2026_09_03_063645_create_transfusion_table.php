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
        Schema::create('transfusion', function (Blueprint $table) {

            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignUuid('patient_uuid')
                ->constrained('patients', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('doctor_uuid')
                ->constrained('doctor_profiles', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('blood_request_uuid')
                ->nullable()
                ->constrained('blood_request', 'uuid')
                ->nullOnDelete();

            $table->foreignUuid('blood_component_uuid')
                ->constrained('blood_components', 'uuid')
                ->restrictOnDelete();

            $table->string('transfusion_number')->unique();

            $table->string('blood_group');

            $table->string('component_type');

            $table->string('blood_unit_id');

            $table->dateTime('start_time');

            $table->dateTime('proposed_end_time')->nullable();

            $table->dateTime('actual_end_time')->nullable();

            $table->foreignUuid('assigned_staff_uuid')
                ->nullable()
                ->constrained('users', 'uuid')
                ->nullOnDelete();

            $table->string('assigned_staff_name')->nullable();

            $table->string('status')->default('in_progress');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('patient_uuid');
            $table->index('doctor_uuid');
            $table->index('blood_request_uuid');
            $table->index('blood_component_uuid');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfusion');
    }
};
