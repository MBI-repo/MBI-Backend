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
        Schema::create('research_and_developments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Step 1
            $table->string('category')->nullable();
            $table->string('title'); // Required
            $table->string('slug')->unique();
            $table->string('principal_investigator'); // Required
            $table->text('abstract')->nullable();
            $table->json('tags')->nullable();

            // Step 2
            $table->string('ethical_approval_id')->nullable();
            $table->string('ethical_approval_file')->nullable();
            $table->json('study_design')->nullable();
            $table->json('methodology')->nullable();
            $table->string('institution')->nullable();

            // Step 3
            $table->string('conflict_of_interest')->nullable();
            $table->string('funding_disclosure')->nullable();
            $table->json('data_sources')->nullable();

            // Step 4
            $table->string('external_url')->nullable();
            $table->string('document_path')->nullable();

            // Other fields
            $table->string('status')->default('draft'); // draft, pending, approved
            $table->string('access_type')->default('general'); // general, peer_reviewed
            $table->string('clinical_phase')->nullable();
            $table->string('peer_review')->nullable();
            $table->string('funding_state')->nullable();
            $table->string('methodology_type')->nullable();
            $table->string('country_region')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('research_and_developments');
    }
};
