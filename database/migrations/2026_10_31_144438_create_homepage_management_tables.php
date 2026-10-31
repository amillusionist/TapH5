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
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_key')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->integer('items_limit')->default(6);
            $table->json('curated_game_ids')->nullable();
            $table->json('display_options')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'sort_order']);
        });

        Schema::create('homepage_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('cta_text')->nullable()->default('Play Now');
            $table->string('cta_url')->nullable();
            $table->string('background_image')->nullable();
            $table->foreignId('game_id')->nullable()->constrained('games')->nullOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_enabled', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_slides');
        Schema::dropIfExists('homepage_sections');
    }
};
