<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * อนุญาตเฉพาะผู้ดูแลระบบ (role = admin)
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            if ($request->expectsJson()) {
                abort(403, 'คุณไม่มีสิทธิ์ใช้งานส่วนนี้');
            }

            abort(403, 'หน้านี้สำหรับผู้ดูแลระบบเท่านั้น');
        }

        return $next($request);
    }
}
