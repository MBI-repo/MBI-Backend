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
       Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignUuid('blood_bank_uuid')
                ->constrained('blood_bank', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('blood_component_uuid')
                ->constrained('blood_components', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('blood_request_uuid')
                ->constrained('blood_request', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('reserved_by_uuid')
                ->nullable()
                ->constrained('users', 'uuid')
                ->nullOnDelete();

            $table->dateTime('reserved_at');
            $table->dateTime('expires_at')->nullable();

            $table->string('status')->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index([
                'blood_component_uuid',
                'status'
            ]);

            $table->index([
                'blood_request_uuid',
                'status'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
