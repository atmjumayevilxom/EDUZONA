<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateAiVideoRequest;
use App\Models\AiVideoRequest;
use App\Services\ProblemSolverService;
use App\Services\VideoGenerationService;
use App\Services\VideoPromptBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiVideoController extends Controller
{
    /**
     * POST /api/ai-video/generate
     * 1) Masalani yechadi (synchronous ~10s)
     * 2) Grok video so'rovini yuboradi (async — request_id oladi)
     * 3) Darhol {solution + status:generating} qaytaradi
     * Frontend yechimni ko'rsatadi va video uchun polling qiladi.
     */
    public function generate(
        GenerateAiVideoRequest $request,
        ProblemSolverService $solver,
        VideoPromptBuilderService $promptBuilder,
        VideoGenerationService $videoService,
    ): JsonResponse {
        $user = $request->user();

        $dailyLimit = config('ai_video.daily_limit', 5);
        $todayCount = AiVideoRequest::where('user_id', $user->id)
            ->whereDate('created_at', today())
            ->whereNotIn('status', [AiVideoRequest::STATUS_FAILED])
            ->count();

        if ($todayCount >= $dailyLimit) {
            return response()->json([
                'message' => "Kunlik limit tugadi ({$dailyLimit} ta). Ertaga qaytib keling.",
            ], 429);
        }

        $req = AiVideoRequest::create([
            'user_id'            => $user->id,
            'subject'            => $request->input('subject'),
            'topic'              => '', // AI yechimdan to'ldiriladi
            'problem_text'       => $request->input('problem_text'),
            'video_style'        => $request->input('video_style', 'blackboard'),
            'explanation_length' => $request->input('explanation_length', 'medium'),
            'voice_style'        => $request->input('voice_style', 'calm'),
            'language'           => $request->input('language', 'uz'),
            'status'             => AiVideoRequest::STATUS_PENDING,
        ]);

        try {
            // ── 1. Masalani yech ───────────────────────────────────────────
            $req->markAs(AiVideoRequest::STATUS_SOLVING);

            $solution = $solver->solve(
                subject:     $req->subject,
                topic:       '',
                problemText: $req->problem_text,
                language:    $req->language,
            );

            // Topic ni AI yechimidan olamiz
            $req->markAs(AiVideoRequest::STATUS_BUILDING_PROMPT, [
                'topic'        => $solution['topic'] ?? $req->subject,
                'solution_json' => $solution,
            ]);

            // ── 2. Video promptini qur ─────────────────────────────────────
            $videoPrompt = $promptBuilder->build(
                solution:          $solution,
                videoStyle:        $req->video_style,
                explanationLength: $req->explanation_length,
                voiceStyle:        $req->voice_style,
                language:          $req->language,
            );

            $req->markAs(AiVideoRequest::STATUS_GENERATING, [
                'video_prompt' => $videoPrompt,
            ]);

            // ── 3. Grok video API ga so'rov yuborish (async) ───────────────
            $result = $videoService->generate($videoPrompt, $req->video_style);

            $req->update([
                'provider_name'       => config('ai_video.video_provider', 'grok'),
                'provider_request_id' => $result['provider_request_id'] ?? null,
            ]);

            // Mock yoki sync provider darhol tugasa — completed qilamiz
            if (($result['status'] ?? '') === 'completed') {
                $req->markAs(AiVideoRequest::STATUS_COMPLETED, [
                    'video_url' => $result['video_url'] ?? null,
                ]);
            }

            $req->refresh();

            return response()->json([
                'id'           => $req->id,
                'status'       => $req->status,
                'subject'      => $req->subject,
                'topic'        => $req->topic,
                'problem_text' => $req->problem_text,
                'solution'     => $req->solution_json,
                'video_url'    => $req->video_url,
                'progress'     => 0,
                'completed_at' => $req->completed_at?->toIso8601String(),
            ], 201);

        } catch (\Throwable $e) {
            $req->markAs(AiVideoRequest::STATUS_FAILED, [
                'error_message' => $e->getMessage(),
            ]);

            Log::error('AI video generate xato', ['id' => $req->id, 'error' => $e->getMessage()]);

            // Yechim bor bo'lsa fallback sifatida qaytaramiz
            $req->refresh();
            if ($req->solution_json) {
                return response()->json([
                    'id'           => $req->id,
                    'status'       => 'completed',
                    'subject'      => $req->subject,
                    'topic'        => $req->topic,
                    'problem_text' => $req->problem_text,
                    'solution'     => $req->solution_json,
                    'video_url'    => null,
                    'completed_at' => now()->toIso8601String(),
                ], 201);
            }

            return response()->json([
                'message' => 'Xato yuz berdi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ai-video/{id}/status
     * Grok video statusini tekshiradi va DB ni yangilaydi.
     * Frontend har 5 soniyada shu endpointni chaqiradi.
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $req = AiVideoRequest::forUser($request->user()->id)->findOrFail($id);

        // Hali generating holatida bo'lsa — Grok API ni tekshir
        if (
            $req->status === AiVideoRequest::STATUS_GENERATING
            && $req->provider_request_id
        ) {
            try {
                $videoService = app(VideoGenerationService::class);
                $result = $videoService->checkStatus($req->provider_request_id);

                if ($result['status'] === 'completed') {
                    $req->markAs(AiVideoRequest::STATUS_COMPLETED, [
                        'video_url' => $result['video_url'],
                    ]);
                } elseif ($result['status'] === 'failed') {
                    // Video muvaffaqiyatsiz — yechim bor bo'lsa completed deb belgilaymiz (fallback)
                    $req->markAs(AiVideoRequest::STATUS_COMPLETED, [
                        'video_url'     => null,
                        'error_message' => 'Video yaratilmadi, yechim ko\'rsatilmoqda.',
                    ]);
                }

                $req->refresh();
            } catch (\Throwable $e) {
                Log::warning('Grok status tekshirishda xato', ['id' => $id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'id'           => $req->id,
            'status'       => $req->status,
            'subject'      => $req->subject,
            'topic'        => $req->topic,
            'problem_text' => $req->problem_text,
            'video_url'    => $req->video_url,
            'solution'     => $req->solution_json,
            'error'        => $req->error_message,
            'completed_at' => $req->completed_at?->toIso8601String(),
        ]);
    }

    /**
     * GET /api/ai-video/history
     */
    public function history(Request $request): JsonResponse
    {
        $items = AiVideoRequest::forUser($request->user()->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id'            => $r->id,
                'subject'       => $r->subject,
                'subject_label' => $r->subject_label,
                'topic'         => $r->topic,
                'status'        => $r->status,
                'status_label'  => $r->status_label,
                'video_url'     => $r->video_url,
                'created_at'    => $r->created_at->toIso8601String(),
                'completed_at'  => $r->completed_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $items]);
    }

    /**
     * GET /api/ai-video/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $r = AiVideoRequest::forUser($request->user()->id)->findOrFail($id);

        return response()->json([
            'id'            => $r->id,
            'subject'       => $r->subject,
            'subject_label' => $r->subject_label,
            'topic'         => $r->topic,
            'problem_text'  => $r->problem_text,
            'status'        => $r->status,
            'status_label'  => $r->status_label,
            'solution_json' => $r->solution_json,
            'video_url'     => $r->video_url,
            'created_at'    => $r->created_at->toIso8601String(),
            'completed_at'  => $r->completed_at?->toIso8601String(),
        ]);
    }
}
