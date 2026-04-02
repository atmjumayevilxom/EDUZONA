<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_templates', function (Blueprint $table) {
            // Token budjetini GenerateGameJob ichidagi hardcoded arraydan DB ga ko'chirish
            $table->integer('token_budget_base')->default(250)->after('status');
            $table->integer('token_budget_per_item')->default(50)->after('token_budget_base');
            $table->text('description')->nullable()->after('token_budget_per_item');
        });
    }

    public function down(): void
    {
        Schema::table('game_templates', function (Blueprint $table) {
            $table->dropColumn(['token_budget_base', 'token_budget_per_item', 'description']);
        });
    }
};
