<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
       
    public function up(): void
    {
        Schema::create('laboratory_tests', function (Blueprint $table) {

            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignUuid('blood_bank_uuid')->constrained('blood_bank', 'uuid')->onDelete('cascade');
            $table->foreignUuid('donor_uuid')->constrained('donors', 'uuid')->onDelete('cascade');
            $table->foreignUuid('screening_uuid')->constrained('donor_screening', 'uuid')->onDelete('cascade');
            $table->foreignUuid('collection_uuid')->constrained('blood_collection', 'uuid')->onDelete('cascade');

            $table->string('abo_typing')->nullable();
            $table->string('rh_factor')->nullable();

            $table->string('hiv_result')->nullable();
            $table->string('hiv_test_kit_used')->nullable();

            $table->string('hepatitis_a')->nullable();
            $table->string('hepatitis_b')->nullable();

            $table->string('syphilis_result')->nullable();
            $table->string('syphilis_test_kit_used')->nullable();

            $table->boolean('is_safe')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_tests');
    }
};