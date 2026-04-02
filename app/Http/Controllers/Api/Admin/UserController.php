<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount('games')
            ->latest()
            ->get();

        return response()->json(['data' => $users]);
    }

    public function block(int $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot block yourself.'], 422);
        }

        $user->update(['status' => 'blocked']);
        AuditLog::log('user.blocked', auth()->id(), 'User', $user->id, ['email' => $user->email]);

        return response()->json(['data' => $user]);
    }

    public function activate(int $id)
    {
        $user = User::findOrFail($id);
        $user->update(['status' => 'active']);
        AuditLog::log('user.activated', auth()->id(), 'User', $user->id, ['email' => $user->email]);

        return response()->json(['data' => $user]);
    }

    public function resetDailyLimit(int $id)
    {
        $user = User::findOrFail($id);
        $user->update(['daily_limit_reset_at' => now()]);
        AuditLog::log('user.limit_reset', auth()->id(), 'User', $user->id, ['email' => $user->email]);

        return response()->json(['message' => 'Limit tiklandi']);
    }
}
