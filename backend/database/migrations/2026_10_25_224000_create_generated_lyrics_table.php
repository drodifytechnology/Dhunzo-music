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
        Schema::create('generated_lyrics', function (Blueprint $table) {
            $table->id();
            $table->string('track_hash', 64)->unique()->index();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('language', 32)->nullable()->default('hi');
            $table->string('provider', 64)->default('ai_speech_to_text');
            $table->string('type', 32)->default('line_synced');
            $table->longText('raw_lrc');
            $table->json('lines');
            $table->text('audio_source_url')->nullable();
            $table->float('confidence_score')->default(0.95);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_lyrics');
    }
};
