<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * บัญชีที่ถูกปิดใช้งาน (users.status = 0) จะถูกออกจากระบบทันที
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (int) $user->status !== 1) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อสำนักวิทยบริการฯ',
            ]);
        }

        return $next($request);
    }
}
