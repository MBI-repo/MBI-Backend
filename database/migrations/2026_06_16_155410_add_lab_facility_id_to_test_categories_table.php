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
        Schema::table('test_categories', function (Blueprint $table) {
            $table->foreignId('lab_facility_id')->nullable()->constrained('lab_facilities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_categories', function (Blueprint $table) {
            $table->dropForeign(['lab_facility_id']);
            $table->dropColumn('lab_facility_id');
        });
    }
};
