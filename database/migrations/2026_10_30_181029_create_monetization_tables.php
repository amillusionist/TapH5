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
        Schema::create('ad_providers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('name', 100);
            $table->boolean('is_enabled')->default(false)->index();
            $table->string('publisher_id', 100)->nullable();
            $table->boolean('test_mode')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('provider', 32)->default('adsense')->index();
            $table->string('slot_id', 100)->nullable();
            $table->string('placement_type', 32)->default('display')->index(); // display, banner, interstitial, rewarded
            $table->boolean('is_enabled')->default(false)->index();
            $table->boolean('show_on_desktop')->default(true);
            $table->boolean('show_on_mobile')->default(true);
            $table->unsignedInteger('frequency_limit')->default(0); // seconds between ad impressions, or 0 for none
            $table->integer('priority')->default(0);
            $table->json('provider_config')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_placements');
        Schema::dropIfExists('ad_providers');
    }
};
