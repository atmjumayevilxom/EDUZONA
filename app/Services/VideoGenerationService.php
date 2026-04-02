<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoGenerationService
{
    /**
     * Video generatsiya so'rovini tashqi API ga yuboradi.
     * Grok uchun async — faqat request_id qaytaradi.
     * Mock uchun darhol completed qaytaradi.
     */
    public function generate(string $prompt, string $style = 'blackboard'): array
    {
        $provider = config('ai_video.video_provider', 'mock');

        return match ($provider) {
            'grok'  => $this->generateWithGrok($prompt, $style),
            default => $this->generateMock($prompt),
        };
    }

    /**
     * Video statusini tashqi API dan tekshiradi.
     */
    public function checkStatus(string $providerRequestId): array
    {
        $provider = config('ai_video.video_provider', 'mock');

        return match ($provider) {
            'grok'  => $this->checkGrokStatus($providerRequestId),
            default => ['status' => 'completed', 'video_url' => null],
        };
    }

    // ── Grok (xAI) ───────────────────────────────────────────────────────────

    private function generateWithGrok(string $prompt, string $style = 'blackboard'): array
    {
        $apiKey = env('GROK_API_KEY');
        $apiUrl = env('GROK_API_URL', 'https://api.x.ai/v1');

        Log::info('Grok video so\'rovi yuborilmoqda', ['prompt_len' => strlen($prompt)]);

        $duration = (int) (\App\Models\AiSetting::where('key', 'video_duration')->value('value') ?? 15);

        $response = Http::withToken($apiKey)
            ->timeout(20)
            ->post("{$apiUrl}/videos/generations", [
                'model'    => 'grok-imagine-video',
                'prompt'   => $prompt,
                'duration' => $duration,
            ]);

        if ($response->failed()) {
            Log::error('Grok video generate xato', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Grok video API xato: ' . $response->status() . ' — ' . $response->body());
        }

        $requestId = $response->json('request_id');

        if (!$requestId) {
            throw new \RuntimeException('Grok video API request_id qaytarmadi: ' . $response->body());
        }

        Log::info('Grok video so\'rovi qabul qilindi', ['request_id' => $requestId]);

        return [
            'provider_request_id' => $requestId,
            'status'              => 'processing',
            'video_url'           => null,
        ];
    }

    private function checkGrokStatus(string $providerRequestId): array
    {
        $apiKey = env('GROK_API_KEY');
        $apiUrl = env('GROK_API_URL', 'https://api.x.ai/v1');

        $response = Http::withToken($apiKey)
            ->timeout(10)
            ->get("{$apiUrl}/videos/{$providerRequestId}");

        if ($response->failed()) {
            Log::warning('Grok video status tekshirishda xato', [
                'request_id' => $providerRequestId,
                'status'     => $response->status(),
            ]);
            return ['status' => 'processing', 'video_url' => null, 'progress' => 0];
        }

        $data     = $response->json();
        $status   = $data['status']   ?? 'pending';
        $progress = $data['progress'] ?? 0;

        Log::info('Grok video status', [
            'request_id' => $providerRequestId,
            'status'     => $status,
            'progress'   => $progress,
        ]);

        if ($status === 'done') {
            $videoUrl = $data['video']['url'] ?? null;
            return [
                'status'    => 'completed',
                'video_url' => $videoUrl,
                'progress'  => 100,
            ];
        }

        if ($status === 'failed' || $status === 'error') {
            return [
                'status'    => 'failed',
                'video_url' => null,
                'progress'  => 0,
            ];
        }

        // pending | processing
        return [
            'status'    => 'processing',
            'video_url' => null,
            'progress'  => $progress,
        ];
    }

    // ── Mock ─────────────────────────────────────────────────────────────────

    private function generateMock(string $prompt = ''): array
    {
        return [
            'provider_request_id' => 'mock_' . uniqid(),
            'status'              => 'completed',
            'video_url'           => null,
        ];
    }
}
