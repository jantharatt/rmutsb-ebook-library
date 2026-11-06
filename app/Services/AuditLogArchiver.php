<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * เก็บถาวร audit log: ส่งออก log ที่เก่ากว่าไตรมาสปัจจุบันเป็นไฟล์ CSV (แยกไฟล์ตามไตรมาส)
 * แล้วจึงลบออกจากตาราง เพื่อไม่ให้ตารางโตเกินไป โดยข้อมูลเดิมยังเปิดดูได้จากไฟล์
 */
class AuditLogArchiver
{
    public const DIRECTORY = 'audit-archives';

    public const FILE_PATTERN = '/^audit-log_\d{4}-Q[1-4]\.csv$/';

    private const HEADER = ['ID', 'วันเวลา', 'แอดมิน', 'การกระทำ', 'ประเภทข้อมูล', 'ID ข้อมูล', 'รายการ',
        'ค่าเดิม (JSON)', 'ค่าใหม่ (JSON)', 'รายละเอียด', 'IP', 'User agent'];

    /** วันแรกของไตรมาสปัจจุบัน: log ที่เก่ากว่านี้จะถูกเก็บถาวร */
    public function cutoff(): Carbon
    {
        return now()->firstOfQuarter()->startOfDay();
    }

    public function pendingCount(): int
    {
        return AuditLog::where('created_at', '<', $this->cutoff())->count();
    }

    /**
     * @return array{count:int, files:array<string,int>}
     */
    public function archive(?User $actor = null): array
    {
        $cutoff = $this->cutoff();
        $expected = $this->pendingCount();

        if ($expected === 0) {
            return ['count' => 0, 'files' => []];
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory(self::DIRECTORY);

        $handles = [];
        $perFile = [];
        $ids = [];

        try {
            AuditLog::where('created_at', '<', $cutoff)
                ->chunkById(1000, function ($logs) use ($disk, &$handles, &$perFile, &$ids) {
                    foreach ($logs as $log) {
                        $name = self::fileNameFor($log->created_at);

                        if (! isset($handles[$name])) {
                            $path = $disk->path(self::DIRECTORY.'/'.$name);
                            $isNew = ! is_file($path) || filesize($path) === 0;
                            $handle = fopen($path, 'ab'); // เขียนต่อท้าย ไม่เขียนทับไฟล์เดิม

                            if ($handle === false) {
                                throw new RuntimeException("เปิดไฟล์ {$name} ไม่ได้");
                            }

                            if ($isNew) {
                                fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM ให้ Excel แสดงภาษาไทยถูกต้อง
                                fputcsv($handle, self::HEADER, escape: "");
                            }

                            $handles[$name] = $handle;
                            $perFile[$name] = 0;
                        }

                        $written = fputcsv($handles[$name], [
                            $log->id,
                            $log->created_at?->format('Y-m-d H:i:s'),
                            $log->user_name,
                            $log->action_name,
                            $log->subject_name,
                            $log->subject_id,
                            $log->subject_label,
                            $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '',
                            $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '',
                            $log->description,
                            $log->ip_address,
                            $log->user_agent,
                        ], escape: ''); // ไม่ใช้ backslash escape เพื่อให้ JSON ที่มี \" อ่านใน Excel ได้ถูกต้อง

                        if ($written === false) {
                            throw new RuntimeException("เขียนไฟล์ {$name} ไม่สำเร็จ");
                        }

                        $perFile[$name]++;
                        $ids[] = $log->id;
                    }
                });

            foreach ($handles as $name => $handle) {
                if (! fflush($handle)) {
                    throw new RuntimeException("บันทึกไฟล์ {$name} ไม่สำเร็จ");
                }
            }
        } finally {
            foreach ($handles as $handle) {
                fclose($handle);
            }
        }

        if (count($ids) !== $expected) {
            throw new RuntimeException('จำนวนรายการที่เขียนลงไฟล์ ('.count($ids).") ไม่ตรงกับที่เลือก ({$expected}) จึงยังไม่ลบข้อมูล");
        }

        // เขียนไฟล์ครบแล้วจึงลบออกจากตาราง
        foreach (array_chunk($ids, 1000) as $chunk) {
            AuditLog::whereIn('id', $chunk)->delete();
        }

        ksort($perFile);

        AuditLogger::log(
            'archived',
            description: 'เก็บถาวร log '.number_format($expected).' รายการ ลงไฟล์ '.implode(', ', array_keys($perFile)),
            actor: $actor,
            system: true,
        );

        return ['count' => $expected, 'files' => $perFile];
    }

    public static function fileNameFor(Carbon $date): string
    {
        return 'audit-log_'.$date->year.'-Q'.$date->quarter.'.csv';
    }

    /** รายการไฟล์เก็บถาวร (ใหม่สุดก่อน) */
    public function files(): array
    {
        $disk = Storage::disk('local');

        return collect($disk->files(self::DIRECTORY))
            ->map(fn ($path) => basename($path))
            ->filter(fn ($name) => preg_match(self::FILE_PATTERN, $name))
            ->sortDesc()
            ->map(fn ($name) => [
                'name' => $name,
                'quarter' => self::quarterLabel($name),
                'size' => $disk->size(self::DIRECTORY.'/'.$name),
                'modified' => Carbon::createFromTimestamp($disk->lastModified(self::DIRECTORY.'/'.$name)),
            ])
            ->values()
            ->all();
    }

    /** audit-log_2026-Q3.csv → ไตรมาส 3/2569 (ก.ค.–ก.ย.) */
    public static function quarterLabel(string $name): string
    {
        if (! preg_match('/(\d{4})-Q([1-4])/', $name, $m)) {
            return $name;
        }

        $months = [1 => 'ม.ค.–มี.ค.', 2 => 'เม.ย.–มิ.ย.', 3 => 'ก.ค.–ก.ย.', 4 => 'ต.ค.–ธ.ค.'];

        return "ไตรมาส {$m[2]}/".((int) $m[1] + 543)." ({$months[(int) $m[2]]})";
    }
}
