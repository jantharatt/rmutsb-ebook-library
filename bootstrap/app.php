<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'active' => \App\Http\Middleware\EnsureAccountIsActive::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\EnsureAccountIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ไฟล์ใหญ่เกินค่า post_max_size ของ PHP → แจ้งผู้ใช้แทนหน้า 413
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'ไฟล์มีขนาดใหญ่เกินกว่าที่เซิร์ฟเวอร์รองรับ'], 413);
            }

            return back()->with('error', 'ไฟล์มีขนาดใหญ่เกินกว่าที่เซิร์ฟเวอร์รองรับ (ตรวจสอบ upload_max_filesize และ post_max_size ใน php.ini)');
        });
    })->create();
