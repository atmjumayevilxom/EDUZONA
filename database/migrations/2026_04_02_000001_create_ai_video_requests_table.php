<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_video_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Foydalanuvchi kiritgan ma'lumotlar
            $table->string('subject');                        // matematika, fizika, biologiya...
            $table->string('topic');                          // Masalan: "Kvadrat tenglamalar"
            $table->text('problem_text');                     // Masala matni
            $table->string('video_style')->default('blackboard');   // blackboard | animated
            $table->string('explanation_length')->default('medium'); // short | medium | long
            $table->string('voice_style')->default('calm');          // calm | energetic
            $table->string('language')->default('uz');               // uz | en | ru

            // AI jarayoni
            $table->json('solution_json')->nullable();        // GPT qadam-baqadam yechimi
            $table->text('video_prompt')->nullable();         // Qurilgan video prompti

            // Tashqi video provider
            $table->string('provider_name')->nullable();      // grok | heygen | ...
            $table->string('provider_request_id')->nullable(); // Tashqi API dan kelgan ID

            // Holat va natija
            $table->enum('status', [
                'pending',          // Kutilmoqda
                'solving',          // GPT masalani yechmoqda
                'building_prompt',  // Video prompti qurilmoqda
                'generating',       // Video generatsiya qilinmoqda
                'completed',        // Tayyor
                'failed',           // Xato
            ])->default('pending');

            $table->text('error_message')->nullable();
            $table->string('video_url')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indekslar — tez qidirish uchun
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_video_requests');
    }
};
