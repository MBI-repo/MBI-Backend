<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->default('approved');
            $table->integer('publication_year')->nullable();
            $table->string('authors')->nullable();
            $table->enum('access_type', ['general', 'peer_reviewed'])->default('general');
            $table->boolean('is_restricted')->default(false);
            $table->text('abstract')->nullable();
            $table->text('introduction')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('no_of_likes')->default(0);
            $table->unsignedInteger('no_of_comments')->default(0);
            $table->string('publication_url')->nullable();
            $table->json('tags')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
