<?php

namespace App\Jobs;

use App\Models\AiSetting;
use App\Models\AuditLog;
use App\Models\Game;
use App\Services\OpenAiService;
use App\Services\TemplateEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class GenerateGameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public Game $game)
    {
    }

    public function handle(TemplateEngine $engine, OpenAiService $openAi): void
    {
        // Queue worker sog'ligini kuzatish uchun — health check endpoint ishlatadi
        Cache::put('queue_last_processed', now()->toIso8601String(), 300);

        $game = $this->game->load(['template', 'promptVersion']);
        $template = $game->template;
        $promptVersion = $game->promptVersion ?? $engine->getActivePromptVersion($template);

        $difficultyLabel = match ($game->difficulty ?? 'medium') {
            'easy'  => 'oson (boshlang\'ich daraja)',
            'hard'  => 'qiyin (yuqori daraja)',
            default => 'o\'rtacha (standart daraja)',
        };

        $input = [
            'topic'          => $game->topic,
            'students_count' => min($game->students_count, 30),
            'language'       => $game->language,
            'grade'          => '',
            'difficulty'     => $difficultyLabel,
        ];

        $prompt    = $engine->buildPrompt($promptVersion, $input);
        $model     = AiSetting::get('model', 'gpt-4o-mini');
        $maxTokens = $this->calcMaxTokens($template, $input['students_count']);

        // Single attempt per job run — Laravel retries automatically up to $tries=3 with $backoff=10s
        $generated = $openAi->generate($prompt, $model, $maxTokens);

        // Normalize output: inject missing top-level fields that can be derived
        $generated = $this->normalizeOutput($generated, $game->topic, $template->output_schema ?? []);

        if (!$engine->validateOutput($generated, $template)) {
            throw new \RuntimeException('Generated JSON failed schema validation.');
        }

        if (!$engine->validateTopicRelevance($generated, $game->topic)) {
            throw new \RuntimeException("Generated content appears unrelated to topic: \"{$game->topic}\". Retrying.");
        }

        $game->update([
            'generated_json'    => $generated,
            'status'            => 'ready',
            'prompt_version_id' => $promptVersion->id,
        ]);

        AuditLog::log('game.generated', $game->user_id, 'Game', $game->id, [
            'template' => $template->code,
            'topic'    => $game->topic,
        ]);
    }

    /**
     * Repair common schema mismatches that are trivially fixable:
     * - Missing "title" → inject from topic
     * - Items missing "id" → auto-number them
     * This avoids wasting retries on easily-correctable AI output.
     */
    private function normalizeOutput(array $data, string $topic, array $schema): array
    {
        $required = $schema['required'] ?? [];

        // Inject missing title from game topic
        if (in_array('title', $required) && !isset($data['title'])) {
            $data['title'] = $topic;
        }

        // Auto-number items missing an "id" field
        if (isset($data['items']) && is_array($data['items'])) {
            $itemFields = $schema['item_fields'] ?? [];
            if (in_array('id', $itemFields)) {
                foreach ($data['items'] as $i => &$item) {
                    if (is_array($item) && !isset($item['id'])) {
                        $item['id'] = $i + 1;
                    }
                }
                unset($item);
            }
        }

        return $data;
    }

    /**
     * Per-template token budget: base + (perItem × items), capped 600–4000.
     * Avval DB dan o'qiydi (admin paneldan boshqariladi).
     * DB da qiymat yo'q bo'lsa, fallback jadvaldan oladi.
     * 40% xavfsizlik chegarasi qo'shilgan.
     */
    private function calcMaxTokens(\App\Models\GameTemplate $template, int $items): int
    {
        // DB da to'g'ridan-to'g'ri saqlangan budjet bormi tekshirish
        $base    = $template->token_budget_base    ?? 0;
        $perItem = $template->token_budget_per_item ?? 0;

        // DB da default qiymatlar (0) bo'lsa, fallback jadvaldan olish
        if ($base === 0 || $perItem === 0) {
            // [base, perItem] — amaliy o'lchovlarga asoslangan
            $fallback = [
                'word_search'       => [200,  18],
                'hangman'           => [200,  22],
                'anagram'           => [200,  22],
                'spell_word'        => [200,  28],
                'random_wheel'      => [250,  55],
                'open_box'          => [200,  25],
                'flashcards'        => [200,  35],
                'matching_pairs'    => [200,  30],
                'memory_cards'      => [200,  35],
                'watch_memorize'    => [200,  35],
                'pair_or_not'       => [200,  38],
                'speed_sort'        => [220,  32],
                'group_sort'        => [220,  32],
                'true_false'        => [220,  55],
                'type_answer'       => [220,  65],
                'reorder'           => [220,  55],
                'diagram'           => [220,  55],
                'complete_sentence' => [250,  75],
                'spelling'          => [250,  70],
                'quiz_mcq'          => [250,  90],
                'game_show_quiz'    => [250,  85],
                'flying_answers'    => [250,  85],
                'airplane'          => [250,  85],
                'whack_mole'        => [250, 100],
                'math_quiz'         => [220,  70],
                'millionaire'       => [280, 100],
            ];

            [$base, $perItem] = $fallback[$template->code] ?? [300, 90];
        }

        $tokens = (int) round(($base + $perItem * $items) * 1.4); // 40% xavfsizlik

        return max(600, min(4000, $tokens));
    }

    public function failed(\Throwable $exception): void
    {
        // error_message ni saqlash — foydalanuvchi xato sababini ko'rishi uchun
        $this->game->update([
            'status'        => 'error',
            'error_message' => $exception->getMessage(),
        ]);

        AuditLog::log('game.generation_failed', $this->game->user_id, 'Game', $this->game->id, [
            'error'    => $exception->getMessage(),
            'template' => $this->game->template?->code,
        ]);
    }
}
