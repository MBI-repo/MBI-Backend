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
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('test_category_id')->constrained('test_categories')->onDelete('cascade');
            $table->string('specific_test_name');
            $table->enum('priority', ['Routine', 'Urgent', 'Emergency'])->default('Routine');
            $table->foreignId('lab_center_id')->constrained('lab_centers')->onDelete('cascade');
            $table->text('provisional_diagnosis')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['Requested', 'Processing', 'Completed'])->default('Requested');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_orders');
    }
};
