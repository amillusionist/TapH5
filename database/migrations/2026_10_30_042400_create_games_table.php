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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('thumbnail')->nullable();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('game_type', 30)->default('single_file')->index(); // single_file, multi_file, original
            $table->string('entry_file')->default('index.html');
            $table->string('storage_path')->nullable();
            $table->string('status', 20)->default('published')->index(); // draft, published, archived
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_trending')->default(false)->index();
            $table->boolean('is_popular')->default(false)->index();
            $table->unsignedBigInteger('plays_count')->default(0)->index();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_featured']);
            $table->index(['status', 'is_trending']);
            $table->index(['status', 'is_popular']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
