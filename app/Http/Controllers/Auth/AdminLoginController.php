<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminLoginController extends Controller
{
    public function showLogin()
    {
        if (auth()->check() && auth()->user()->isAdmin()) {
            return redirect('/admin');
        }
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password], true)) {
            return back()->withErrors(['email' => 'Email yoki parol noto\'g\'ri.']);
        }

        $user = auth()->user();

        if (!$user->isAdmin()) {
            Auth::logout();
            return back()->withErrors(['email' => 'Sizda admin huquqlari yo\'q.']);
        }

        if (!$user->isActive()) {
            Auth::logout();
            return back()->withErrors(['email' => 'Akkauntingiz bloklangan.']);
        }

        $user->update(['last_login_at' => now()]);
        AuditLog::log('admin.login', $user->id, 'User', $user->id, ['email' => $user->email]);

        $request->session()->regenerate();
        return redirect('/admin');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }
}
