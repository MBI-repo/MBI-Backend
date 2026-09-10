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
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignUuid('blood_bank_uuid')
                ->constrained('blood_bank', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('donor_uuid')
                ->constrained('donors', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('blood_collection_uuid')
                ->nullable()
                ->constrained('blood_collection', 'uuid')
                ->nullOnDelete();

            $table->string('donation_number')->unique();

            $table->string('unit_id')->unique();

            $table->string('blood_group')->nullable();

            $table->string('source')->default('Walk-in');

            $table->decimal('volume', 8, 2);

            $table->string('volume_unit')->default('ml');

            $table->dateTime('donation_date');

            $table->string('status')->default('Pending');

            $table->foreignUuid('recorded_by_uuid')
                ->nullable()
                ->constrained('users', 'uuid')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('source');
            $table->index('blood_group');
            $table->index('donation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
