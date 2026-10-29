<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ป้องกัน mixed content: บังคับให้ URL/asset เป็น https เมื่อรันบน production
        if ($this->app->environment('production') || str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // นับผู้ใช้ที่ “เคยเข้าสู่ระบบ” ครั้งเดียว: บันทึกเวลาเข้าสู่ระบบครั้งแรกไว้ใน email_verified_at
        // (คอลัมน์เดิม ไม่เพิ่มฐานข้อมูล) เข้าครั้งต่อไปจะไม่นับซ้ำ
        Event::listen(Login::class, function (Login $event) {
            $user = $event->user;

            if ($user instanceof \App\Models\User && ! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->saveQuietly();
            }
        });

        Gate::define('admin', function ($user) {
            return $user->role === 'admin';
        });
    }
}