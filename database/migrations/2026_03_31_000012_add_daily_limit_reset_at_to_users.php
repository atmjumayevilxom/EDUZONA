<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Admin foydalanuvchining kunlik limitini qayta tiklashi uchun.
            // generate() faqat bu vaqtdan keyingi o'yinlarni hisoblaydi.
            $table->timestamp('daily_limit_reset_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('daily_limit_reset_at');
        });
    }
};
