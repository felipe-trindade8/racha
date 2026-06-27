<?php

use App\Enums\AttendanceStatusEnum;
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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_match_id')->constrained()->cascadeOnDelete();
            // The player's availability for the match; `confirmed` tracks whether
            // they have actually confirmed their presence.
            $table->string('status')->default(AttendanceStatusEnum::Available->value);
            $table->boolean('confirmed')->default(false);
            $table->timestamps();

            // A player has at most one attendance record per match.
            $table->unique(['player_id', 'game_match_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
