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
            $table->foreignId('category_id')->nullable()->change();
            $table->string('fingerprint', 64)->nullable()->index()->after('storage_path');
            $table->json('scan_metadata')->nullable()->after('fingerprint');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['fingerprint']);
            $table->dropColumn(['fingerprint', 'scan_metadata']);
            $table->foreignId('category_id')->nullable(false)->change();
        });
    }
};
