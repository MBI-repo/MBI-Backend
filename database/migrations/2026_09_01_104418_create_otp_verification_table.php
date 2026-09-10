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
       Schema::create('otp_verification', function (Blueprint $table) {
        
                $table->id();

                $table->uuid('uuid')->unique();

                $table->foreignUuid('user_uuid')
                    ->constrained('users', 'uuid')
                    ->cascadeOnDelete();

                $table->string('identifier');

                $table->string('type');
                // password_reset
                // phone_verification
                // email_verification

                $table->string('otp_hash');

                $table->unsignedTinyInteger('attempts')
                    ->default(0);

                $table->timestamp('expires_at');

                $table->timestamp('verified_at')
                    ->nullable();

                $table->timestamp('last_sent_at')
                    ->nullable();

                $table->timestamps();

                $table->index([
                    'user_uuid',
                    'type',
                ]);

                $table->index('identifier');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verification');
    }
};
