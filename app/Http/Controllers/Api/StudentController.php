<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SessionResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function results(Request $request)
    {
        $name = trim($request->query('name', ''));

        if (mb_strlen($name) < 2) {
            return response()->json(['message' => 'Ism kamida 2 ta harf bo\'lishi kerak.'], 422);
        }

        $results = SessionResult::whereRaw('LOWER(participant_name) = ?', [mb_strtolower($name)])
            ->with([
                'session:id,session_code,game_id,started_at,status',
                'session.game:id,topic,template_id,user_id',
                'session.game.template:id,code,name',
                'session.game.user:id,name',
            ])
            ->orderByDesc('created_at')
            ->get();

        // Calculate stats
        $totalSessions = $results->count();
        $totalScore    = $results->sum('score');
        $avgScore      = $totalSessions > 0 ? round($totalScore / $totalSessions) : 0;

        // Rank: bitta query bilan barcha sessionlar uchun o'quvchidan yuqori balllilar soni
        $sessionIds = $results->pluck('session_id')->unique()->toArray();
        $userScoreBySession = $results->pluck('score', 'session_id');

        $higherCountRows = DB::table('session_results')
            ->select('session_id', DB::raw('COUNT(*) as cnt'))
            ->whereIn('session_id', $sessionIds)
            ->where(function ($q) use ($userScoreBySession) {
                foreach ($userScoreBySession as $sid => $score) {
                    $q->orWhere(fn($sub) => $sub->where('session_id', $sid)->where('score', '>', $score));
                }
            })
            ->groupBy('session_id')
            ->pluck('cnt', 'session_id');

        $firstPlaces = 0;
        $topThree    = 0;
        foreach ($results as $result) {
            $rank = ($higherCountRows[$result->session_id] ?? 0) + 1;
            $result->rank = $rank;
            if ($rank === 1) $firstPlaces++;
            if ($rank <= 3) $topThree++;
        }

        // Achievements
        $achievements = [];
        if ($totalSessions >= 1)  $achievements[] = ['key' => 'first_game',   'label' => 'Birinchi o\'yin',   'icon' => '🎮', 'desc' => 'Birinchi marta o\'yin o\'yndingiz'];
        if ($totalSessions >= 5)  $achievements[] = ['key' => 'active',        'label' => 'Faol o\'quvchi',    'icon' => '⚡', 'desc' => '5+ o\'yin o\'yndingiz'];
        if ($totalSessions >= 20) $achievements[] = ['key' => 'champion',      'label' => 'Chempion',          'icon' => '🏆', 'desc' => '20+ o\'yin o\'yndingiz'];
        if ($firstPlaces >= 1)    $achievements[] = ['key' => 'gold',          'label' => 'Oltin medal',       'icon' => '🥇', 'desc' => '1-o\'rin olgansiz'];
        if ($topThree >= 3)       $achievements[] = ['key' => 'podium',        'label' => 'Podium usatasi',    'icon' => '🎖️', 'desc' => '3 marta top-3ga kirdingiz'];
        if ($avgScore >= 80)      $achievements[] = ['key' => 'excellent',     'label' => 'A\'lochi',          'icon' => '⭐', 'desc' => "O'rtacha ball ≥80"];

        return response()->json([
            'data' => [
                'name'          => $results->first()?->participant_name ?? $name,
                'stats'         => compact('totalSessions', 'totalScore', 'avgScore', 'firstPlaces', 'topThree'),
                'achievements'  => $achievements,
                'results'       => $results->map(fn($r) => [
                    'id'           => $r->id,
                    'score'        => $r->score,
                    'rank'         => $r->rank,
                    'created_at'   => $r->created_at,
                    'session_code' => $r->session?->session_code,
                    'game_topic'   => $r->session?->game?->topic,
                    'template'     => $r->session?->game?->template?->name,
                    'teacher'      => $r->session?->game?->user?->name,
                    'session_date' => $r->session?->started_at,
                ]),
            ],
        ]);
    }
}
