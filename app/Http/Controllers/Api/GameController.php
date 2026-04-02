<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateGameRequest;
use App\Jobs\GenerateGameJob;
use App\Models\AiSetting;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\SessionResult;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function index()
    {
        $query = auth()->user()->games()
            ->with(['category:id,name', 'template:id,code,name,renderer_component'])
            ->latest();

        if ($search = request('search')) {
            $query->where('topic', 'like', '%' . $search . '%');
        }

        if ($template = request('template')) {
            $query->whereHas('template', fn($q) => $q->where('code', $template));
        }

        if ($status = request('status')) {
            $query->where('status', $status);
        }

        if ($difficulty = request('difficulty')) {
            $query->where('difficulty', $difficulty);
        }

        $games = $query->paginate(15)->withQueryString();

        return response()->json($games);
    }

    public function show(int $id)
    {
        $game = auth()->user()->games()
            ->with(['category:id,name', 'template:id,code,name,renderer_component'])
            ->findOrFail($id);

        return response()->json(['data' => $game]);
    }

    public function generate(GenerateGameRequest $request)
    {
        $user = auth()->user();
        $dailyLimit = (int) AiSetting::get('daily_request_limit', 10);

        $countFrom = $user->daily_limit_reset_at && $user->daily_limit_reset_at->isToday()
            ? $user->daily_limit_reset_at
            : today()->startOfDay();

        $todayCount = $user->games()
            ->where('created_at', '>=', $countFrom)
            ->whereIn('status', ['ready', 'generating'])
            ->count();

        if ($todayCount >= $dailyLimit) {
            return response()->json([
                'message' => "Kunlik limit ({$dailyLimit}) oshib ketdi. Ertaga qayta urinib ko'ring.",
            ], 429);
        }

        $game = Game::create([
            'user_id'        => $user->id,
            'category_id'    => $request->category_id ?: null,
            'template_id'    => $request->template_id,
            'topic'          => $request->topic,
            'language'       => $request->language,
            'students_count' => $request->students_count,
            'difficulty'     => $request->difficulty ?? 'medium',
            'status'         => 'generating',
        ]);

        GenerateGameJob::dispatch($game);

        $game->load(['category:id,name', 'template:id,code,name,renderer_component']);

        return response()->json(['data' => $game], 201);
    }

    public function createSession(int $id)
    {
        $game = auth()->user()->games()->where('status', 'ready')->findOrFail($id);

        // classroom_id ixtiyoriy — faqat o'qituvchining o'z sinfiga bog'lash
        $classroomId = null;
        if ($cid = request('classroom_id')) {
            $classroom = auth()->user()->classrooms()->find($cid);
            if ($classroom) {
                $classroomId = $classroom->id;
            }
        }

        do {
            $code = strtoupper(Str::random(6));
        } while (GameSession::where('session_code', $code)->exists());

        $session = GameSession::create([
            'game_id'      => $game->id,
            'session_code' => $code,
            'status'       => 'waiting',
            'classroom_id' => $classroomId,
        ]);

        return response()->json(['data' => $session], 201);
    }

    public function stats(int $id)
    {
        $game = auth()->user()->games()
            ->with([
                'template:id,code,name',
                'sessions' => fn($q) => $q->withCount('results')
                    ->with(['results' => fn($q) => $q->orderByDesc('score')]),
            ])
            ->findOrFail($id);

        $allResults = $game->sessions->flatMap(fn($s) => $s->results);

        return response()->json([
            'data' => [
                'id'             => $game->id,
                'topic'          => $game->topic,
                'template'       => $game->template,
                'total_sessions' => $game->sessions->count(),
                'total_players'  => $allResults->count(),
                'average_score'  => $allResults->count() ? round($allResults->avg('score')) : 0,
                'best_score'     => $allResults->count() ? $allResults->max('score') : 0,
                'sessions'       => $game->sessions->map(fn($s) => [
                    'id'            => $s->id,
                    'session_code'  => $s->session_code,
                    'status'        => $s->status,
                    'started_at'    => $s->started_at,
                    'results_count' => $s->results->count(),
                    'avg_score'     => $s->results->count() ? round($s->results->avg('score')) : 0,
                    'top_results'   => $s->results->sortByDesc('score')->take(20)->values(),
                ]),
            ],
        ]);
    }

    public function retry(int $id)
    {
        $game = auth()->user()->games()
            ->where('status', 'error')
            ->findOrFail($id);

        $game->update(['status' => 'generating']);
        GenerateGameJob::dispatch($game);

        $game->load(['category:id,name', 'template:id,code,name,renderer_component']);

        return response()->json(['data' => $game]);
    }

    public function destroy(int $id)
    {
        $game = auth()->user()->games()->findOrFail($id);
        $game->delete();

        return response()->json(['message' => 'O\'yin o\'chirildi'], 200);
    }

    public function exportStats(int $id)
    {
        $game = auth()->user()->games()
            ->with(['sessions.results'])
            ->findOrFail($id);

        $rows = [];
        $rows[] = ['Sessiya kodi', 'Ishtirokchi ismi', 'Ball', 'Sana'];

        foreach ($game->sessions as $session) {
            foreach ($session->results->sortByDesc('score') as $result) {
                $rows[] = [
                    $session->session_code,
                    $result->participant_name,
                    $result->score,
                    $result->created_at?->format('Y-m-d H:i:s') ?? '',
                ];
            }
        }

        $csv = implode("\n", array_map(fn($row) => implode(',', array_map(
            fn($val) => '"' . str_replace('"', '""', $val) . '"',
            $row
        )), $rows));

        $filename = 'stats_' . $game->id . '_' . now()->format('Ymd') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function publicGames()
    {
        $query = Game::with(['template:id,code,name', 'user:id,name'])
            ->where('status', 'ready')
            ->where('is_public', true)
            ->latest();

        if ($search = request('search')) {
            $query->where('topic', 'like', '%' . $search . '%');
        }

        if ($template = request('template')) {
            $query->whereHas('template', fn($q) => $q->where('code', $template));
        }

        $games = $query->paginate(24)->withQueryString();

        return response()->json($games);
    }

    public function togglePublic(int $id)
    {
        $game = auth()->user()->games()->where('status', 'ready')->findOrFail($id);
        $game->update(['is_public' => !$game->is_public]);

        return response()->json(['data' => $game, 'is_public' => $game->is_public]);
    }

    public function copy(int $id)
    {
        $original = auth()->user()->games()
            ->with('template:id,code,name,renderer_component')
            ->findOrFail($id);

        $copy = Game::create([
            'user_id'           => auth()->id(),
            'category_id'       => $original->category_id,
            'template_id'       => $original->template_id,
            'prompt_version_id' => $original->prompt_version_id,
            'topic'             => $original->topic . ' (nusxa)',
            'language'          => $original->language,
            'students_count'    => $original->students_count,
            'generated_json'    => $original->generated_json,
            'status'            => $original->status,
        ]);

        $copy->load(['category:id,name', 'template:id,code,name,renderer_component']);

        return response()->json(['data' => $copy], 201);
    }

    public function profileStats()
    {
        $user = auth()->user();

        $totalGames    = $user->games()->count();
        $readyGames    = $user->games()->where('status', 'ready')->count();
        $totalSessions = GameSession::whereHas('game', fn($q) => $q->where('user_id', $user->id))->count();
        $totalStudents = SessionResult::whereHas('session.game', fn($q) => $q->where('user_id', $user->id))->count();
        $publicGames   = $user->games()->where('is_public', true)->count();

        return response()->json([
            'data' => compact('totalGames', 'readyGames', 'totalSessions', 'totalStudents', 'publicGames'),
        ]);
    }
}
