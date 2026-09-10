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
        Schema::create('blood_unit', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique();

            $table->string('unit_number')->unique();

            $table->foreignUuid('donation_uuid')
                ->nullable()
                ->constrained('donations', 'uuid')
                ->nullOnDelete();


            $table->foreignUuid('component_uuid')
                ->nullable()
                ->constrained('blood_components', 'uuid')
                ->nullOnDelete();


            $table->foreignUuid('facility_uuid')
                ->nullable()
                ->constrained('facilities', 'uuid')
                ->nullOnDelete();


            $table->string('blood_group');
            $table->string('genotype')->nullable();

            $table->decimal('volume', 8, 2)->nullable();

            $table->dateTime('collection_date')->nullable();
            $table->dateTime('expiry_date')->nullable();

            $table->string('status')->default('available');
            // available
            // reserved
            // issued
            // transfused
            // expired
            // discarded
            // quarantined

            $table->string('storage_location')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_unit');
    }
};
