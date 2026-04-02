<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;

class ProblemSolverService
{
    /**
     * Masalani yechadi.
     * VIDEO_PROVIDER=grok bo'lsa → Grok API ishlatadi (chat completions).
     * Aks holda → OpenAI (GPT) ishlatadi.
     */
    public function solve(string $subject, string $topic, string $problemText, string $language = 'uz'): array
    {
        $provider  = config('ai_video.video_provider', 'mock');
        $systemPrompt = $this->buildSystemPrompt($subject, $language);
        $userInput    = $this->buildUserMessage($subject, $topic, $problemText);

        $raw = $provider === 'grok'
            ? $this->callGrok($systemPrompt, $userInput)
            : $this->callOpenAi($systemPrompt, $userInput);

        $raw = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $raw);

        if (str_starts_with($raw, '```')) {
            $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/', '', $raw);
            $raw = trim($raw);
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('AI noto\'g\'ri JSON qaytardi: ' . json_last_error_msg());
        }

        if (empty($decoded['steps']) || !\is_array($decoded['steps'])) {
            throw new \RuntimeException("AI javobi noto'g'ri formatda: 'steps' maydoni yo'q.");
        }

        return $decoded;
    }

    // ── API chaqiruvlar ───────────────────────────────────────────────────────

    private function callOpenAi(string $systemPrompt, string $userInput): string
    {
        $model = AiSetting::get('model', config('ai_video.openai_model'));

        $response = OpenAI::chat()->create([
            'model'           => $model,
            'messages'        => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userInput],
            ],
            'max_tokens'      => 2500,
            'temperature'     => 0.4,
            'response_format' => ['type' => 'json_object'],
        ]);

        return trim($response->choices[0]->message->content);
    }

    private function callGrok(string $systemPrompt, string $userInput): string
    {
        $apiKey  = config('ai_video.grok.api_key');
        $apiUrl  = config('ai_video.grok.api_url');
        $timeout = (int) config('ai_video.grok.timeout', 60);

        if (empty($apiKey)) {
            throw new \RuntimeException('GROK_API_KEY sozlanmagan. .env faylni tekshiring.');
        }

        $model = config('ai_video.grok.solver_model', 'grok-3');

        $response = Http::withToken($apiKey)
            ->timeout($timeout)
            ->post("{$apiUrl}/chat/completions", [
                'model'       => $model,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userInput],
                ],
                'max_tokens'  => 2500,
                'temperature' => 0.4,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Grok API xatosi (' . $response->status() . '): ' . $response->body());
        }

        return trim($response->json('choices.0.message.content') ?? '');
    }

    // ── Prompt qurish ─────────────────────────────────────────────────────────

    private function buildSystemPrompt(string $subject, string $language): string
    {
        $langLabel    = match ($language) {
            'en'    => 'English',
            'ru'    => 'Russian',
            default => "Uzbek (o'zbek tilida)",
        };

        $subjectHints = $this->getSubjectHints($subject);

        return <<<PROMPT
You are an expert {$subject} teacher who explains problems clearly for students.
Your job is to solve the given problem step by step and return ONLY a valid JSON object.

Language of explanation: {$langLabel}

{$subjectHints}

REQUIRED JSON FORMAT (return ONLY this, no extra text):
{
  "subject": "fan nomi",
  "topic": "mavzu nomi",
  "steps": [
    {
      "step": 1,
      "title": "Qadam nomi",
      "explanation": "Batafsil tushuntirish",
      "formula": "Agar formula bo'lsa (aks holda null)",
      "note": "Qo'shimcha eslatma (ixtiyoriy, aks holda null)"
    }
  ],
  "final_answer": "Yakuniy javob aniq va qisqa",
  "uzbek_summary": "Butun yechimning o'zbek tilidagi qisqacha xulosasi (2-3 gap)",
  "key_concepts": ["tushuncha1", "tushuncha2"],
  "difficulty_hint": "Ushbu masalaning qiyin joyi: ..."
}

RULES:
- steps must have at least 2 elements
- Every explanation must be in {$langLabel}
- formulas use plain text (e.g. x^2 + 5x + 6 = 0)
- Be concise but complete
- Return ONLY valid JSON, no markdown, no extra text
PROMPT;
    }

    private function buildUserMessage(string $subject, string $topic, string $problemText): string
    {
        return <<<MSG
Fan: {$subject}
Mavzu: {$topic}
Masala/Savol: {$problemText}

Iltimos shu masalani qadam-baqadam yeching va JSON formatda qaytaring.
MSG;
    }

    private function getSubjectHints(string $subject): string
    {
        return match ($subject) {
            'mathematics', 'algebra' =>
                'MATH HINTS: Show all calculation steps. Include formulas. Explain WHY each step is done.',
            'geometry' =>
                'GEOMETRY HINTS: Describe geometric shapes and spatial reasoning. Reference angles, sides, theorems by name.',
            'physics' =>
                'PHYSICS HINTS: State the physical law/principle used. Include units (m, s, kg, N). Show formula derivation.',
            'chemistry' =>
                'CHEMISTRY HINTS: Show chemical equations. Explain atomic/molecular concepts. Include valence/bonds if relevant.',
            'biology' =>
                'BIOLOGY HINTS: Explain biological processes. Use proper scientific terms. Relate to real-life examples.',
            'history' =>
                'HISTORY HINTS: Provide dates, key figures, causes and effects. Explain historical significance.',
            'geography' =>
                'GEOGRAPHY HINTS: Reference maps, coordinates, climate zones, or geographic features as needed.',
            'language', 'english' =>
                'LANGUAGE HINTS: Explain grammar rules clearly. Provide examples. Show correct vs incorrect usage.',
            default =>
                'Explain clearly and logically for a student. Use simple language.',
        };
    }
}
