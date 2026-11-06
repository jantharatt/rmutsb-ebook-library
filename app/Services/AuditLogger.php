<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * บันทึกการกระทำของผู้ดูแลระบบลงตาราง audit_logs
 * ผู้ใช้ที่ไม่ใช่แอดมินจะไม่ถูกบันทึก ยกเว้นระบุ $system = true (งานอัตโนมัติของระบบ)
 */
class AuditLogger
{
    public static function log(
        string $action,
        ?Model $subject = null,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null,
        ?User $actor = null,
        bool $system = false,
    ): void {
        try {
            $actor ??= auth()->user();

            if (! $system && ! ($actor instanceof User && $actor->isAdmin())) {
                return;
            }

            $request = app()->runningInConsole() ? null : request();

            AuditLog::create([
                'user_id' => $actor?->getKey(),
                'user_name' => $actor?->name ?? ($system ? 'ระบบอัตโนมัติ' : null),
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subject && method_exists($subject, 'auditLabel')
                    ? mb_substr((string) $subject->auditLabel(), 0, 255)
                    : null,
                'old_values' => $old ?: null,
                'new_values' => $new ?: null,
                'description' => $description,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
            ]);
        } catch (Throwable $e) {
            // การบันทึก log ล้มเหลวต้องไม่ทำให้งานหลักพัง
            Log::warning('บันทึก audit log ไม่สำเร็จ: '.$e->getMessage());
        }
    }
}
