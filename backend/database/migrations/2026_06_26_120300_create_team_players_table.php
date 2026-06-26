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
        Schema::create('team_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_match_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            // Per-match position the player lines up in, drawn from the positions
            // catalog. Nullable: assigned when the roster is arranged. Detaching on
            // catalog delete keeps the roster record intact.
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            // Performance rating for this match (1-5); set after the match.
            $table->unsignedTinyInteger('game_rating')->nullable();
            $table->boolean('is_starter')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_players');
    }
};
