<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();
            $table->longText('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_online')->default(false);
            // $table->boolean('visibility')->default(true);
            
            $table->string('meeting_link')->nullable();
            $table->enum('status', ['draft', 'published', 'cancelled', 'upcoming', 'completed'])->default('draft');
            $table->boolean('visibility')->default(true);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('timezone')->nullable();

            $table->string('venue')->nullable();

            $table->decimal('price', 10, 2)->nullable();
            $table->json('tags')->nullable();

            $table->softDeletes();
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};