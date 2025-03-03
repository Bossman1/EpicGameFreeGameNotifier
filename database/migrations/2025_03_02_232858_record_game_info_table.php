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
        Schema::create('record_game_infos', function (Blueprint $table) {
            $table->id();
            $table->string('game_id');
            $table->string('game_title');
            $table->string('game_description');
            $table->string('game_effective_date');
            $table->string('game_seller');
            $table->json('game_images');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('record_game_infos');
    }
};
