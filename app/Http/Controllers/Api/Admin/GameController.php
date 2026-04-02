<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Game;

class GameController extends Controller
{
    public function index()
    {
        $games = Game::with(['user:id,name,email', 'template:id,code,name', 'category:id,name'])
            ->latest()
            ->get();

        return response()->json(['data' => $games]);
    }

    public function destroy(int $id)
    {
        $game = Game::findOrFail($id);
        AuditLog::log('admin.delete_game', auth()->id(), 'Game', $id, ['topic' => $game->topic]);
        $game->delete();

        return response()->json(['message' => 'O\'chirildi.']);
    }
}
