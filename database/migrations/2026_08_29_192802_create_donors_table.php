<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PhpParser\Node\NullableType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('donors', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignUuid('blood_bank_uuid')->constrained('blood_bank', 'uuid')->cascadeOnDelete();

            $table->string('donor_number')->unique();

            $table->string('patient_id')->unique()->nullable();

            $table->string('batch_number')->unique()->default(null)->nullable();

            $table->string('full_name')->nullable();

            $table->date('date_of_birth')->nullable();

            $table->string('gender')->nullable();

            $table->string('existing_condition')->nullable();
            $table->string('allergies')->nullable();

            $table->string('contact')->nullable();
            $table->string('medical_history')->nullable();

            $table->string('blood_group')->nullable();
            $table->string('genotype')->nullable();

            $table->string('email')->nullable();

            $table->string('source')->nullable();

            $table->text('blood_type')->nullable();

            $table->string('quantity')->nullable();

            $table->date('collection_date')->nullable();

            $table->string('donor_type');

            $table->string('donor_category')->nullable();

            $table->string('eligibility')->nullable()->default('pending');

            $table->date('last_donation_date')->nullable();

            $table->string('status')->default('active');

            $table->string('count')->default('0');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donors');
    }
};
