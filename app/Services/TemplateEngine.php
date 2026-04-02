<?php

namespace App\Services;

use App\Models\GameTemplate;
use App\Models\PromptVersion;

class TemplateEngine
{
    public function getActivePromptVersion(GameTemplate $template): PromptVersion
    {
        $version = $template->promptVersions()->where('status', 'active')->latest()->first();

        if (!$version) {
            throw new \RuntimeException("No active prompt version for template: {$template->code}");
        }

        return $version;
    }

    public function buildPrompt(PromptVersion $version, array $input): string
    {
        $prompt = $version->prompt_text;

        foreach ($input as $key => $value) {
            $prompt = str_replace('{{' . $key . '}}', $value, $prompt);
        }

        return $prompt;
    }

    /**
     * Lightweight topic-relevance check.
     * Extracts all string content from JSON and verifies at least one
     * topic keyword appears — catches obvious off-topic generations.
     */
    public function validateTopicRelevance(array $json, string $topic): bool
    {
        // Extract all string values recursively from the generated JSON
        $allText = implode(' ', $this->extractStrings($json));
        $allText = mb_strtolower($allText);

        // Split topic into keywords (min 3 chars), skip noise words
        $noiseWords = ['the', 'and', 'for', 'with', 'about', 'this', 'that', 'from'];
        $keywords   = array_filter(
            preg_split('/[\s\-_,;:]+/u', mb_strtolower($topic)),
            fn($w) => mb_strlen($w) >= 3 && !in_array($w, $noiseWords)
        );

        if (empty($keywords)) {
            return true; // Cannot check — pass
        }

        foreach ($keywords as $keyword) {
            if (str_contains($allText, $keyword)) {
                return true;
            }
        }

        return false; // None of the topic keywords found in generated content
    }

    private function extractStrings(array $data): array
    {
        $strings = [];
        array_walk_recursive($data, function ($value) use (&$strings) {
            if (is_string($value) && mb_strlen($value) > 1) {
                $strings[] = $value;
            }
        });
        return $strings;
    }

    public function validateOutput(array $json, GameTemplate $template): bool
    {
        $schema = $template->output_schema;

        if (empty($schema['required'])) {
            return true;
        }

        foreach ($schema['required'] as $field) {
            if (!array_key_exists($field, $json)) {
                return false;
            }
        }

        if (isset($schema['item_fields']) && isset($json['items'])) {
            if (!is_array($json['items']) || count($json['items']) === 0) {
                return false;
            }
            foreach ($json['items'] as $item) {
                foreach ($schema['item_fields'] as $field) {
                    if (!array_key_exists($field, $item)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
