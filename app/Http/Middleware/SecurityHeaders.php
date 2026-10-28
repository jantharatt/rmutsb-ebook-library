<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * เพิ่ม HTTP Security Headers พื้นฐานให้ทุกหน้า
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // หน้าที่ต้องล็อกอิน ไม่ให้เบราว์เซอร์/พร็อกซีเก็บแคช (กันกดย้อนกลับหลังออกจากระบบ)
        $isHtml = str_starts_with((string) $headers->get('Content-Type'), 'text/html');

        if ($request->user() && $isHtml) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
