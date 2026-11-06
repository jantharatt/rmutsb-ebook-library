<?php

namespace App\Services;

use App\Mail\BorrowReminderMail;
use App\Models\Borrow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * งานอัตโนมัติของการยืม
 * - ตัดคืนอัตโนมัติเมื่อเลยวันครบกำหนด (อ่านได้ถึงสิ้นวันครบกำหนด)
 * - ส่งอีเมลเตือนก่อนครบกำหนดคืน (ครั้งเดียวต่อรอบการยืม/ต่ออายุ)
 */
class BorrowLifecycle
{
    public const REMIND_DAYS_BEFORE = 3;

    /** รายการที่เลยกำหนดแล้วแต่ยังไม่คืน (รวมรายการสถานะ expired แบบเดิม) */
    public function overdueQuery(?int $userId = null): Builder
    {
        return Borrow::query()
            ->whereIn('status', ['borrowed', 'expired'])
            ->whereNull('return_date')
            ->whereDate('due_date', '<', today())
            ->when($userId, fn ($q) => $q->where('user_id', $userId));
    }

    /** ตัดคืนอัตโนมัติ: สถานะ "คืนแล้ว" วันที่คืน = วันครบกำหนด */
    public function autoReturnOverdue(?int $userId = null): int
    {
        return $this->overdueQuery($userId)->update([
            'status' => 'returned',
            'return_date' => \Illuminate\Support\Facades\DB::raw('due_date'),
            'auto_returned' => true,
            'updated_at' => now(),
        ]);
    }

    /** รายการที่ต้องส่งอีเมลเตือน: กำลังยืม ครบกำหนดภายใน N วัน และยังไม่เคยเตือนในรอบนี้ */
    public function reminderQuery(): Builder
    {
        return Borrow::query()
            ->with(['user', 'ebook'])
            ->where('status', 'borrowed')
            ->whereNull('return_date')
            ->whereNull('reminder_sent_at')
            ->whereDate('due_date', '>=', today())
            ->whereDate('due_date', '<=', today()->addDays(self::REMIND_DAYS_BEFORE));
    }

    /**
     * @return array{sent:int, failed:int, skipped:int}
     */
    public function sendDueReminders(): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'skipped' => 0];

        $this->reminderQuery()->chunkById(200, function ($borrows) use (&$result) {
            foreach ($borrows as $borrow) {
                if (! $borrow->user?->email || ! $borrow->ebook) {
                    $result['skipped']++;

                    continue;
                }

                try {
                    Mail::to($borrow->user->email)->send(new BorrowReminderMail($borrow));

                    // saveQuietly: ไม่ต้องบันทึกลง audit log
                    $borrow->forceFill(['reminder_sent_at' => now()])->saveQuietly();
                    $result['sent']++;
                } catch (Throwable $e) {
                    // ส่งไม่สำเร็จ → ยังไม่ตั้ง reminder_sent_at รอบถัดไปจะลองส่งใหม่
                    Log::warning("ส่งอีเมลเตือนคืน Ebook ไม่สำเร็จ (borrow #{$borrow->id}): ".$e->getMessage());
                    $result['failed']++;
                }
            }
        });

        return $result;
    }
}
