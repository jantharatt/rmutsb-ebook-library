<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/*
|--------------------------------------------------------------------------
| ตัดคืนอัตโนมัติเมื่อเลยวันครบกำหนด (ไม่ได้ต่ออายุ) → สถานะ "คืนแล้ว"
| ผู้ยืมอ่านได้ถึงสิ้นวันครบกำหนด จึงรันหลังเที่ยงคืนทุกวัน
| php artisan borrows:auto-return [--dry-run]
|--------------------------------------------------------------------------
*/

Artisan::command('borrows:auto-return {--dry-run : แสดงจำนวนโดยไม่ทำจริง}', function () {
    $lifecycle = app(\App\Services\BorrowLifecycle::class);

    if ($this->option('dry-run')) {
        $this->info('จะตัดคืนอัตโนมัติ '.$lifecycle->overdueQuery()->count().' รายการ');

        return;
    }

    $this->info('ตัดคืนอัตโนมัติแล้ว '.$lifecycle->autoReturnOverdue().' รายการ');
})->purpose('คืน Ebook ที่เลยกำหนดคืนและไม่ได้ต่ออายุโดยอัตโนมัติ');

Schedule::command('borrows:auto-return')->dailyAt('00:05');


/*
|--------------------------------------------------------------------------
| ส่งอีเมลเตือนก่อนครบกำหนดคืน 3 วัน (ส่งครั้งเดียวต่อรอบการยืม/ต่ออายุ)
| ถ้าวันไหนงานไม่ได้รัน รอบถัดไปจะส่งให้รายการที่ครบกำหนดภายใน 3 วันที่ยังไม่ได้เตือน
| php artisan borrows:send-reminders [--dry-run]
|--------------------------------------------------------------------------
*/

Artisan::command('borrows:send-reminders {--dry-run : แสดงรายการโดยไม่ส่งจริง}', function () {
    $lifecycle = app(\App\Services\BorrowLifecycle::class);

    if ($this->option('dry-run')) {
        $rows = $lifecycle->reminderQuery()->get()->map(fn ($b) => [
            $b->id, $b->user?->email, \Illuminate\Support\Str::limit($b->ebook?->title, 40), \App\Support\ThaiDate::short($b->due_date),
        ]);
        $this->table(['ID', 'อีเมล', 'หนังสือ', 'ครบกำหนด'], $rows);

        return;
    }

    $r = $lifecycle->sendDueReminders();
    $this->info("ส่งอีเมลเตือนแล้ว {$r['sent']} รายการ");

    if ($r['failed'] || $r['skipped']) {
        $this->warn("ส่งไม่สำเร็จ {$r['failed']} รายการ (จะลองใหม่รอบถัดไป), ข้าม {$r['skipped']} รายการ (ไม่มีอีเมลหรือหนังสือถูกลบ)");
    }
})->purpose('ส่งอีเมลเตือนผู้ยืมก่อนครบกำหนดคืน 3 วัน');

Schedule::command('borrows:send-reminders')->dailyAt('08:00');

/*
|--------------------------------------------------------------------------
| เก็บถาวร Audit log ทุกต้นไตรมาส (1 ม.ค. / 1 เม.ย. / 1 ก.ค. / 1 ต.ค.)
| ส่งออก log ที่เก่ากว่าไตรมาสปัจจุบันเป็น CSV ใน storage/app/private/audit-archives แล้วลบออกจากตาราง
| php artisan audit-logs:archive [--dry-run]
|--------------------------------------------------------------------------
*/

Artisan::command('audit-logs:archive {--dry-run : แสดงจำนวนที่จะเก็บถาวรโดยไม่ทำจริง}', function () {
    $archiver = app(\App\Services\AuditLogArchiver::class);
    $pending = $archiver->pendingCount();
    $cutoff = \App\Support\ThaiDate::short($archiver->cutoff());

    if ($pending === 0) {
        $this->info("ไม่มี log ที่เก่ากว่า {$cutoff}");

        return;
    }

    if ($this->option('dry-run')) {
        $this->info("จะเก็บถาวร log {$pending} รายการ (ก่อน {$cutoff})");

        return;
    }

    $result = $archiver->archive();

    foreach ($result['files'] as $file => $count) {
        $this->line("  {$file}: {$count} รายการ");
    }

    $this->info("เก็บถาวรแล้ว {$result['count']} รายการ");
})->purpose('ส่งออก audit log ของไตรมาสก่อนหน้าเป็นไฟล์ CSV แล้วลบออกจากตาราง');

Schedule::command('audit-logs:archive')->quarterly();

/*
|--------------------------------------------------------------------------
| ย้ายไฟล์ PDF เดิมออกจากโฟลเดอร์สาธารณะ (ค่า file_path ในฐานข้อมูลไม่เปลี่ยน)
| php artisan ebooks:secure-files
|--------------------------------------------------------------------------
*/

Artisan::command('ebooks:secure-files {--dry-run : แสดงรายการโดยไม่ย้ายจริง}', function () {
    $moved = 0;

    \App\Models\Ebook::query()->orderBy('id')->each(function ($ebook) use (&$moved) {
        $path = $ebook->file_path;

        if (! $path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return;
        }

        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            $this->line("ข้าม #{$ebook->id} (มีไฟล์ในพื้นที่ส่วนตัวแล้ว)");

            return;
        }

        $this->info("ย้าย #{$ebook->id} {$path}");

        if ($this->option('dry-run')) {
            return;
        }

        $source = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        $target = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
        @mkdir(dirname($target), 0775, true);

        if (@rename($source, $target) || (copy($source, $target) && unlink($source))) {
            $moved++;
        } else {
            $this->error("ย้ายไม่สำเร็จ: {$path}");
        }
    });

    $this->info("เสร็จสิ้น ย้ายแล้ว {$moved} ไฟล์");
})->purpose('ย้ายไฟล์ PDF จาก storage/app/public ไปยัง storage/app/private');


/*
|--------------------------------------------------------------------------
| สร้างปกจากหน้าแรก PDF และไฟล์ตัวอย่าง 5 หน้า ให้หนังสือที่ยังไม่มี
| php artisan ebooks:generate-assets
|--------------------------------------------------------------------------
*/

Artisan::command('ebooks:generate-assets {--force : สร้างใหม่ทั้งหมด}', function () {
    $files = app(\App\Services\EbookFileService::class);
    $cover = $preview = $failed = 0;

    \App\Models\Ebook::query()->orderBy('id')->each(function ($ebook) use ($files, &$cover, &$preview, &$failed) {
        if ($this->option('force')) {
            $files->deleteAutoCover($ebook);
            $files->deletePreview($ebook);
            $ebook->forceFill(['preview_file' => null])->saveQuietly();
        }

        if (! $files->hasUploadedCover($ebook) && ! $files->hasAutoCover($ebook)) {
            $files->generateAutoCover($ebook) ? $cover++ : $failed++;
        }

        if (! $files->previewAbsolutePath($ebook)) {
            $files->generatePreview($ebook) ? $preview++ : $failed++;
        }
    });

    $this->info("สร้างปก {$cover} เล่ม, ไฟล์ตัวอย่าง {$preview} เล่ม");

    if ($failed) {
        $this->warn("ทำไม่สำเร็จ {$failed} รายการ (เซิร์ฟเวอร์ไม่มีเครื่องมือแปลง PDF) — ระบบจะให้เบราว์เซอร์ของผู้ดูแลสร้างให้อัตโนมัติเมื่อเปิดหน้ารายการ Ebook");
    }
})->purpose('สร้างปกอัตโนมัติและไฟล์ตัวอย่างจาก PDF');


/*
|--------------------------------------------------------------------------
| ตรวจสาเหตุที่ปกหนังสือไม่แสดง
| php artisan ebooks:check-covers
|--------------------------------------------------------------------------
*/

Artisan::command('ebooks:check-covers', function () {
    $files = app(\App\Services\EbookFileService::class);

    $tools = array_filter([
        'pdftoppm' => (new \Symfony\Component\Process\ExecutableFinder())->find('pdftoppm') ?: config('services.pdf_tools.pdftoppm'),
        'ghostscript' => (new \Symfony\Component\Process\ExecutableFinder())->find('gs') ?: config('services.pdf_tools.ghostscript'),
        'imagick' => extension_loaded('imagick') ? 'php extension' : null,
    ]);

    $this->info('เครื่องมือสร้างปกฝั่งเซิร์ฟเวอร์: '.($tools ? implode(', ', array_keys($tools)) : 'ไม่มี (จะให้เบราว์เซอร์ของผู้ดูแลสร้างแทน)'));
    $this->line('fileinfo extension: '.(function_exists('mime_content_type') ? 'มี' : 'ไม่มี'));
    $this->newLine();

    $rows = [];

    \App\Models\Ebook::query()->orderBy('id')->each(function ($ebook) use ($files, &$rows) {
        $uploaded = $ebook->cover ? ($files->hasUploadedCover($ebook) ? 'มีไฟล์' : 'ไม่พบไฟล์!') : '-';
        $pdf = $files->pdfAbsolutePath($ebook->file_path) ? 'มี' : 'ไม่พบไฟล์!';

        $rows[] = [
            $ebook->id,
            \Illuminate\Support\Str::limit($ebook->title, 40),
            $uploaded,
            $files->hasAutoCover($ebook) ? 'มี' : '-',
            $pdf,
            $files->coverAbsolutePath($ebook) ? 'แสดงได้' : 'ใช้ปกจำลอง',
        ];
    });

    $this->table(['ID', 'ชื่อเรื่อง', 'รูปปกอัปโหลด', 'ปกจาก PDF', 'ไฟล์ PDF', 'ผล'], $rows);
})->purpose('ตรวจสถานะรูปปกของ Ebook ทุกเล่ม');
