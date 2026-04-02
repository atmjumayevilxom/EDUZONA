<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class GoogleController extends Controller
{
    private function driver(): GoogleProvider
    {
        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');
        // Windows/XAMPP da SSL sertifikat muammosini hal qilish
        $driver->setHttpClient(new GuzzleClient(['verify' => false]));
        return $driver;
    }

    public function redirect()
    {
        return $this->driver()->stateless()->redirect();
    }

    public function callback(Request $request)
    {
        Log::info('Google callback keldi', [
            'url'     => $request->fullUrl(),
            'has_code'  => $request->has('code'),
            'has_error' => $request->has('error'),
            'error'   => $request->get('error'),
            'params'  => $request->except(['code']),
        ]);

        // Agar code yo'q bo'lsa — Google ga yo'naltir
        if (!$request->has('code')) {
            return $this->driver()->stateless()->redirect();
        }

        try {
            $googleUser = $this->driver()->stateless()->user();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            Log::error('Google OAuth xato', ['error' => $msg, 'trace' => $e->getTraceAsString()]);
            if (str_contains($msg, 'redirect_uri_mismatch')) {
                return redirect('/')->with('error', 'Google: Callback URL mos kelmadi. ' . $msg);
            }
            return redirect('/')->with('error', 'Google xato: ' . $msg);
        }

        $existing = User::where('email', $googleUser->getEmail())->first();

        if ($existing) {
            $existing->update([
                'name'          => $googleUser->getName(),
                'google_id'     => $googleUser->getId(),
                'avatar'        => $googleUser->getAvatar(),
                'last_login_at' => now(),
            ]);
            $user = $existing->fresh();
        } else {
            $user = User::create([
                'email'         => $googleUser->getEmail(),
                'name'          => $googleUser->getName(),
                'google_id'     => $googleUser->getId(),
                'avatar'        => $googleUser->getAvatar(),
                'role'          => 'user',
                'status'        => 'active',
                'last_login_at' => now(),
            ]);
        }

        if ($user->status === 'blocked') {
            return redirect('/')->with('error', 'Sizning hisobingiz bloklangan. Administrator bilan bog\'laning.');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        AuditLog::log('user.login', $user->id, 'User', $user->id, [
            'email'  => $user->email,
            'method' => 'google',
        ]);

        if ($user->role === 'admin') {
            return redirect('/admin');
        }

        return redirect()->intended('/dashboard');
    }
}
