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
        Schema::create('blood_request_items', function (Blueprint $table) {
                 $table->id();

                $table->uuid('uuid')->unique();

                $table->foreignUuid('blood_request_uuid')
                    ->constrained('blood_request', 'uuid')
                    ->cascadeOnDelete();

                $table->foreignUuid('component_uuid')
                    ->constrained('blood_components', 'uuid')
                    ->cascadeOnDelete();

                $table->string('blood_group');

                $table->unsignedInteger('quantity');

                $table->unsignedInteger('fulfilled_quantity')->default(0);

                $table->decimal('volume_required', 8, 2)->nullable();

                $table->string('status')->default('pending');

                $table->timestamps();

                
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_request_items');
    }
};
