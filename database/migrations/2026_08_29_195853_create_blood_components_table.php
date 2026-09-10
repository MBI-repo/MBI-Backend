<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blood_components', function (Blueprint $table) {

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

            $table->foreignUuid('blood_collection_uuid')
                ->constrained('blood_collection', 'uuid')
                ->cascadeOnDelete();

            $table->foreignUuid('laboratory_test_uuid')
                ->constrained('laboratory_tests', 'uuid')
                ->cascadeOnDelete();


            $table->string('component_type');
            
            $table->string('component_id')->unique();

        
            $table->decimal('volume', 8, 2);

            $table->string('volume_unit')->default('ml');

           
            $table->string('blood_group')->nullable();

          
            $table->date('expiry_date')->nullable();

            
            $table->string('storage_type')->nullable();

          
            $table->string('status')->default('Ready for Storage');

            $table->timestamps();

            $table->index('component_type');
            $table->index('status');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_components');
    }
};
