<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use App\Models\Borrow;
use App\Mail\BorrowReminderMail;
use Illuminate\Support\Facades\Mail;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


/*
|--------------------------------------------------------------------------
| ส่งอีเมลก่อนครบกำหนด 2 วัน
|--------------------------------------------------------------------------
*/

Schedule::call(function () {

    Borrow::with(['user','ebook'])

        ->where('status','borrowed')

        ->whereDate(
            'due_date',
            now()->addDays(2)->toDateString()
        )

        ->get()

        ->each(function ($borrow) {

            Mail::to($borrow->user->email)

                ->send(

                    new BorrowReminderMail($borrow)

                );

        });

})->daily();



/*
|--------------------------------------------------------------------------
| ครบกำหนด เปลี่ยนเป็น expired
|--------------------------------------------------------------------------
*/

Schedule::call(function () {

    Borrow::where('status','borrowed')

        ->whereDate(
            'due_date',
            '<',
            now()->toDateString()
        )

        ->update([

            'status' => 'expired'

        ]);

})->daily();

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
