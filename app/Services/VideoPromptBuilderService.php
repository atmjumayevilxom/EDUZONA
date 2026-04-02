<?php

namespace App\Services;

class VideoPromptBuilderService
{
    /**
     * Grok grok-imagine-video uchun professional ta'limiy prompt quradi.
     *
     * Model: 8 soniyalik vizual klip generatori.
     * Ovoz/narration model tomonidan qo'shilmaydi — vizual animatsiya asosiy.
     * Prompt: qisqa, vizual, aniq — model tushunishi uchun.
     */
    public function build(
        array  $solution,
        string $videoStyle       = 'blackboard',
        string $explanationLength = 'medium',
        string $voiceStyle       = 'calm',
        string $language         = 'uz'
    ): string {
        $prefix = trim(\App\Models\AiSetting::where('key', 'video_prompt_prefix')->value('value') ?? '');
        $suffix = trim(\App\Models\AiSetting::where('key', 'video_prompt_suffix')->value('value') ?? 'Academic chalkboard style. 720p quality.');
        $topic       = $solution['topic']        ?? '';
        $subject     = $solution['subject']      ?? '';
        $finalAnswer = $solution['final_answer'] ?? '';
        $steps       = $solution['steps']        ?? [];

        $mainFormula = $this->extractMainFormula($steps);
        $allFormulas = $this->extractAllFormulas($steps, 3);
        $stepTitles  = $this->extractStepTitles($steps, 4);
        $givenData   = $this->extractGivenData($steps);
        $subjectHint = $this->subjectVisuals($subject);

        $langLabel = match ($language) {
            'uz' => "O'zbek",
            'ru' => 'Russian',
            'en' => 'English',
            default => "O'zbek",
        };

        $styleBg = match ($videoStyle) {
            'blackboard' => 'Black chalkboard background, white chalk handwriting',
            'animated'   => 'Clean white background, colorful animated text',
            'minimal'    => 'White background, black pen handwriting, minimal style',
            default      => 'Black chalkboard background, white chalk handwriting',
        };

        $pace = match ($voiceStyle) {
            'energetic' => 'dynamic fast-paced animation',
            default     => 'calm slow teacher-style animation',
        };

        $density = match ($explanationLength) {
            'short' => 'show only key formula and answer',
            'long'  => 'show all steps in detail with notes',
            default => 'show main steps and final answer',
        };

        // ── Prompt ────────────────────────────────────────────────────────────
        $parts = [];

        $parts[] = "{$styleBg}, educational science video, {$pace}";
        $parts[] = "All text in {$langLabel} language";
        $parts[] = "Layout: top title \"{$topic}\", middle active step, bottom previous results";

        if ($givenData) {
            $parts[] = "Step 1: given data: {$givenData}";
        }
        if ($mainFormula) {
            $parts[] = "Step 2: formula with handwriting animation: {$mainFormula}";
        }
        if (!empty($allFormulas)) {
            $parts[] = "Step 3: calculations: " . implode(' then ', $allFormulas);
        } elseif (!empty($stepTitles)) {
            $parts[] = "Step 3: " . implode(', ', $stepTitles);
        }
        if ($finalAnswer) {
            $parts[] = "Step 4: final answer large underlined: {$finalAnswer}";
        }

        $parts[] = $subjectHint;
        $parts[] = "Each element appears slowly, old steps fade out, {$density}";
        $parts[] = "Max 3-4 elements visible, clean minimalist board, 720p academic";

        $prompt = implode('. ', $parts) . '.';

        if ($prefix !== '') {
            $prompt = $prefix . ' ' . $prompt;
        }
        if ($suffix !== '') {
            $prompt = $prompt . ' ' . $suffix;
        }

        return $prompt;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function extractMainFormula(array $steps): string
    {
        foreach ($steps as $step) {
            if (!empty($step['formula'])) {
                return $step['formula'];
            }
        }
        return '';
    }

    private function extractAllFormulas(array $steps, int $max): array
    {
        $formulas = [];
        foreach ($steps as $step) {
            if (!empty($step['formula'])) {
                $formulas[] = $step['formula'];
                if (count($formulas) >= $max) break;
            }
        }
        return $formulas;
    }

    private function extractStepTitles(array $steps, int $max): array
    {
        $titles = [];
        foreach (array_slice($steps, 0, $max) as $step) {
            if (!empty($step['title'])) {
                $titles[] = $step['title'];
            }
        }
        return $titles;
    }

    private function extractGivenData(array $steps): string
    {
        // Birinchi qadamdan berilgan ma'lumotlar
        $first = $steps[0] ?? null;
        if (!$first) return '';

        $explanation = $first['explanation'] ?? '';
        // 80 belgidan qisqa qilib kesish
        if (strlen($explanation) > 80) {
            $explanation = substr($explanation, 0, 80) . '...';
        }
        return $explanation;
    }

    private function subjectVisuals(string $subject): string
    {
        return match ($subject) {
            'mathematics', 'algebra' =>
                'Mathematical equations, numbers and symbols in chalk style',
            'geometry' =>
                'Geometric shapes drawn with chalk, labeled sides and angles',
            'physics' =>
                'Physics diagrams with force arrows, motion vectors, formulas with units',
            'chemistry' =>
                'Chemical equations, molecular structures, reaction arrows in chalk',
            'biology' =>
                'Biological diagrams with labeled parts, scientific terminology',
            'history', 'geography' =>
                'Educational text, dates and key terms highlighted in chalk',
            'informatics' =>
                'Algorithm flowchart or code snippet written in chalk style',
            default =>
                'Educational diagrams and formulas in chalk writing style',
        };
    }
}
