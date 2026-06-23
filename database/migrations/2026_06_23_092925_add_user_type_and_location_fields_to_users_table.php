<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->default('user')->after('status');
            }
            if (!Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable()->after('state');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'user_type')) {
                $columns[] = 'user_type';
            }
            if (Schema::hasColumn('users', 'state')) {
                $columns[] = 'state';
            }
            if (Schema::hasColumn('users', 'country')) {
                $columns[] = 'country';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
