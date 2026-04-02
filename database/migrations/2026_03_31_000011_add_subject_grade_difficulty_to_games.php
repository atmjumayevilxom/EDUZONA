<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            // Fan (Matematika, Fizika, Ingliz tili, ...)
            $table->string('subject', 100)->nullable()->after('topic');
            // Sinf darajasi (1, 2, ..., 11, oliy)
            $table->string('grade_level', 20)->nullable()->after('subject');
            // Qiyinlik darajasi
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->nullable()->after('grade_level');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['subject', 'grade_level', 'difficulty']);
        });
    }
};
