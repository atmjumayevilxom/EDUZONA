<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\GameTemplate;
use App\Models\SessionResult;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo ma'lumotlar seederi — faqat development/staging uchun.
 * Joriy sana bilan realistik statistika ko'rsatadi.
 *
 * Ishlatish: php artisan db:seed --class=DevDataSeeder
 */
class DevDataSeeder extends Seeder
{
    public function run(): void
    {
        $templates = GameTemplate::where('status', 'enabled')->get();

        if ($templates->isEmpty()) {
            $this->command->warn('Avval asosiy seederni ishga tushiring: php artisan db:seed');
            return;
        }

        $this->command->info('Demo foydalanuvchilar yaratilmoqda...');

        // 10 ta demo o'qituvchi (so'nggi 30 kun ichida ro'yxatdan o'tgan)
        $teachers = [];
        $names = [
            ['Aziz Karimov', 'aziz.karimov@school.uz'],
            ['Malika Yusupova', 'malika.yusupova@school.uz'],
            ['Bobur Toshmatov', 'bobur@school.uz'],
            ['Zulfiya Hasanova', 'zulfiya@school.uz'],
            ['Jasur Mirzayev', 'jasur@school.uz'],
            ['Dilnoza Rahimova', 'dilnoza@school.uz'],
            ['Sherzod Nazarov', 'sherzod@school.uz'],
            ['Nargiza Abdullayeva', 'nargiza@school.uz'],
            ['Ulugbek Xolmatov', 'ulugbek@school.uz'],
            ['Feruza Ismoilova', 'feruza@school.uz'],
        ];

        foreach ($names as [$name, $email]) {
            $teachers[] = User::updateOrCreate(
                ['email' => $email],
                [
                    'name'       => $name,
                    'role'       => 'user',
                    'status'     => 'active',
                    'password'   => Hash::make('demo1234'),
                    'created_at' => now()->subDays(rand(1, 30)),
                ]
            );
        }

        $this->command->info('Demo o\'yinlar yaratilmoqda...');

        $topics = [
            'Ko\'paytirish jadvali', 'Oddiy kasrlar', 'Geometrik shakllar',
            'Quyosh tizimi', 'Insonning skelet tizimi', 'Fotosintez',
            'O\'zbekiston tarixi', 'Ikkinchi jahon urushi', 'Buyuk ipak yo\'li',
            'Ingliz tili Past Simple', 'Ingliz tili Present Perfect', 'Irregular Verbs',
            'O\'zbekiston poytaxti va shaharlar', 'Dunyo qitoalari', 'Dunyo daryolari',
            'Atom tuzilishi', 'Kimyoviy elementlar', 'Moddalar holatlari',
        ];

        $games = [];
        foreach ($teachers as $teacher) {
            // Har bir o'qituvchi uchun 3-8 ta o'yin (so'nggi 14 kun)
            $gameCount = rand(3, 8);
            for ($i = 0; $i < $gameCount; $i++) {
                $template = $templates->random();
                $daysAgo  = rand(0, 14);
                $game = Game::updateOrCreate(
                    [
                        'user_id'     => $teacher->id,
                        'topic'       => $topics[array_rand($topics)],
                        'template_id' => $template->id,
                    ],
                    [
                        'language'       => ['uz', 'ru', 'en'][rand(0, 2)],
                        'students_count' => rand(5, 25),
                        'status'         => 'ready',
                        'is_public'      => rand(0, 3) === 0, // 25% public
                        'generated_json' => ['title' => 'Demo', 'items' => []],
                        'created_at'     => now()->subDays($daysAgo)->subHours(rand(0, 8)),
                    ]
                );
                $games[] = $game;
            }
        }

        $this->command->info('Demo sessiyalar va natijalar yaratilmoqda...');

        $studentNames = [
            'Alisher', 'Malika', 'Bobur', 'Zulfiya', 'Jasur',
            'Dilnoza', 'Sherzod', 'Nargiza', 'Ulugbek', 'Feruza',
            'Kamola', 'Sarvar', 'Aziza', 'Mirzo', 'Gulnora',
        ];

        foreach (array_slice($games, 0, 20) as $game) {
            // Har bir o'yinning 40% iga sessiya qo'shamiz
            if (rand(0, 9) < 4) continue;

            $session = GameSession::create([
                'game_id'      => $game->id,
                'session_code' => strtoupper(Str::random(6)),
                'status'       => 'ended',
                'started_at'   => $game->created_at->addHours(rand(1, 24)),
                'ended_at'     => $game->created_at->addHours(rand(25, 48)),
                'created_at'   => $game->created_at->addHours(rand(1, 24)),
            ]);

            // 5-15 ta o'quvchi natijasi
            $playerCount = rand(5, 15);
            $shuffled    = collect($studentNames)->shuffle()->take($playerCount);

            foreach ($shuffled as $name) {
                SessionResult::create([
                    'session_id'       => $session->id,
                    'participant_name' => $name,
                    'score'            => rand(40, 100),
                    'answers_json'     => [],
                    'student_token'    => Str::uuid(),
                    'created_at'       => $session->started_at->addMinutes(rand(1, 30)),
                ]);
            }
        }

        $this->command->info('✅ Demo ma\'lumotlar muvaffaqiyatli yaratildi!');
        $this->command->table(
            ['Ko\'rsatkich', 'Soni'],
            [
                ["O'qituvchilar", count($teachers)],
                ["O'yinlar", count($games)],
                ["Sessiyalar", GameSession::count()],
                ["Natijalar", SessionResult::count()],
            ]
        );
    }
}
