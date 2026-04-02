<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function usage()
    {
        $totalGames  = Game::count();
        $todayGames  = Game::whereDate('created_at', today())->count();
        $readyGames  = Game::where('status', 'ready')->count();
        $errorGames  = Game::where('status', 'error')->count();
        $totalUsers  = User::where('role', 'user')->count();
        $activeUsers = User::where('role', 'user')->where('status', 'active')->count();

        $settings = AiSetting::all()->pluck('value', 'key');

        // Last 7 days daily chart
        $dailyChart = collect(range(6, 0))->map(fn($i) => [
            'date'  => now()->subDays($i)->format('d/m'),
            'count' => Game::whereDate('created_at', now()->subDays($i))->count(),
        ])->values();

        // Top 10 pedagoglar by game count
        $topPedagoglar = User::where('role', 'user')
            ->withCount(['games' => fn($q) => $q->where('status', 'ready')])
            ->orderByDesc('games_count')
            ->limit(10)
            ->get(['id', 'name', 'email', 'avatar', 'last_login_at'])
            ->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'email'        => $u->email,
                'avatar'       => $u->avatar,
                'games_count'  => $u->games_count,
                'last_login'   => $u->last_login_at,
            ]);

        return response()->json([
            'data' => [
                'total_games'    => $totalGames,
                'today_games'    => $todayGames,
                'ready_games'    => $readyGames,
                'error_games'    => $errorGames,
                'total_users'    => $totalUsers,
                'active_users'   => $activeUsers,
                'settings'       => $settings,
                'daily_chart'    => $dailyChart,
                'top_pedagoglar' => $topPedagoglar,
            ],
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'model' => ['sometimes', 'string'],
            'daily_request_limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'daily_token_budget' => ['sometimes', 'integer', 'min:1000'],
            'max_retries' => ['sometimes', 'integer', 'min:0', 'max:5'],
            'max_tokens' => ['sometimes', 'integer', 'min:100', 'max:8000'],
        ]);

        foreach ($validated as $key => $value) {
            AiSetting::set($key, $value);
        }

        return response()->json(['message' => 'Settings updated.', 'data' => $validated]);
    }

    public function videoSettings()
    {
        $keys = ['video_duration', 'video_prompt_style', 'video_daily_limit', 'video_prompt_prefix', 'video_prompt_suffix'];
        $settings = AiSetting::whereIn('key', $keys)->pluck('value', 'key');

        return response()->json([
            'data' => [
                'video_duration'      => $settings['video_duration']      ?? '15',
                'video_prompt_style'  => $settings['video_prompt_style']  ?? 'blackboard',
                'video_daily_limit'   => $settings['video_daily_limit']   ?? '5',
                'video_prompt_prefix' => $settings['video_prompt_prefix'] ?? '',
                'video_prompt_suffix' => $settings['video_prompt_suffix'] ?? 'Academic chalkboard style. 720p quality.',
            ],
        ]);
    }

    public function updateVideoSettings(Request $request)
    {
        $validated = $request->validate([
            'video_duration'      => ['sometimes', 'integer', 'min:5', 'max:30'],
            'video_prompt_style'  => ['sometimes', 'string', 'in:blackboard,animated,minimal'],
            'video_daily_limit'   => ['sometimes', 'integer', 'min:1', 'max:50'],
            'video_prompt_prefix' => ['sometimes', 'string', 'max:300'],
            'video_prompt_suffix' => ['sometimes', 'string', 'max:300'],
        ]);

        foreach ($validated as $key => $value) {
            AiSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return response()->json(['message' => 'Video sozlamalar saqlandi.', 'data' => $validated]);
    }
}
