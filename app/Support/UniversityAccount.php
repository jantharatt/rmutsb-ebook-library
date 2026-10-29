<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * กติกาบัญชีอีเมลมหาวิทยาลัย (@rmutsb.ac.th)
 *
 *  - นักศึกษา (ปัจจุบัน/ศิษย์เก่า): ขึ้นต้นด้วยตัวเลข เช่น 6612345678-st@rmutsb.ac.th  → role = student
 *  - อาจารย์/บุคลากร:          ชื่อ.นามสกุล เช่น somchai.s@rmutsb.ac.th           → role = teacher
 *  - อีเมลใน ADMIN_EMAILS (.env)                                                   → role = admin
 *
 * กำหนดสิทธิ์เฉพาะตอนสร้างบัญชีครั้งแรก หลังจากนั้นแอดมินเปลี่ยนสิทธิ์ได้ที่เมนู “ผู้ใช้งาน”
 */
class UniversityAccount
{
    public static function domain(): string
    {
        return strtolower(config('services.login.domain', 'rmutsb.ac.th'));
    }

    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /** อีเมลอยู่ในโดเมนมหาวิทยาลัยหรือไม่ */
    public static function isUniversityEmail(string $email): bool
    {
        return str_ends_with(self::normalize($email), '@'.self::domain());
    }

    public static function localPart(string $email): string
    {
        return Str::before(self::normalize($email), '@');
    }

    /** อีเมลนักศึกษา: ขึ้นต้นด้วยตัวเลข (เช่น 6612345678-st) */
    public static function isStudentEmail(string $email): bool
    {
        return (bool) preg_match('/^\d/', self::localPart($email));
    }

    /** อีเมลอาจารย์/บุคลากร: ขึ้นต้นด้วยตัวอักษร (เช่น ชื่อ.นามสกุล) */
    public static function isStaffEmail(string $email): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)*$/', self::localPart($email));
    }

    public static function isAdminEmail(string $email): bool
    {
        return in_array(self::normalize($email), config('services.login.admin_emails', []), true);
    }

    /** สิทธิ์เริ่มต้นตามรูปแบบอีเมล */
    public static function defaultRole(string $email): string
    {
        if (self::isAdminEmail($email)) {
            return 'admin';
        }

        return self::isStudentEmail($email) ? 'student' : 'teacher';
    }

    /** ชื่อเริ่มต้นจากอีเมล (ผู้ใช้/แอดมินแก้ภายหลังได้) */
    public static function defaultName(string $email): string
    {
        $local = self::localPart($email);

        if (self::isStudentEmail($email)) {
            return 'นักศึกษา '.preg_replace('/-st$/', '', $local);
        }

        return Str::of($local)->replace(['.', '_'], ' ')->title()->toString();
    }

    /** อีเมลนี้ใช้เข้าสู่ระบบได้หรือไม่ (บัญชีเดิมในระบบเข้าได้เสมอ) */
    public static function canSignIn(string $email): bool
    {
        if (User::where('email', self::normalize($email))->exists()) {
            return true;
        }

        return self::isUniversityEmail($email)
            && (self::isStudentEmail($email) || self::isStaffEmail($email));
    }

    /**
     * ตรวจอีเมลกับระบบ e-profile ของมหาวิทยาลัย
     * คืนข้อมูลโปรไฟล์ (array) เมื่อพบ, [] เมื่อไม่พบ, null เมื่อเรียก API ไม่สำเร็จ
     * ถ้าไม่ได้ตั้งค่า URL จะข้ามการตรวจและคืน ['skipped' => true]
     */
    public static function fetchEProfile(string $email): ?array
    {
        $url = config('services.login.eprofile_url');

        if (! $url) {
            return ['skipped' => true];
        }

        try {
            $json = Http::timeout(10)->acceptJson()
                ->get($url, ['email' => self::normalize($email)])
                ->throw()
                ->json();

            return ($json['found'] ?? false) ? $json : [];
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /** แปลง "ประเภท" จาก e-profile เป็น role ของระบบ (null = ไม่รู้จัก ใช้กติกาตามอีเมลแทน) */
    public static function roleFromProfileType(?string $type): ?string
    {
        $type = trim((string) $type);

        return match (true) {
            $type === '' => null,
            str_contains($type, 'อาจารย์') => 'teacher',
            str_contains($type, 'นักศึกษา') => 'student',
            str_contains($type, 'ศิษย์เก่า') => 'alumni',
            str_contains($type, 'เจ้าหน้าที่'),
            str_contains($type, 'บุคลากร'),
            str_contains($type, 'พนักงาน') => 'staff',
            default => null,
        };
    }

    /**
     * หา/สร้างบัญชีจากอีเมล (สร้างใหม่พร้อมสิทธิ์ตามรูปแบบอีเมล)
     */
    public static function findOrCreate(string $email, ?string $name = null, array $extra = [], ?string $role = null): User
    {
        $email = self::normalize($email);

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create(array_merge([
                'name' => $name ?: self::defaultName($email),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => self::isAdminEmail($email) ? 'admin' : ($role ?: self::defaultRole($email)),
                'status' => 1,
            ], $extra));
        }

        // อีเมลที่กำหนดเป็นแอดมินใน .env → ยกระดับเป็นแอดมิน (ไม่ลดสิทธิ์ใคร)
        if (self::isAdminEmail($email) && $user->role !== 'admin') {
            $user->forceFill(['role' => 'admin'])->save();
        }

        return $user;
    }

    /** โหมดเข้าสู่ระบบด้วยอีเมลอย่างเดียว (สำหรับทดสอบบนเครื่อง local/XAMPP เท่านั้น) */
    public static function emailOnlyLogin(): bool
    {
        return (bool) config('services.login.email_only')
            && app()->environment('local', 'testing');
    }
}
