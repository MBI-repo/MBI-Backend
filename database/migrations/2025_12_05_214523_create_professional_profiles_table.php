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
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_uuid')->unique(); // one profile per user

            $table->text('bio')->nullable();
            $table->json('areas_of_expertise')->nullable();

            $table->json('educations')->nullable();
            $table->json('experiences')->nullable();
            $table->json('certifications')->nullable();
            $table->json('publications')->nullable();
            $table->json('memberships')->nullable();
            $table->json('awards')->nullable();

            $table->timestamps();

            $table->foreign('user_uuid')
                  ->references('uuid')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_profiles');
    }
};
