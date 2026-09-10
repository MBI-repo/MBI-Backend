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
        Schema::create('facilities', function (Blueprint $table) {

            $table->id();

            $table->uuid('uuid')->unique();

            $table->foreignUuid('user_uuid')->unique()->constrained('users','uuid')->cascadeOnDelete();

            $table->string('facility_name')->nullable();

            $table->string('facility_type')->nullable();

            $table->string('registration_number')->nullable()->unique();

            $table->string('license_number')->nullable();

            $table->string('license_document')->nullable();

            $table->date('license_expiry_date')->nullable();

            $table->string('contact_email')->nullable();

            $table->string('contact_phone')->nullable();

            $table->text('address')->nullable();

            $table->string('city')->nullable();

            $table->string('state')->nullable();

            $table->string('country')->nullable();

            $table->string('postal_code')->nullable();

            $table->string('logo')->nullable();

            $table->text('description')->nullable();

            $table->string('registration_status')->default('pending');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
