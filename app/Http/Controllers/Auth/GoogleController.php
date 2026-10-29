<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UniversityAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors([
                'email' => 'เข้าสู่ระบบด้วย Google ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง',
            ]);
        }

        $email = strtolower($googleUser->getEmail());

        if (! UniversityAccount::canSignIn($email)) {
            return redirect()->route('login')->withErrors([
                'email' => 'อนุญาตเฉพาะอีเมล @'.UniversityAccount::domain().' เท่านั้น',
            ]);
        }

        // ตรวจอีเมลกับระบบ e-profile ของมหาวิทยาลัย
        // นักศึกษา (อีเมล -st) ไม่มีใน e-profile → ข้ามการตรวจ ตีเป็นนักศึกษาตามรูปแบบอีเมล
        $profile = UniversityAccount::isStudentEmail($email)
            ? ['skipped' => true]
            : UniversityAccount::fetchEProfile($email);

        if ($profile === null) {
            return redirect()->route('login')->withErrors([
                'email' => 'ไม่สามารถตรวจสอบอีเมลกับระบบ e-profile ได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง',
            ]);
        }

        if ($profile === []) {
            return redirect()->route('login')->withErrors([
                'email' => 'ไม่พบอีเมลนี้ในระบบ e-profile ของมหาวิทยาลัย',
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $email)->first();

        if ($user && (int) $user->status !== 1) {
            return redirect()->route('login')->withErrors([
                'email' => 'บัญชีนี้ถูกระงับการใช้งาน กรุณาติดต่อสำนักวิทยบริการฯ',
            ]);
        }

        if ($user) {
            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
                $user->save();
            }
            // อัปเดตคณะ/หน่วยงานจาก e-profile ทุกครั้งที่เข้าสู่ระบบ
            $user->fill(array_filter([
                'faculty' => $profile['faculty_name_th'] ?? null,
                'department' => $profile['division_name_th'] ?? null,
            ]));
            if ($user->isDirty()) {
                $user->save();
            }
            // ยกระดับเป็นแอดมินตาม ADMIN_EMAILS (ถ้ามี)
            if (UniversityAccount::isAdminEmail($user->email) && $user->role !== 'admin') {
                $user->forceFill(['role' => 'admin'])->save();
            }
        } else {
            // บัญชีใหม่: กำหนดสิทธิ์ตามรูปแบบอีเมล (ตัวเลข = นักศึกษา, ตัวอักษร = อาจารย์/บุคลากร)
            $user = UniversityAccount::findOrCreate($email, $profile['name'] ?? $googleUser->getName(), array_filter([
                'google_id' => $googleUser->getId(),
                'faculty' => $profile['faculty_name_th'] ?? null,
                'department' => $profile['division_name_th'] ?? null,
            ]), UniversityAccount::roleFromProfileType($profile['type'] ?? null));
        }

        Auth::login($user);

        request()->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
