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
        Schema::create('blood_collection', function (Blueprint $table) {

            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignUuid('blood_bank_uuid')
                ->constrained('blood_bank', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('donor_uuid')
                ->constrained('donors', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('screening_uuid')
                ->constrained('donor_screening', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('collected_by_uuid')
                ->nullable()
                ->constrained('users', 'uuid')
                ->nullOnDelete();

            $table->string('label')->unique();

            $table->decimal('volume', 8, 2);

            $table->dateTime('collected_at');

            

            $table->string('status')->default('collected');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_collection');
    }
};
