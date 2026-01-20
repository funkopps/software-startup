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
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->string('spotify_id')->index();
            $table->foreignId('artist_id')
                  ->constrained('artists')
                  ->cascadeOnDelete();
            $table->string('track_name');
            $table->string('album')->nullable();
            $table->timestamp('timestamp'); // timestamp wanneer deze track in de opgegeven set is herkend
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracks');
    }
};
