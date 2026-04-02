<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_results', function (Blueprint $table) {
            // O'quvchining qurilmasida localStorage da saqlanadigan token
            // Akkaunt ochmasdan, sessiyalar orasida o'quvchini tanib olish uchun
            $table->string('student_token', 36)->nullable()->after('participant_name')->index();

            // O'quvchi qaysi sinfdan ekanini bog'lash uchun (ixtiyoriy)
            $table->foreignId('classroom_student_id')
                  ->nullable()
                  ->after('student_token')
                  ->constrained('classroom_students')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('session_results', function (Blueprint $table) {
            $table->dropForeign(['classroom_student_id']);
            $table->dropColumn(['student_token', 'classroom_student_id']);
        });
    }
};
