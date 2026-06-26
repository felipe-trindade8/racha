<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Added after `game_match_teams` exists to resolve the circular reference:
     * a match points at its two teams while each team belongs to the match.
     * Nullable because a match is created before its teams are assigned.
     */
    public function up(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->foreignId('team_a_id')->nullable()->after('date')
                ->constrained('game_match_teams')->nullOnDelete();
            $table->foreignId('team_b_id')->nullable()->after('team_a_id')
                ->constrained('game_match_teams')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_a_id');
            $table->dropConstrainedForeignId('team_b_id');
        });
    }
};
