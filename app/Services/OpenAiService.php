<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAiService
{
    public function generate(string $prompt, string $model = 'gpt-4o-mini', int $maxTokens = 2000): array
    {
        $response = OpenAI::chat()->create([
            'model'           => $model,
            'messages'        => [
                [
                    'role'    => 'system',
                    'content' => 'You are an expert educational content creator for AI-powered classroom games. Your ONLY job is to generate content that is 100% directly and specifically about the TOPIC given in the user message. ABSOLUTE RULES: (1) Every single word, question, answer, hint, option, pair, label, fact, and example MUST be explicitly about the given TOPIC — no exceptions. (2) NEVER generate random vocabulary, generic filler, or loosely related content. (3) If the topic is a grammar concept (e.g. "To be"), generate ONLY sentences/examples using that grammar rule. (4) If the topic is a subject (e.g. "Solar System"), generate ONLY facts about that subject. (5) Wrong/distractor options must still be plausible alternatives WITHIN the topic domain. (6) Return ONLY valid JSON with no explanation, markdown, or extra text.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens'      => $maxTokens,
            'temperature'     => 0.3,
            'response_format' => ['type' => 'json_object'],
        ]);

        $content = trim($response->choices[0]->message->content);

        // Remove control characters that can break json_decode
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);

        // Strip markdown code fences if model wrapped response
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('OpenAI returned invalid JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
