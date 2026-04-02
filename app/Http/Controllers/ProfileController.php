<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update(['name' => $validated['name']]);

        return back()->with('success', 'Profil muvaffaqiyatli yangilandi.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Google orqali kirgan foydalanuvchilar parolni o'zgartira olmaydi
        if ($user->google_id && !$user->password) {
            return back()->with('error', 'Google orqali kirgan foydalanuvchilar parolni o\'zgartira olmaydi.');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'current_password.current_password' => 'Joriy parol noto\'g\'ri kiritildi.',
            'password.min'                      => 'Yangi parol kamida 8 ta belgi bo\'lishi kerak.',
            'password.mixed_case'               => 'Parolda katta va kichik harflar bo\'lishi kerak.',
            'password.numbers'                  => 'Parolda kamida bitta raqam bo\'lishi kerak.',
            'password.confirmed'                => 'Parollar mos kelmadi.',
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Parol muvaffaqiyatli o\'zgartirildi.');
    }
}
