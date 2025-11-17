<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // Add uuid columns
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->unique();
        });

        // Backfill existing rows with UUIDs
        DB::table('users')->whereNull('uuid')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('users')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });

        DB::table('conversations')->whereNull('uuid')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('conversations')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });

        DB::table('messages')->whereNull('uuid')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('messages')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};