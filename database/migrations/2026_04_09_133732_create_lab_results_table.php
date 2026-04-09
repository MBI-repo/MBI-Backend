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
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained('lab_orders')->onDelete('cascade');
            $table->string('signed_off_by_name');
            $table->string('signed_off_by_title')->nullable();
            $table->string('signed_off_by_gmc')->nullable();
            $table->timestamp('date_completed');
            $table->text('doctor_comment')->nullable();
            $table->enum('overall_flag', ['Normal', 'Abnormal', 'Critical'])->default('Normal');
            $table->enum('status', ['Draft', 'Completed', 'Amended'])->default('Completed');
            $table->timestamps();
        });

        Schema::create('lab_result_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_result_id')->constrained('lab_results')->onDelete('cascade');
            $table->foreignId('lab_equipment_id')->constrained('lab_equipment')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};
