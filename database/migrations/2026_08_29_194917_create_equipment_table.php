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
        Schema::create('equipment', function (Blueprint $table) {
                
                $table->id();

                $table->uuid('uuid')->unique();

                $table->uuid('blood_bank_uuid')->nullable();

                $table->foreignUuid('facility_uuid')->nullable()
                    ->constrained('facilities', 'uuid')
                    ->cascadeOnDelete();

                $table->string('name');
                $table->string('equipment_code')->unique();

                $table->string('category')->nullable();
                $table->string('purpose')->nullable();
                $table->string('uses')->nullable();
                $table->string('replacement')->default('no');
                $table->string('frequency')->nullable();
                $table->string('department')->nullable();
                $table->string('trainning')->default('no');
                $table->string('image')->nullable();
                $table->string('manufacturer')->nullable();
                $table->string('condition')->default('good');
                $table->text('description')->nullable();

                $table->timestamps();
                $table->softDeletes();

                
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
