<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Refreshes all prompt_versions with topic-enforcing prompts.
 * Run: php artisan db:seed --class=RefreshPromptsSeeder
 */
class RefreshPromptsSeeder extends Seeder
{
    // ─── Universal topic-lock header ────────────────────────────────────────
    // Injected at the top of every prompt so the AI always has context.
    private const HEADER = <<<'HDR'
TOPIC: "{{topic}}"
LANGUAGE: {{language}}
ITEMS: {{students_count}}

MANDATORY RULE: Every single word, question, answer, hint, option, pair, label, and fact MUST relate specifically and exclusively to the topic "{{topic}}". Content that is unrelated to "{{topic}}" is strictly forbidden. Generate exactly {{students_count}} items unless otherwise specified.

HDR;

    // ─── Prompts keyed by template code ─────────────────────────────────────
    private function prompts(): array
    {
        $h = self::HEADER;

        return [

            'quiz_mcq' => $h . <<<'P'
Generate a multiple-choice quiz strictly about "{{topic}}".

Rules:
- Every question must directly test knowledge of "{{topic}}"
- Every CORRECT answer must be an accurate fact about "{{topic}}"
- Every WRONG option must be a plausible but incorrect alternative related to "{{topic}}" — never random words
- The explanation must clarify why the answer is correct for "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — Test",
  "description": "{{topic}} mavzusida bilimingizni sinang",
  "items": [
    {"id": 1, "question": "Question about {{topic}}?", "options": ["Correct answer", "Wrong option 1", "Wrong option 2", "Wrong option 3"], "answer_index": 0, "explanation": "Why this is correct for {{topic}}"}
  ]
}
P,

            'anagram' => $h . <<<'P'
Generate an anagram word game strictly about "{{topic}}".

Rules:
- Each word must be a key term, concept, or vocabulary word from "{{topic}}"
- Scramble each word (scrambled version MUST be different from original)
- The hint must describe the word's meaning or connection to "{{topic}}"
- Words must be 4–12 letters, no spaces

Return ONLY this JSON:
{
  "title": "Anagramma: {{topic}}",
  "items": [
    {"id": 1, "original": "KEYWORD", "scrambled": "DKROEWW", "hint": "Meaning or role in {{topic}}"}
  ]
}
P,

            'true_false' => $h . <<<'P'
Generate a true/false quiz strictly about "{{topic}}".

Rules:
- Every statement must be a fact or misconception about "{{topic}}"
- Mix roughly equal true and false statements
- The explanation must reference "{{topic}}" specifically
- Never use statements unrelated to "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — To'g'ri yoki noto'g'ri?",
  "items": [
    {"id": 1, "statement": "A factual claim about {{topic}}", "answer": true, "explanation": "Why this is true/false for {{topic}}"}
  ]
}
P,

            'flashcards' => $h . <<<'P'
Generate educational flashcards strictly about "{{topic}}".

Rules:
- Front: a term, concept, or question from "{{topic}}"
- Back: its accurate definition, translation, or answer within "{{topic}}"
- Every card must be directly about "{{topic}}" — no unrelated vocabulary

Return ONLY this JSON:
{
  "title": "{{topic}} — Flesh kartalar",
  "items": [
    {"id": 1, "front": "Key term from {{topic}}", "back": "Definition or answer about {{topic}}"}
  ]
}
P,

            'matching_pairs' => $h . <<<'P'
Generate a matching pairs game strictly about "{{topic}}".

Rules:
- Every LEFT item must be a key term or concept from "{{topic}}"
- Every RIGHT item must be the accurate definition, translation, or related fact from "{{topic}}"
- Pairs must be clearly and directly connected within the context of "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — Juftlashtirish",
  "items": [
    {"id": 1, "left": "Term from {{topic}}", "right": "Its definition or match within {{topic}}"}
  ]
}
P,

            'word_search' => $h . <<<'P'
Generate words for a word search puzzle strictly about "{{topic}}".

Rules:
- Every word must be a key term, person, place, or concept from "{{topic}}"
- Words must be 4–12 uppercase letters, no spaces or hyphens
- Hint must explain the word's connection to "{{topic}}"
- No generic or unrelated words

Return ONLY this JSON:
{
  "title": "{{topic}} — So'z qidirish",
  "items": [
    {"id": 1, "word": "KEYWORD", "hint": "How this word relates to {{topic}}"}
  ]
}
P,

            'reorder' => $h . <<<'P'
Generate sentence reordering tasks strictly about "{{topic}}".

Rules:
- Every sentence must contain information about "{{topic}}"
- Sentences should teach a fact, rule, or process related to "{{topic}}"
- Provide the words in SHUFFLED order in the "words" array
- "correct_sentence" is the grammatically correct full sentence

Return ONLY this JSON:
{
  "title": "{{topic}} — Tartiblashtirish",
  "items": [
    {"id": 1, "words": ["shuffled", "word", "array", "here"], "correct_sentence": "Grammatically correct sentence about {{topic}}"}
  ]
}
P,

            'group_sort' => $h . <<<'P'
Generate a group sorting game strictly about "{{topic}}".

Rules:
- Create 2–4 groups that are meaningful categories WITHIN "{{topic}}"
- Every item in every group must belong to "{{topic}}"
- Group names must be descriptive categories of "{{topic}}"
- Distribute {{students_count}} total items across groups

Return ONLY this JSON:
{
  "title": "{{topic}} — Guruhlash",
  "groups": [
    {"name": "Category from {{topic}}", "items": ["item1 from {{topic}}", "item2 from {{topic}}", "item3 from {{topic}}"]}
  ]
}
P,

            'complete_sentence' => $h . <<<'P'
Generate fill-in-the-blank sentences strictly about "{{topic}}".

Rules:
- Every sentence must teach or test a fact about "{{topic}}"
- The blank (___) must represent a key word from "{{topic}}"
- The answer and all options must be words from "{{topic}}" vocabulary
- Wrong options should be plausible but incorrect within "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — Gap to'ldirish",
  "items": [
    {"id": 1, "sentence": "Sentence about {{topic}} with ___ blank", "answer": "correct word", "options": ["correct word", "wrong1", "wrong2", "wrong3"]}
  ]
}
P,

            'open_box' => $h . <<<'P'
Generate an "open the box" game strictly about "{{topic}}".

Rules:
- Each box reveals a fact, term, or question about "{{topic}}"
- The hint visible on the box must hint at the topic connection
- Every hidden text must be directly about "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — Qutini och",
  "items": [
    {"id": 1, "hidden_text": "Fact or term about {{topic}} revealed inside", "hint": "Clue about {{topic}}"}
  ]
}
P,

            'random_wheel' => $h . <<<'P'
Generate items for a spinning random wheel strictly about "{{topic}}".

Rules:
- Every item must be a word, term, question, or task DIRECTLY related to "{{topic}}"
- Items can be: key vocabulary, questions, names, facts, or tasks — all about "{{topic}}"
- No generic or unrelated items

Return ONLY this JSON:
{
  "title": "{{topic}} — Tasodifiy g'ildirak",
  "items": [
    {"id": 1, "text": "A term, question, or task about {{topic}}"}
  ]
}
P,

            'whack_mole' => $h . <<<'P'
Generate a whack-a-mole quiz strictly about "{{topic}}".

Rules:
- Every question must be about "{{topic}}"
- Correct answers must be accurate facts about "{{topic}}"
- Wrong answers must be plausible but incorrect alternatives within "{{topic}}" context
- Never use random or unrelated words as distractors

Return ONLY this JSON:
{
  "title": "{{topic}} — Mol urish",
  "items": [
    {"id": 1, "question": "Question about {{topic}}?", "correct_answers": ["correct1 from {{topic}}", "correct2"], "wrong_answers": ["wrong1 related to {{topic}}", "wrong2", "wrong3"]}
  ]
}
P,

            'hangman' => $h . <<<'P'
Generate a hangman word game strictly about "{{topic}}".

Rules:
- Every word must be a key term, concept, or vocabulary item from "{{topic}}"
- The hint must describe the word's meaning within "{{topic}}"
- Words should be 5–12 letters, uppercase, no spaces

Return ONLY this JSON:
{
  "title": "{{topic}} — Harfni top",
  "items": [
    {"id": 1, "word": "KEYWORD", "hint": "This word's role or meaning in {{topic}}"}
  ]
}
P,

            'type_answer' => $h . <<<'P'
Generate a type-the-answer quiz strictly about "{{topic}}".

Rules:
- Every question must test specific knowledge of "{{topic}}"
- The answer must be an accurate fact or term from "{{topic}}"
- Alternatives must be accepted spelling variations or synonyms within "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — Javob yozish",
  "items": [
    {"id": 1, "question": "Question about {{topic}}?", "answer": "exact correct answer", "alternatives": ["accepted variant 1", "accepted variant 2"]}
  ]
}
P,

            'memory_cards' => $h . <<<'P'
Generate a memory card matching game strictly about "{{topic}}".

Rules:
- Front of each card: a term, word, or concept from "{{topic}}"
- Back of each card: its definition, translation, or matching item within "{{topic}}"
- Every card pair must be directly about "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — Xotira kartalar",
  "items": [
    {"id": 1, "front": "Term from {{topic}}", "back": "Definition or match within {{topic}}"}
  ]
}
P,

            'game_show_quiz' => $h . <<<'P'
Generate a game show style quiz strictly about "{{topic}}".

Rules:
- Every question must test knowledge of "{{topic}}" specifically
- The correct answer must be an accurate fact about "{{topic}}"
- All three wrong options must be plausible but incorrect within "{{topic}}" context
- Never use random or unrelated answer choices

Return ONLY this JSON:
{
  "title": "{{topic}} — TV Viktorina",
  "items": [
    {"id": 1, "question": "Question about {{topic}}?", "options": ["Correct answer", "Wrong 1", "Wrong 2", "Wrong 3"], "answer_index": 0}
  ]
}
P,

            'flying_answers' => $h . <<<'P'
Generate a flying answers quiz strictly about "{{topic}}".

Rules:
- Every question must be directly about "{{topic}}"
- The first option (index 0) is always the CORRECT answer about "{{topic}}"
- The other 3 options must be plausible but wrong alternatives related to "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — Uchuvchi javoblar",
  "items": [
    {"id": 1, "question": "Question about {{topic}}?", "options": ["Correct answer", "Wrong 1", "Wrong 2", "Wrong 3"], "answer_index": 0}
  ]
}
P,

            'pair_or_not' => $h . <<<'P'
Generate a "pair or not" decision game strictly about "{{topic}}".

Rules:
- Mix CORRECT pairs (front and back are genuinely related within "{{topic}}") and INCORRECT pairs (front and back do NOT match within "{{topic}}")
- All terms and definitions must still relate to "{{topic}}" — just some pairings are wrong
- Roughly 50% correct, 50% incorrect pairings

Return ONLY this JSON:
{
  "title": "{{topic}} — Juft yoki yo'q?",
  "items": [
    {"id": 1, "front": "Term from {{topic}}", "back": "Its correct definition in {{topic}}", "is_pair": true},
    {"id": 2, "front": "Term from {{topic}}", "back": "Deliberately wrong definition within {{topic}}", "is_pair": false}
  ]
}
P,

            'speed_sort' => $h . <<<'P'
Generate a speed sorting game strictly about "{{topic}}".

Rules:
- Create 2–4 categories that are meaningful WITHIN "{{topic}}"
- Every single item must belong to "{{topic}}" — no unrelated words
- Categories must be clear, distinct sub-groups of "{{topic}}"
- Distribute {{students_count}} total items across groups

Return ONLY this JSON:
{
  "title": "{{topic}} — Tez saralash",
  "groups": [
    {"name": "Subcategory from {{topic}}", "items": ["item from {{topic}}", "item from {{topic}}", "item from {{topic}}"]}
  ]
}
P,

            'spell_word' => $h . <<<'P'
Generate a spell-the-word game strictly about "{{topic}}".

Rules:
- Every word must be an important vocabulary item from "{{topic}}"
- The hint must describe the word in the context of "{{topic}}"
- Words should be 4–15 letters, important terms from "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — So'z imlosi",
  "items": [
    {"id": 1, "word": "KEYWORD", "hint": "Definition or role of this word in {{topic}}"}
  ]
}
P,

            'airplane' => $h . <<<'P'
Generate an airplane quiz game strictly about "{{topic}}".

Rules:
- Every question must test knowledge of "{{topic}}"
- Correct answer (index 0) must be accurate for "{{topic}}"
- Wrong options must be plausible but incorrect within "{{topic}}" context

Return ONLY this JSON:
{
  "title": "{{topic}} — Samolyot o'yini",
  "items": [
    {"id": 1, "question": "Question about {{topic}}?", "options": ["Correct answer", "Wrong 1", "Wrong 2", "Wrong 3"], "answer_index": 0}
  ]
}
P,

            'watch_memorize' => $h . <<<'P'
Generate a watch-and-memorize card game strictly about "{{topic}}".

Rules:
- Front: a term, question, or concept from "{{topic}}"
- Back: its definition, answer, or explanation within "{{topic}}"
- All content must be educational and relevant to "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — Ko'rib eslab qol",
  "items": [
    {"id": 1, "front": "Term or question from {{topic}}", "back": "Answer or definition within {{topic}}"}
  ]
}
P,

            'win_or_lose' => $h . <<<'P'
Generate a win-or-lose mystery box game strictly about "{{topic}}".

Rules:
- WINNER boxes: contain correct facts, terms, or answers about "{{topic}}"
- LOSER boxes: contain incorrect facts, myths, or wrong answers related to "{{topic}}"
- All labels must reference "{{topic}}" content — no random words
- Mix winners and losers roughly 50/50

Return ONLY this JSON:
{
  "title": "{{topic}} — Yut yoki Yoqot",
  "items": [
    {"id": 1, "label": "Correct fact about {{topic}}", "is_winner": true},
    {"id": 2, "label": "Incorrect claim about {{topic}}", "is_winner": false}
  ]
}
P,

            'math_quiz' => $h . <<<'P'
Generate a math quiz strictly about the mathematical topic "{{topic}}".

Rules:
- Every expression must be a problem specifically from "{{topic}}" (e.g., if topic is multiplication tables, generate multiplication)
- The correct answer must be mathematically accurate
- Wrong options must be close but incorrect numbers (plausible distractors)
- All 4 options must be of the same type/scale

Return ONLY this JSON:
{
  "title": "{{topic}} — Matematik test",
  "items": [
    {"id": 1, "expression": "Math expression from {{topic}} = ?", "answer": "correct result", "options": ["correct", "wrong1", "wrong2", "wrong3"]}
  ]
}
P,

            'millionaire' => $h . <<<'P'
Generate a "Who Wants to Be a Millionaire" style quiz strictly about "{{topic}}".

Rules:
- Questions must INCREASE in difficulty about "{{topic}}"
- Every question tests specific knowledge of "{{topic}}"
- Correct answer must be an accurate fact about "{{topic}}"
- Wrong options must be plausible alternatives within "{{topic}}" context
- Prize amounts: 100, 200, 300, 500, 1000, 2000, 4000, 8000, 16000, 32000, 64000, 125000, 250000, 500000, 1000000

Return ONLY this JSON:
{
  "title": "{{topic}} — Millioner",
  "items": [
    {"id": 1, "question": "Easy question about {{topic}}?", "options": ["Correct", "Wrong 1", "Wrong 2", "Wrong 3"], "answer_index": 0, "prize": "100"},
    {"id": 2, "question": "Harder question about {{topic}}?", "options": ["Correct", "Wrong 1", "Wrong 2", "Wrong 3"], "answer_index": 0, "prize": "200"}
  ]
}
P,

            'spelling' => $h . <<<'P'
Generate a spelling practice game strictly about "{{topic}}".

Rules:
- Every word must be an important vocabulary item from "{{topic}}"
- The hint must describe the word in the context of "{{topic}}"
- The example sentence must use the word naturally in a sentence about "{{topic}}"
- Use ___ to mark where the word goes in the sentence

Return ONLY this JSON:
{
  "title": "{{topic}} — Imlo",
  "items": [
    {"id": 1, "word": "KEYWORD", "hint": "Description within {{topic}}", "sentence": "Example sentence about {{topic}} using ___ here"}
  ]
}
P,

            'diagram' => $h . <<<'P'
Generate a diagram labeling activity strictly about "{{topic}}".

Rules:
- Parts must be real components, sections, or elements of "{{topic}}"
- Labels must be accurate terminology from "{{topic}}"
- Descriptions must explain each part's role within "{{topic}}"

Return ONLY this JSON:
{
  "title": "{{topic}} — Diagramma",
  "description": "Quyidagi \"{{topic}}\" qismlarini aniqlang",
  "parts": [
    {"id": 1, "label": "Component of {{topic}}", "description": "Role or function of this part in {{topic}}"}
  ]
}
P,

        ];
    }

    // ─── Run ────────────────────────────────────────────────────────────────
    public function run(): void
    {
        $prompts  = $this->prompts();
        $updated  = 0;
        $skipped  = 0;

        foreach ($prompts as $code => $promptText) {
            $template = DB::table('game_templates')->where('code', $code)->first();

            if (!$template) {
                $this->command?->warn("Template not found: {$code}");
                $skipped++;
                continue;
            }

            // Deactivate all existing versions
            DB::table('prompt_versions')
                ->where('template_id', $template->id)
                ->update(['status' => 'inactive']);

            // Upsert v2 active version
            $existing = DB::table('prompt_versions')
                ->where('template_id', $template->id)
                ->where('version', 2)
                ->first();

            if ($existing) {
                DB::table('prompt_versions')
                    ->where('id', $existing->id)
                    ->update(['prompt_text' => $promptText, 'status' => 'active', 'updated_at' => now()]);
            } else {
                DB::table('prompt_versions')->insert([
                    'template_id' => $template->id,
                    'version'     => 2,
                    'prompt_text' => $promptText,
                    'status'      => 'active',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            $this->command?->info("Updated: {$code}");
            $updated++;
        }

        $this->command?->info("Done. Updated: {$updated}, Skipped: {$skipped}");
    }
}
