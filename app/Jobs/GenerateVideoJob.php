<?php

namespace App\Jobs;

use App\Models\AiVideoRequest;
use App\Services\VideoGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 30;

    public function __construct(public AiVideoRequest $request)
    {
    }

    public function handle(VideoGenerationService $videoService): void
    {
        $req = $this->request;

        // Video provideriga yuborish
        $result = $videoService->generate(
            prompt: $req->video_prompt,
            style:  $req->video_style,
        );

        $providerRequestId = $result['provider_request_id'] ?? null;
        $status            = $result['status'] ?? 'processing';
        $videoUrl          = $result['video_url'] ?? null;

        // Provider so'rovini saqlash
        $req->update([
            'provider_name'       => config('ai_video.video_provider'),
            'provider_request_id' => $providerRequestId,
        ]);

        // Agar provider darhol tayyor bo'lsa (mock yoki sync API)
        if ($status === 'completed' && $videoUrl) {
            $req->markAs(AiVideoRequest::STATUS_COMPLETED, [
                'video_url' => $videoUrl,
            ]);

            Log::info('GenerateVideoJob: video tayyor', [
                'request_id' => $req->id,
                'video_url'  => $videoUrl,
            ]);

            return;
        }

        // Async provider — status hali processing, keyin polling qilish kerak.
        // Hozircha "generating" holatida qoladi va frontend polling qiladi.
        Log::info('GenerateVideoJob: video qayta ishlanmoqda', [
            'request_id'          => $req->id,
            'provider_request_id' => $providerRequestId,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->request->markAs(AiVideoRequest::STATUS_FAILED, [
            'error_message' => 'Video yaratishda xato: ' . $e->getMessage(),
        ]);

        Log::error('GenerateVideoJob xato', [
            'request_id' => $this->request->id,
            'error'      => $e->getMessage(),
        ]);
    }
}
