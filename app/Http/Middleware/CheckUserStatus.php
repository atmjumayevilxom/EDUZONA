<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Hisobingiz bloklangan.'], 403);
            }

            return redirect('/')->with('error', 'Hisobingiz bloklangan. Administrator bilan bog\'laning.');
        }

        return $next($request);
    }
}
