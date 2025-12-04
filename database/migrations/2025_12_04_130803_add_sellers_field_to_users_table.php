<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('corporate_registration_papers')->nullable();
            $table->string('product_approval')->nullable();
            $table->string('warranty_policy_document')->nullable();
            $table->string('export_capability_statement')->nullable();
            $table->string('incoterms_preference')->nullable();
            $table->text('admin_comment')->nullable();
            $table->text('rejection_comment')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'corporate_registration_papers',
                'product_approval',
                'warranty_policy_document',
                'export_capability_statement',
                'incoterms_preference',
                'admin_comment',
                'rejection_comment',
            ]);
        });
    }
};