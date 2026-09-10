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
    Schema::create('blood_request_status_histories', function (Blueprint $table) {
        
                $table->id();

                $table->uuid('uuid')->unique();

                $table->foreignUuid('blood_request_uuid')
                    ->constrained('blood_request', 'uuid')
                    ->cascadeOnDelete();

                $table->string('old_status')->nullable();

                $table->string('new_status');

                $table->text('comment')->nullable();

                $table->foreignUuid('changed_by')
                    ->nullable()
                    ->constrained('users', 'uuid')
                    ->cascadeOnDelete();

                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blood_request_status_histories');
    }
};
