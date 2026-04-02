<?php

namespace App\Jobs;

use App\Models\AiVideoRequest;
use App\Services\ProblemSolverService;
use App\Services\VideoPromptBuilderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SolveProblemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 15;

    public function __construct(public AiVideoRequest $request)
    {
    }

    public function handle(ProblemSolverService $solver, VideoPromptBuilderService $promptBuilder): void
    {
        // Queue sog'ligini kuzatish
        Cache::put('queue_last_processed', now()->toIso8601String(), 300);

        $req = $this->request;

        // 1. GPT masalani yechsin
        $req->markAs(AiVideoRequest::STATUS_SOLVING);

        $solution = $solver->solve(
            subject:     $req->subject,
            topic:       $req->topic,
            problemText: $req->problem_text,
            language:    $req->language,
        );

        // 2. Yechimni saqlash
        $req->markAs(AiVideoRequest::STATUS_BUILDING_PROMPT, [
            'solution_json' => $solution,
        ]);

        // 3. Video promptini qurish
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

        // 4. Video generatsiya jobini ishga tushirish
        GenerateVideoJob::dispatch($req);

        Log::info('SolveProblemJob tugadi', [
            'request_id' => $req->id,
            'subject'    => $req->subject,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->request->markAs(AiVideoRequest::STATUS_FAILED, [
            'error_message' => $e->getMessage(),
        ]);

        Log::error('SolveProblemJob xato', [
            'request_id' => $this->request->id,
            'error'      => $e->getMessage(),
        ]);
    }
}
