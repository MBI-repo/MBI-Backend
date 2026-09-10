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
     
            Schema::create('blood_bank', function (Blueprint $table) {
               
                $table->id();

                $table->uuid('uuid')->unique();

                $table->string('name');

                $table->foreignUuid('user_uuid')->unique()->constrained('users','uuid')->cascadeOnDelete();

                $table->string('registration_number')->nullable()->unique();

                $table->string('license_number')->nullable();

                $table->string('email')->nullable();

                $table->string('phone')->nullable();

                $table->string('address')->nullable();

                $table->string('city')->nullable();

                $table->string('state')->nullable();

                $table->string('country')->nullable();

                $table->string('logo')->nullable();

                $table->string('status')->default('active');

                $table->timestamps();
            });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_bank');
    }
};
