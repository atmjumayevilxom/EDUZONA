<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'games_user_id_created_at_idx');
            $table->index(['user_id', 'status'], 'games_user_id_status_idx');
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->index('game_id', 'game_sessions_game_id_idx');
        });

        Schema::table('session_results', function (Blueprint $table) {
            $table->index(['session_id', 'score'], 'session_results_session_id_score_idx');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex('games_user_id_created_at_idx');
            $table->dropIndex('games_user_id_status_idx');
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropIndex('game_sessions_game_id_idx');
        });

        Schema::table('session_results', function (Blueprint $table) {
            $table->dropIndex('session_results_session_id_score_idx');
        });
    }
};
