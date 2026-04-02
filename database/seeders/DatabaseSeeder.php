<?php

namespace Database\Seeders;

use App\Models\AiSetting;
use App\Models\Category;
use App\Models\GameTemplate;
use App\Models\PromptVersion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user (Google + password login uchun)
        User::updateOrCreate(
            ['email' => 'admin@games.uz'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'status' => 'active',
                'google_id' => null,
                'password' => Hash::make('Admin@12345'),
            ]
        );

        // Categories
        $categories = [
            ['name' => 'Matematika', 'sort_order' => 1],
            ['name' => 'Tabiiy fanlar', 'sort_order' => 2],
            ['name' => 'Til va adabiyot', 'sort_order' => 3],
            ['name' => 'Tarix', 'sort_order' => 4],
            ['name' => 'Geografiya', 'sort_order' => 5],
            ['name' => 'Umumiy bilimlar', 'sort_order' => 6],
        ];
        foreach ($categories as $cat) {
            Category::updateOrCreate(['name' => $cat['name']], array_merge($cat, ['status' => 'active']));
        }

        // ===== TEMPLATELAR =====
        $templates = [
            // --- STANDART ---
            [
                'code' => 'quiz_mcq',
                'name' => 'Viktorina (Test)',
                'type' => 'quiz',
                'renderer_component' => 'QuizRenderer',
                'status' => 'enabled',
                'sort' => 1,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'options', 'answer_index']],
                'prompt' => 'Create a multiple choice quiz about "{{topic}}" in {{language}} language with exactly {{students_count}} questions.

Return ONLY valid JSON:
{
  "title": "Quiz title",
  "description": "Brief description",
  "items": [
    {"id": 1, "question": "Question?", "options": ["A", "B", "C", "D"], "answer_index": 0, "explanation": "Why correct"}
  ]
}',
            ],
            [
                'code' => 'anagram',
                'name' => 'Anagramma',
                'type' => 'word',
                'renderer_component' => 'AnagramRenderer',
                'status' => 'enabled',
                'sort' => 2,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'original', 'scrambled']],
                'prompt' => 'Create an anagram word game about "{{topic}}" in {{language}} language with exactly {{students_count}} words.
Each word must be related to the topic. Scramble letters (must differ from original).

Return ONLY valid JSON:
{
  "title": "Game title",
  "items": [
    {"id": 1, "original": "WORD", "scrambled": "RWOD", "hint": "hint about this word"}
  ]
}',
            ],
            [
                'code' => 'true_false',
                'name' => 'To\'g\'ri yoki Noto\'g\'ri',
                'type' => 'quiz',
                'renderer_component' => 'TrueFalseRenderer',
                'status' => 'enabled',
                'sort' => 3,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'statement', 'answer']],
                'prompt' => 'Create a true/false quiz about "{{topic}}" in {{language}} language with exactly {{students_count}} statements.

Return ONLY valid JSON:
{
  "title": "True/False Quiz",
  "items": [
    {"id": 1, "statement": "Statement here", "answer": true, "explanation": "Why true/false"}
  ]
}',
            ],
            [
                'code' => 'flashcards',
                'name' => 'Flesh kartalar',
                'type' => 'memory',
                'renderer_component' => 'FlashcardsRenderer',
                'status' => 'enabled',
                'sort' => 4,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'front', 'back']],
                'prompt' => 'Create flashcards about "{{topic}}" in {{language}} language with exactly {{students_count}} cards.

Return ONLY valid JSON:
{
  "title": "Flashcards title",
  "items": [
    {"id": 1, "front": "Term or question", "back": "Definition or answer"}
  ]
}',
            ],
            [
                'code' => 'matching_pairs',
                'name' => 'Juftlikni top',
                'type' => 'match',
                'renderer_component' => 'MatchingPairsRenderer',
                'status' => 'enabled',
                'sort' => 5,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'left', 'right']],
                'prompt' => 'Create a matching pairs game about "{{topic}}" in {{language}} language with exactly {{students_count}} pairs.

Return ONLY valid JSON:
{
  "title": "Matching game title",
  "items": [
    {"id": 1, "left": "Term", "right": "Definition or translation"}
  ]
}',
            ],
            [
                'code' => 'word_search',
                'name' => 'So\'z qidirish',
                'type' => 'word',
                'renderer_component' => 'WordSearchRenderer',
                'status' => 'enabled',
                'sort' => 6,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'word', 'hint']],
                'prompt' => 'Generate {{students_count}} words related to "{{topic}}" in {{language}} language for a word search game.
Words should be 3-10 letters, all uppercase.

Return ONLY valid JSON:
{
  "title": "Word Search title",
  "words": [
    {"word": "WORD", "hint": "hint or definition"}
  ]
}',
            ],
            [
                'code' => 'reorder',
                'name' => 'Tartibga sol',
                'type' => 'drag',
                'renderer_component' => 'ReorderRenderer',
                'status' => 'enabled',
                'sort' => 7,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'words', 'correct_sentence']],
                'prompt' => 'Create a sentence reordering game about "{{topic}}" in {{language}} language with exactly {{students_count}} sentences.
Sentences should be educational and related to the topic.

Return ONLY valid JSON:
{
  "title": "Reorder sentences game",
  "items": [
    {"id": 1, "words": ["Word3", "Word1", "Word2", "Word4"], "correct_sentence": "Word1 Word2 Word3 Word4"}
  ]
}',
            ],
            [
                'code' => 'group_sort',
                'name' => 'Guruh bo\'yicha saralash',
                'type' => 'drag',
                'renderer_component' => 'GroupSortRenderer',
                'status' => 'enabled',
                'sort' => 8,
                'output_schema' => ['required' => ['title', 'groups'], 'item_fields' => ['name', 'items']],
                'prompt' => 'Create a group sorting game about "{{topic}}" in {{language}} language. Create 2-4 groups with {{students_count}} total items.

Return ONLY valid JSON:
{
  "title": "Group sort title",
  "groups": [
    {"name": "Group name", "items": ["item1", "item2", "item3"]}
  ]
}',
            ],
            [
                'code' => 'complete_sentence',
                'name' => 'Gapni to\'ldiring',
                'type' => 'drag',
                'renderer_component' => 'CompleteSentenceRenderer',
                'status' => 'enabled',
                'sort' => 9,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'sentence', 'answer', 'options']],
                'prompt' => 'Create a fill-in-the-blank game about "{{topic}}" in {{language}} language with exactly {{students_count}} sentences.
Use ___ to indicate the blank.

Return ONLY valid JSON:
{
  "title": "Complete the sentence",
  "items": [
    {"id": 1, "sentence": "The capital of France is ___", "answer": "Paris", "options": ["Paris", "London", "Berlin", "Madrid"]}
  ]
}',
            ],
            [
                'code' => 'open_box',
                'name' => 'Qutini och',
                'type' => 'memory',
                'renderer_component' => 'OpenBoxRenderer',
                'status' => 'enabled',
                'sort' => 10,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'hidden_text', 'hint']],
                'prompt' => 'Create an "open the box" memory game about "{{topic}}" in {{language}} language with exactly {{students_count}} items.

Return ONLY valid JSON:
{
  "title": "Open the box",
  "items": [
    {"id": 1, "hidden_text": "Answer or fact hidden in box", "hint": "Small hint visible on box"}
  ]
}',
            ],
            [
                'code' => 'random_wheel',
                'name' => 'Tasodifiy g\'ildirak',
                'type' => 'quiz',
                'renderer_component' => 'RandomWheelRenderer',
                'status' => 'enabled',
                'sort' => 11,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'text']],
                'prompt' => 'Create {{students_count}} items for a random wheel spinner about "{{topic}}" in {{language}} language.
Items can be questions, names, words, or tasks.

Return ONLY valid JSON:
{
  "title": "Random wheel title",
  "items": [
    {"id": 1, "text": "Item text here"}
  ]
}',
            ],
            [
                'code' => 'whack_mole',
                'name' => 'Ko\'rsichaqa ur',
                'type' => 'quiz',
                'renderer_component' => 'WhackMoleRenderer',
                'status' => 'enabled',
                'sort' => 12,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'correct_answers', 'wrong_answers']],
                'prompt' => 'Create a whack-a-mole quiz about "{{topic}}" in {{language}} language with {{students_count}} rounds.
For each round: 1 question with correct answers and wrong answers (distractors).

Return ONLY valid JSON:
{
  "title": "Whack-a-mole title",
  "rounds": [
    {"id": 1, "question": "Question?", "correct_answers": ["correct1", "correct2"], "wrong_answers": ["wrong1", "wrong2", "wrong3"]}
  ]
}',
            ],
            [
                'code' => 'hangman',
                'name' => 'Harfni top (Osib qo\'yish)',
                'type' => 'word',
                'renderer_component' => 'HangmanRenderer',
                'status' => 'enabled',
                'sort' => 13,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'word', 'hint']],
                'prompt' => 'Create a hangman word guessing game about "{{topic}}" in {{language}} language with exactly {{students_count}} words.

Return ONLY valid JSON:
{
  "title": "Hangman game title",
  "items": [
    {"id": 1, "word": "SCIENCE", "hint": "The study of natural world"}
  ]
}',
            ],
            [
                'code' => 'type_answer',
                'name' => 'Javobni yozing',
                'type' => 'quiz',
                'renderer_component' => 'TypeAnswerRenderer',
                'status' => 'enabled',
                'sort' => 14,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'answer']],
                'prompt' => 'Create a type-the-answer quiz about "{{topic}}" in {{language}} language with exactly {{students_count}} questions.

Return ONLY valid JSON:
{
  "title": "Type answer quiz",
  "items": [
    {"id": 1, "question": "Question here?", "answer": "correct answer", "alternatives": ["alt1", "alt2"]}
  ]
}',
            ],
            // ===== YANGI FORMATLAR (batch 1) =====
            [
                'code' => 'memory_cards',
                'name' => 'Xotira o\'yini',
                'type' => 'memory',
                'renderer_component' => 'MemoryCardsRenderer',
                'status' => 'enabled',
                'sort' => 15,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'front', 'back']],
                'prompt' => 'Create a memory card matching game about "{{topic}}" in {{language}} language with exactly {{students_count}} pairs.
Each pair has a front (term/word) and back (definition/translation).

Return ONLY valid JSON:
{
  "title": "Memory game title",
  "items": [
    {"id": 1, "front": "Term or word", "back": "Definition or translation"}
  ]
}',
            ],
            [
                'code' => 'game_show_quiz',
                'name' => 'O\'yin shou viktorinasi',
                'type' => 'quiz',
                'renderer_component' => 'GameShowRenderer',
                'status' => 'enabled',
                'sort' => 16,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'options', 'answer_index']],
                'prompt' => 'Create a game show style multiple choice quiz about "{{topic}}" in {{language}} language with exactly {{students_count}} questions.

Return ONLY valid JSON:
{
  "title": "Game show title",
  "items": [
    {"id": 1, "question": "Question?", "options": ["A", "B", "C", "D"], "answer_index": 0}
  ]
}',
            ],
            [
                'code' => 'flying_answers',
                'name' => 'Uchuvchi javoblar',
                'type' => 'quiz',
                'renderer_component' => 'FlyingAnswersRenderer',
                'status' => 'enabled',
                'sort' => 17,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'options', 'answer_index']],
                'prompt' => 'Create a flying answers quiz about "{{topic}}" in {{language}} language with exactly {{students_count}} questions.
Each question has 4 options, one correct.

Return ONLY valid JSON:
{
  "title": "Flying answers title",
  "items": [
    {"id": 1, "question": "Question?", "options": ["Correct", "Wrong1", "Wrong2", "Wrong3"], "answer_index": 0}
  ]
}',
            ],
            [
                'code' => 'pair_or_not',
                'name' => 'Juftmi yoki yo\'q?',
                'type' => 'match',
                'renderer_component' => 'PairOrNotRenderer',
                'status' => 'enabled',
                'sort' => 18,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'front', 'back']],
                'prompt' => 'Create a pair-or-not decision game about "{{topic}}" in {{language}} language with exactly {{students_count}} pairs.

Return ONLY valid JSON:
{
  "title": "Pair or not title",
  "items": [
    {"id": 1, "front": "Term", "back": "Its correct definition"}
  ]
}',
            ],
            [
                'code' => 'speed_sort',
                'name' => 'Tez saralash',
                'type' => 'drag',
                'renderer_component' => 'SpeedSortRenderer',
                'status' => 'enabled',
                'sort' => 19,
                'output_schema' => ['required' => ['title', 'groups'], 'item_fields' => ['name', 'items']],
                'prompt' => 'Create a speed sorting game about "{{topic}}" in {{language}} language. Create 2-4 groups with {{students_count}} total items.

Return ONLY valid JSON:
{
  "title": "Speed sort title",
  "groups": [
    {"name": "Group name", "items": ["item1", "item2", "item3"]}
  ]
}',
            ],
            [
                'code' => 'spell_word',
                'name' => 'So\'zni yoz',
                'type' => 'word',
                'renderer_component' => 'SpellWordRenderer',
                'status' => 'enabled',
                'sort' => 20,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'word', 'hint']],
                'prompt' => 'Create a spell-the-word game about "{{topic}}" in {{language}} language with exactly {{students_count}} words.

Return ONLY valid JSON:
{
  "title": "Spell the word",
  "items": [
    {"id": 1, "word": "SCIENCE", "hint": "The study of the natural world and its phenomena"}
  ]
}',
            ],
            // ===== YANGI FORMATLAR (batch 2) =====
            [
                'code' => 'airplane',
                'name' => 'Samolyot o\'yini',
                'type' => 'quiz',
                'renderer_component' => 'AirplaneRenderer',
                'status' => 'enabled',
                'sort' => 21,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'options', 'answer_index']],
                'prompt' => 'Create an airplane quiz game about "{{topic}}" in {{language}} language with exactly {{students_count}} questions.
Each question has 4 options, one correct.

Return ONLY valid JSON:
{
  "title": "Airplane quiz title",
  "items": [
    {"id": 1, "question": "Question?", "options": ["Correct", "Wrong1", "Wrong2", "Wrong3"], "answer_index": 0}
  ]
}',
            ],
            [
                'code' => 'watch_memorize',
                'name' => 'Ko\'rib eslab qol',
                'type' => 'memory',
                'renderer_component' => 'WatchMemorizeRenderer',
                'status' => 'enabled',
                'sort' => 22,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'front', 'back']],
                'prompt' => 'Create a watch-and-memorize flashcard game about "{{topic}}" in {{language}} language with exactly {{students_count}} cards.
Each card has a front (term) and back (definition).

Return ONLY valid JSON:
{
  "title": "Watch and memorize title",
  "items": [
    {"id": 1, "front": "Term or concept", "back": "Definition or answer"}
  ]
}',
            ],
            [
                'code' => 'win_or_lose',
                'name' => 'Yut yoki Yoqot',
                'type' => 'quiz',
                'renderer_component' => 'WinOrLoseRenderer',
                'status' => 'enabled',
                'sort' => 23,
                'output_schema' => ['required' => ['title', 'items']],
                'prompt' => 'Create a win-or-lose mystery box game about "{{topic}}" in {{language}} language with exactly {{students_count}} boxes.
Some boxes are winners (correct facts/answers), some are losers (wrong/trap answers).

Return ONLY valid JSON:
{
  "title": "Win or lose title",
  "items": [
    {"id": 1, "label": "Short text shown after opening", "is_winner": true},
    {"id": 2, "label": "Wrong answer revealed", "is_winner": false}
  ]
}',
            ],
            [
                'code' => 'math_quiz',
                'name' => 'Matematik test',
                'type' => 'quiz',
                'renderer_component' => 'MathQuizRenderer',
                'status' => 'enabled',
                'sort' => 24,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'expression', 'answer', 'options']],
                'prompt' => 'Create a math quiz about "{{topic}}" in {{language}} language with exactly {{students_count}} math problems.
Each problem has an expression and 4 multiple choice options.

Return ONLY valid JSON:
{
  "title": "Math quiz title",
  "items": [
    {"id": 1, "expression": "12 × 8 = ?", "answer": "96", "options": ["84", "96", "108", "72"]}
  ]
}',
            ],
            // ===== YANGI FORMATLAR (batch 3) =====
            [
                'code' => 'millionaire',
                'name' => 'Millioner',
                'type' => 'quiz',
                'renderer_component' => 'MillionaireRenderer',
                'status' => 'enabled',
                'sort' => 25,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'options', 'answer_index']],
                'prompt' => 'Create a "Who Wants to Be a Millionaire" style quiz about "{{topic}}" in {{language}} language with exactly {{students_count}} questions.
Questions should gradually increase in difficulty. Each question has 4 options, only one correct.

Return ONLY valid JSON:
{
  "title": "Millioner — quiz title",
  "items": [
    {"id": 1, "question": "Easy question?", "options": ["Correct", "Wrong1", "Wrong2", "Wrong3"], "answer_index": 0, "prize": "100"}
  ]
}',
            ],
            [
                'code' => 'spelling',
                'name' => 'Imlo',
                'type' => 'word',
                'renderer_component' => 'SpellingRenderer',
                'status' => 'enabled',
                'sort' => 26,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'word', 'hint', 'sentence']],
                'prompt' => 'Create a spelling practice game about "{{topic}}" in {{language}} language with exactly {{students_count}} words.
Each word has a hint and an example sentence.

Return ONLY valid JSON:
{
  "title": "Imlo — spelling title",
  "items": [
    {"id": 1, "word": "MATHEMATICS", "hint": "Fan nomi", "sentence": "Men ___ darsini yaxshi ko\'raman."}
  ]
}',
            ],
            [
                'code' => 'diagram',
                'name' => 'Diagramma',
                'type' => 'drag',
                'renderer_component' => 'DiagramRenderer',
                'status' => 'enabled',
                'sort' => 27,
                'output_schema' => ['required' => ['title', 'parts'], 'item_fields' => ['id', 'label', 'description']],
                'prompt' => 'Create a diagram labeling activity about "{{topic}}" in {{language}} language with exactly {{students_count}} parts/components.
Each part has a label and description.

Return ONLY valid JSON:
{
  "title": "Diagramma — topic title",
  "description": "Quyidagi qismlarni aniqlang",
  "parts": [
    {"id": 1, "label": "Nucleus", "description": "Hujayraning boshqaruv markazi"}
  ]
}',
            ],
            // ===== YANGI ENABLED O'YINLAR =====
            [
                'code' => 'zakovat',
                'name' => 'Zakovat',
                'type' => 'quiz',
                'renderer_component' => 'ZakovatRenderer',
                'status' => 'enabled',
                'sort' => 28,
                'output_schema' => ['required' => ['items']],
                'prompt' => 'Sen professional o\'zbek tili o\'qituvchisisizsan. Zakovat viktorina o\'yini uchun materiallar yarating.

Mavzu: {{topic}}
Til: {{language}}
Savollar soni: {{students_count}}
Sinf: {{grade}}

Quyidagi JSON formatida javob bering (boshqa hech narsa yozmang):
{
  "items": [
    {
      "category": "Kategoriya nomi",
      "question": "Savol matni?",
      "options": ["A variant", "B variant", "C variant", "D variant"],
      "answer": "To\'g\'ri javob (options dan biri)",
      "points": 100
    }
  ]
}

Talablar:
- Har bir kategoriyada 3-4 ta savol bo\'lsin
- Savollar mavzuga mos, qiziqarli va yoshga mos bo\'lsin
- points: birinchi savol 100, ikkinchi 200, uchinchi 300
- Savollar to\'g\'ri javob bilan birga 4 ta variant bo\'lsin
- Barcha matnlar {{language}} tilida bo\'lsin',
            ],
            [
                'code' => 'race',
                'name' => 'Poyga',
                'type' => 'quiz',
                'renderer_component' => 'RaceRenderer',
                'status' => 'enabled',
                'sort' => 30,
                'output_schema' => ['required' => ['items']],
                'prompt' => 'Sen professional o\'zbek tili o\'qituvchisisizsan. Poyga o\'yini uchun savollar yarating.

Mavzu: {{topic}}
Til: {{language}}
Savollar soni: {{students_count}}
Sinf: {{grade}}

Quyidagi JSON formatida javob bering (boshqa hech narsa yozmang):
{
  "items": [
    {
      "question": "Savol matni?",
      "options": ["A variant", "B variant", "C variant", "D variant"],
      "answer": "To\'g\'ri javob (options dan biri)"
    }
  ]
}

Talablar:
- Savollar mavzuga mos, qiziqarli va yoshga mos bo\'lsin
- Har bir savolda 4 ta variant bo\'lsin
- Savollar turli qiyinchilik darajasida bo\'lsin
- Barcha matnlar {{language}} tilida bo\'lsin',
            ],
            [
                'code' => 'timeline',
                'name' => "Vaqt chizig'i",
                'type' => 'drag',
                'renderer_component' => 'TimelineRenderer',
                'status' => 'enabled',
                'sort' => 33,
                'output_schema' => ['required' => ['items']],
                'prompt' => 'Sen professional o\'zbek tili o\'qituvchisisizsan. Vaqt chizig\'i (timeline) o\'yini uchun materiallar yarating.

Mavzu: {{topic}}
Til: {{language}}
Voqealar soni: {{students_count}}
Sinf: {{grade}}

Quyidagi JSON formatida javob bering (boshqa hech narsa yozmang):
{
  "items": [
    {
      "text": "Voqea tavsifi",
      "year": 1991,
      "hint": "Qo\'shimcha ma\'lumot (ixtiyoriy)"
    }
  ]
}

Talablar:
- Voqealar mavzuga mos va tarixiy/mantiqiy tartibda bo\'lsin
- year maydoni son bo\'lsin (yil raqami)
- Voqealar qisqa va aniq tasvirlangan bo\'lsin
- 6-10 ta voqea yarating
- Barcha matnlar {{language}} tilida bo\'lsin',
            ],
            // ===== TEZ KUNDA (coming soon - disabled) =====
            [
                'code' => 'rope_pull',
                'name' => 'Arqon tortish',
                'type' => 'quiz',
                'renderer_component' => 'RopePullRenderer',
                'status' => 'enabled',
                'sort' => 29,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'options', 'answer_index']],
                'prompt' => 'Create a tug-of-war quiz game about "{{topic}}" in {{language}} language with exactly {{students_count}} questions.

Return ONLY valid JSON:
{
  "title": "Game title",
  "team_a": "Yashillar",
  "team_b": "Qizillar",
  "items": [
    {"id": 1, "question": "Question text?", "options": ["A) Option", "B) Option", "C) Option", "D) Option"], "answer_index": 0}
  ]
}

Rules:
- Exactly 4 options per question, labeled A) B) C) D)
- answer_index is 0-based (0=A, 1=B, 2=C, 3=D)
- Questions vary in difficulty: some easy, some medium, some hard
- All text in {{language}} language',
            ],
            [
                'code' => 'sleeping_bear',
                'name' => 'Uyqudagi Ayiq',
                'type' => 'activity',
                'renderer_component' => 'SleepingBearRenderer',
                'status' => 'enabled',
                'sort' => 31,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'answer']],
                'prompt' => 'Create a "Sleeping Bear" quiz game about "{{topic}}" in {{language}} language with exactly {{students_count}} questions. The game mechanic: wrong answers disturb the sleeping bear — 3 wrong answers and the bear wakes up!

Return ONLY valid JSON:
{
  "title": "Game title",
  "description": "Brief description in {{language}}",
  "items": [
    {"id": 1, "question": "Question text?", "options": ["A) Option", "B) Option", "C) Option", "D) Option"], "answer_index": 0, "hint": "A short hint"}
  ]
}

Rules:
- Exactly 4 options per question labeled A) B) C) D)
- answer_index is 0-based (0=A, 1=B, 2=C, 3=D)
- Include a short hint for each question (shown after wrong answer)
- Mix difficulty: mostly medium questions with some easy warm-ups
- All text in {{language}} language',
            ],
            [
                'code' => 'crossword',
                'name' => 'Krossvord',
                'type' => 'word',
                'renderer_component' => 'CrosswordRenderer',
                'status' => 'enabled',
                'sort' => 32,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'word', 'clue']],
                'prompt' => 'Create a crossword puzzle about "{{topic}}" in {{language}} language with exactly {{students_count}} words.

Return ONLY valid JSON:
{
  "title": "Crossword title",
  "description": "Brief description in {{language}}",
  "items": [
    {"id": 1, "word": "ALGEBRA", "clue": "Clue for this word in {{language}}"},
    {"id": 2, "word": "NUMBER", "clue": "Clue for this word in {{language}}"}
  ]
}

IMPORTANT rules:
- Each "word" must be UPPERCASE Latin letters only (A-Z), no spaces, no hyphens, no special characters
- Words must be 3 to 12 letters long
- Words should share common letters so they can intersect in a crossword grid
- Include a variety of word lengths (short 3-4, medium 5-7, long 8-12)
- Clues must be written in {{language}} language — clear, educational hints
- Do NOT put the answer word in the clue',
            ],
            [
                'code' => 'lesson_plan',
                'name' => 'Dars rejasi',
                'type' => 'document',
                'renderer_component' => 'LessonPlanRenderer',
                'status' => 'enabled',
                'sort' => 33,
                'output_schema' => ['required' => ['title', 'sections']],
                'prompt' => 'Create a detailed lesson plan about "{{topic}}" in {{language}} language for a class of {{students_count}} students.

Return ONLY valid JSON:
{
  "title": "Lesson plan title",
  "subject": "Subject name",
  "duration": "45 minutes",
  "objectives": ["Students will be able to...", "Students will understand..."],
  "sections": [
    {
      "id": 1,
      "phase": "Kirish",
      "duration": "5 daqiqa",
      "activity": "What the teacher does in this phase",
      "student_activity": "What students do in this phase",
      "materials": "Required materials or tools"
    }
  ],
  "assessment": "How to assess student learning",
  "homework": "Optional homework assignment"
}

Phases to include: Kirish (intro 5 min), Yangi mavzu (new topic 20 min), Mustahkamlash (practice 15 min), Baholash (assessment 5 min).
Write all text content in {{language}} language.',
            ],
            [
                'code' => 'dtm_test',
                'name' => 'DTM test',
                'type' => 'quiz',
                'renderer_component' => 'DtmTestRenderer',
                'status' => 'enabled',
                'sort' => 34,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'options', 'answer_index']],
                'prompt' => 'Create a DTM (Davlat Test Markazi) style practice test about "{{topic}}" in {{language}} language with exactly {{students_count}} questions.

Return ONLY valid JSON:
{
  "title": "DTM Test: topic name",
  "subject": "Subject name",
  "instructions": "Brief instructions for students in {{language}}",
  "items": [
    {
      "id": 1,
      "question": "Full question text",
      "options": ["A) First option", "B) Second option", "C) Third option", "D) Fourth option"],
      "answer_index": 0,
      "explanation": "Why this answer is correct, in {{language}}"
    }
  ]
}

Rules:
- Questions must be rigorous, exam-style, similar to official DTM tests
- Each question has exactly 4 options labeled A) B) C) D)
- answer_index is 0-based (0=A, 1=B, 2=C, 3=D)
- Mix difficulty: 40% easy, 40% medium, 20% hard
- No repeated questions, all options must be plausible
- Write all text in {{language}} language',
            ],
            [
                'code' => 'pisa_reading',
                'name' => 'PISA o\'qish',
                'type' => 'quiz',
                'renderer_component' => 'PisaRenderer',
                'status' => 'enabled',
                'sort' => 36,
                'output_schema' => ['required' => ['title', 'passage', 'items']],
                'prompt' => 'Create a PISA-style reading comprehension task about "{{topic}}" in {{language}} language with exactly {{students_count}} questions.

Return ONLY valid JSON:
{
  "title": "PISA task title",
  "context": "Brief real-world context (1 sentence)",
  "passage": "A 150-250 word informational text about the topic. Should be like a newspaper article, infographic description, or real document. Make it engaging and realistic.",
  "items": [
    {
      "id": 1,
      "type": "mcq",
      "question": "Question based on the passage?",
      "options": ["A) Option", "B) Option", "C) Option", "D) Option"],
      "answer_index": 0,
      "skill": "find_info"
    }
  ]
}

Skill types: "find_info" (find explicit info), "interpret" (interpret meaning), "reflect" (reflect and evaluate).
Include a mix of all 3 skill types across questions.
answer_index is 0-based.
All text in {{language}} language.',
            ],
            [
                'code' => 'olimpiada',
                'name' => 'Olimpiada',
                'type' => 'quiz',
                'renderer_component' => 'DtmTestRenderer',
                'status' => 'enabled',
                'sort' => 37,
                'output_schema' => ['required' => ['title', 'items'], 'item_fields' => ['id', 'question', 'options', 'answer_index']],
                'prompt' => 'Create an olympiad-level practice test about "{{topic}}" in {{language}} language with exactly {{students_count}} challenging questions. These questions should be significantly harder than standard curriculum — suitable for subject olympiad competitions.

Return ONLY valid JSON:
{
  "title": "Olimpiada: topic name",
  "subject": "Subject name",
  "level": "Olimpiada darajasi",
  "instructions": "Instructions for olympiad participants in {{language}}",
  "items": [
    {
      "id": 1,
      "question": "Deep, analytical question requiring higher-order thinking",
      "options": ["A) Option", "B) Option", "C) Option", "D) Option"],
      "answer_index": 0,
      "explanation": "Detailed explanation of the solution approach in {{language}}"
    }
  ]
}

Rules:
- Questions must require analysis, synthesis, or evaluation (Bloom\'s higher levels)
- Include multi-step reasoning problems
- Some questions can reference real historical events, scientific discoveries, or mathematical theorems
- Difficulty: 20% medium, 50% hard, 30% very hard
- All text in {{language}} language',
            ],
            [
                'code' => 'map_quiz',
                'name' => 'Xarita testi',
                'type' => 'quiz',
                'renderer_component' => 'MapQuizRenderer',
                'status' => 'disabled',
                'sort' => 38,
                'output_schema' => ['required' => ['title', 'items']],
                'prompt' => 'Coming soon.',
            ],
        ];

        foreach ($templates as $tpl) {
            $prompt = $tpl['prompt'];
            unset($tpl['prompt'], $tpl['sort']);

            $tpl['input_schema'] = [
                'fields' => ['topic', 'students_count', 'language'],
                'required' => ['topic', 'students_count', 'language'],
                'limits' => ['students_count' => ['min' => 1, 'max' => 30]],
            ];

            $template = GameTemplate::updateOrCreate(['code' => $tpl['code']], $tpl);

            PromptVersion::updateOrCreate(
                ['template_id' => $template->id, 'version' => 'v1'],
                ['status' => 'active', 'prompt_text' => $prompt]
            );
        }

        // Apply topic-locking v2 prompts (deactivates v1, inserts v2 active)
        $this->call(RefreshPromptsSeeder::class);

        // AI Settings
        $settings = [
            ['key' => 'model', 'value' => 'gpt-4o-mini'],
            ['key' => 'daily_request_limit', 'value' => '10'],
            ['key' => 'daily_token_budget', 'value' => '50000'],
            ['key' => 'max_retries', 'value' => '2'],
            ['key' => 'max_tokens', 'value' => '2000'],
        ];
        foreach ($settings as $setting) {
            AiSetting::updateOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }
    }
}
