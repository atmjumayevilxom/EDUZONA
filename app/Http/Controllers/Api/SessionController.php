<?php

namespace App\Http\Controllers\Api;

use App\Events\SessionEnded;
use App\Events\SessionResultSubmitted;
use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\SessionResult;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function show(string $code)
    {
        $session = GameSession::where('session_code', $code)
            ->with(['game.template:id,code,name,renderer_component', 'game:id,topic,generated_json,status,template_id'])
            ->firstOrFail();

        return response()->json(['data' => $session]);
    }

    public function submitResult(string $code, Request $request)
    {
        $session = GameSession::where('session_code', $code)->firstOrFail();

        if ($session->status === 'ended') {
            return response()->json(['message' => 'Sessiya tugagan. Natija qabul qilinmaydi.'], 422);
        }

        $validated = $request->validate([
            'participant_name' => ['required', 'string', 'max:100'],
            'score'            => ['required', 'integer', 'min:0'],
            'total'            => ['nullable', 'integer', 'min:0'],
            'answers_json'     => ['nullable', 'array'],
            'student_token'    => ['nullable', 'string', 'max:36'],  // localStorage UUID
        ]);

        $name  = trim($validated['participant_name']);
        $token = $validated['student_token'] ?? null;

        // Dublikat tekshiruvi: bir sessiyada bir xil nom (katta-kichik harfdan qat'i nazar)
        $duplicate = SessionResult::where('session_id', $session->id)
            ->whereRaw('LOWER(participant_name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($duplicate) {
            return response()->json(['message' => "Bu nom bilan natija allaqachon yuborilgan: {$name}"], 422);
        }

        if ($session->status === 'waiting') {
            $session->update(['status' => 'active', 'started_at' => now()]);
        }

        $result = SessionResult::create([
            'session_id'       => $session->id,
            'participant_name' => $name,
            'score'            => $validated['score'],
            'answers_json'     => $validated['answers_json'] ?? [],
            'student_token'    => $token,  // qurilma asosida tarixni kuzatish uchun
        ]);

        broadcast(new SessionResultSubmitted($code, $result->only(['id', 'participant_name', 'score'])))->toOthers();

        return response()->json(['data' => $result], 201);
    }

    public function endSession(Request $request, string $code)
    {
        $session = GameSession::where('session_code', $code)
            ->with('game:id,user_id')
            ->firstOrFail();

        if ($session->game->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ruxsat yo\'q.'], 403);
        }

        $session->update(['status' => 'ended', 'ended_at' => now()]);

        broadcast(new SessionEnded($code));

        return response()->json(['message' => 'Sessiya tugatildi.', 'data' => ['status' => 'ended']]);
    }

    public function results(string $code)
    {
        $session = GameSession::where('session_code', $code)
            ->with([
                'results' => fn($q) => $q->orderByDesc('score'),
                'game:id,topic,template_id',
                'game.template:id,code,name',
            ])
            ->firstOrFail();

        return response()->json([
            'data' => [
                'session' => $session->only(['id', 'session_code', 'status', 'started_at']),
                'game'    => $session->game,
                'results' => $session->results,
            ],
        ]);
    }
}
