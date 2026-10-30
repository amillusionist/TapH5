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
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->string('event_type', 32)->index();
            $table->string('visitor_id', 64)->index();
            $table->string('session_id', 64)->nullable()->index();
            $table->string('device_type', 16)->default('desktop')->index();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['event_type', 'created_at']);
            $table->index(['game_id', 'created_at']);
            $table->index(['visitor_id', 'created_at']);
            $table->index(['event_type', 'game_id', 'created_at']);
        });

        Schema::create('analytics_daily_aggregates', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('play_clicks_count')->default(0);
            $table->unsignedInteger('game_loads_count')->default(0);
            $table->unsignedInteger('unique_players_count')->default(0);
            $table->unsignedBigInteger('total_engagement_seconds')->default(0);
            $table->timestamps();

            $table->unique(['date', 'game_id']);
            $table->index(['date', 'views_count']);
            $table->index(['date', 'play_clicks_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_daily_aggregates');
        Schema::dropIfExists('analytics_events');
    }
};
