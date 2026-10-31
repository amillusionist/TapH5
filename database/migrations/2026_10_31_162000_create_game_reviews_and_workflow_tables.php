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
        Schema::table('games', function (Blueprint $table) {
            $table->string('review_status', 30)->default('not_submitted')->after('status')->index();
            $table->timestamp('submitted_at')->nullable()->after('review_status');
            $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('submitted_by');
            $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable()->after('reviewed_by');
            $table->index(['status', 'review_status']);
        });

        Schema::create('game_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40)->index();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_reviews');

        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['status', 'review_status']);
            $table->dropIndex(['review_status']);
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'review_status',
                'submitted_at',
                'submitted_by',
                'reviewed_at',
                'reviewed_by',
                'review_notes',
            ]);
        });
    }
};
