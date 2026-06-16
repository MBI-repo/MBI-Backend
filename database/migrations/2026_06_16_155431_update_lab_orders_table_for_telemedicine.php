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
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->foreignId('lab_facility_id')->nullable()->constrained('lab_facilities')->onDelete('cascade');
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropForeign(['lab_facility_id']);
            $table->dropColumn(['lab_facility_id', 'frequency', 'duration', 'start_date', 'end_date']);
        });
    }
};
