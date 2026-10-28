<?php

namespace App\Services;

use App\Models\Ebook;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * จัดการไฟล์ของ Ebook โดยไม่ต้องแก้โครงสร้างฐานข้อมูล
 *
 * - ไฟล์ PDF ใหม่เก็บใน disk "local" (storage/app/private) ซึ่งเข้าถึงจากเว็บโดยตรงไม่ได้
 *   ไฟล์เดิมที่อยู่ใน disk "public" ยังอ่านได้ตามปกติ (ค่า file_path ในฐานข้อมูลเหมือนเดิม)
 * - ปกอัตโนมัติจากหน้าแรกของ PDF เก็บที่ public: ebooks/covers/auto/{id}.jpg
 * - ไฟล์ตัวอย่าง 5 หน้า เก็บใน disk "local" และบันทึก path ในคอลัมน์ preview_file (มีอยู่แล้ว)
 */
class EbookFileService
{
    public const PREVIEW_PAGES = 5;

    public const AUTO_COVER_DIR = 'ebooks/covers/auto';

    public const PREVIEW_DIR = 'ebooks/previews';

    /* ------------------------------------------------------------------
     | PDF ต้นฉบับ
     * ------------------------------------------------------------------ */

    /** เก็บ PDF ที่อัปโหลดลงพื้นที่ส่วนตัว */
    public function storePdf(UploadedFile $file): string
    {
        $path = $file->store('ebooks/pdf', 'local');

        if (! $path) {
            throw new \RuntimeException('ไม่สามารถบันทึกไฟล์ PDF ได้');
        }

        return $path;
    }

    /** คืน path จริงของไฟล์ PDF (รองรับทั้งไฟล์ใหม่และไฟล์เดิม) */
    public function pdfAbsolutePath(?string $relative): ?string
    {
        if (! $relative || str_contains($relative, '..')) {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($relative)) {
                return Storage::disk($disk)->path($relative);
            }
        }

        return null;
    }

    public function deletePdf(?string $relative): void
    {
        if (! $relative) {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            Storage::disk($disk)->delete($relative);
        }
    }

    /* ------------------------------------------------------------------
     | ปกหนังสือ
     * ------------------------------------------------------------------ */

    public function autoCoverRelative(Ebook $ebook): string
    {
        return self::AUTO_COVER_DIR.'/'.$ebook->id.'.jpg';
    }

    public function hasAutoCover(Ebook $ebook): bool
    {
        return Storage::disk('public')->exists($this->autoCoverRelative($ebook));
    }

    public function hasUploadedCover(Ebook $ebook): bool
    {
        return $ebook->cover && Storage::disk('public')->exists($ebook->cover);
    }

    /**
     * path จริงของไฟล์ปกที่ใช้แสดง (รูปที่อัปโหลด → ปกอัตโนมัติจากหน้าแรก PDF) หรือ null
     */
    public function coverAbsolutePath(Ebook $ebook): ?string
    {
        if ($this->hasUploadedCover($ebook)) {
            return Storage::disk('public')->path($ebook->cover);
        }

        $auto = $this->autoCoverRelative($ebook);

        return Storage::disk('public')->exists($auto)
            ? Storage::disk('public')->path($auto)
            : null;
    }

    /**
     * URL ของปกที่ใช้แสดงผล (null = ยังไม่มี ให้แสดงปกจำลอง)
     *
     * ส่งรูปผ่าน route ของ Laravel แทน /storage/... จึงไม่ต้องพึ่งลิงก์ public/storage
     * (ปัญหาที่พบบ่อยบน XAMPP/Windows ที่ทำให้รูปปกไม่แสดง)
     */
    public function coverUrl(Ebook $ebook): ?string
    {
        $path = $this->coverAbsolutePath($ebook);

        if (! $path) {
            return null;
        }

        return route('ebooks.cover', ['ebook' => $ebook->id, 'v' => @filemtime($path) ?: 0]);
    }

    /** บันทึกปกอัตโนมัติจากรูปที่เบราว์เซอร์ของผู้ดูแลเรนเดอร์ส่งมา */
    public function saveAutoCoverFromUpload(Ebook $ebook, UploadedFile $image): void
    {
        $info = @getimagesize($image->getRealPath());

        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new \InvalidArgumentException('ไฟล์ปกไม่ถูกต้อง');
        }

        Storage::disk('public')->putFileAs(
            self::AUTO_COVER_DIR,
            $image,
            $ebook->id.'.jpg'
        );
    }

    public function deleteAutoCover(Ebook $ebook): void
    {
        Storage::disk('public')->delete($this->autoCoverRelative($ebook));
    }

    /**
     * พยายามสร้างปกจากหน้าแรกของ PDF ฝั่งเซิร์ฟเวอร์
     * ใช้ pdftoppm (Poppler) / Ghostscript / Imagick ถ้ามีติดตั้ง
     * ถ้าไม่มีเครื่องมือ ระบบจะให้เบราว์เซอร์ของผู้ดูแลสร้างแทน (pdf.js)
     */
    public function generateAutoCover(Ebook $ebook): bool
    {
        $pdf = $this->pdfAbsolutePath($ebook->file_path);

        if (! $pdf) {
            return false;
        }

        Storage::disk('public')->makeDirectory(self::AUTO_COVER_DIR);
        $target = Storage::disk('public')->path($this->autoCoverRelative($ebook));
        $tmpBase = sys_get_temp_dir().DIRECTORY_SEPARATOR.'cover_'.$ebook->id.'_'.uniqid();

        try {
            if ($bin = $this->findBinary('pdftoppm', 'pdftoppm')) {
                $this->run([$bin, '-f', '1', '-l', '1', '-singlefile', '-jpeg', '-jpegopt', 'quality=85', '-scale-to', '900', $pdf, $tmpBase]);

                if (is_file($tmpBase.'.jpg')) {
                    return rename($tmpBase.'.jpg', $target) || copy($tmpBase.'.jpg', $target);
                }
            }

            if ($bin = $this->findBinary(['gs', 'gswin64c', 'gswin32c'], 'ghostscript')) {
                $this->run([$bin, '-q', '-dSAFER', '-dBATCH', '-dNOPAUSE', '-sDEVICE=jpeg', '-dJPEGQ=85', '-r110', '-dFirstPage=1', '-dLastPage=1', '-sOutputFile='.$target, $pdf]);

                if (is_file($target) && filesize($target) > 0) {
                    return true;
                }
            }

            if (extension_loaded('imagick')) {
                $im = new \Imagick();
                $im->setResolution(110, 110);
                $im->readImage($pdf.'[0]');
                $im->setImageFormat('jpeg');
                $im->setImageCompressionQuality(85);
                $im->thumbnailImage(900, 0);
                $im->writeImage($target);
                $im->clear();

                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('สร้างปกอัตโนมัติไม่สำเร็จ', ['ebook' => $ebook->id, 'error' => $e->getMessage()]);
        } finally {
            @unlink($tmpBase.'.jpg');
        }

        return false;
    }

    /* ------------------------------------------------------------------
     | ไฟล์ตัวอย่าง (5 หน้าแรก)
     * ------------------------------------------------------------------ */

    public function previewAbsolutePath(Ebook $ebook): ?string
    {
        if ($ebook->preview_file && Storage::disk('local')->exists($ebook->preview_file)) {
            return Storage::disk('local')->path($ebook->preview_file);
        }

        return null;
    }

    /** สร้างไฟล์ตัวอย่างฝั่งเซิร์ฟเวอร์ (FPDI → qpdf → Ghostscript) */
    public function generatePreview(Ebook $ebook): bool
    {
        $pdf = $this->pdfAbsolutePath($ebook->file_path);

        if (! $pdf) {
            return false;
        }

        $relative = self::PREVIEW_DIR.'/'.$ebook->id.'.pdf';
        Storage::disk('local')->makeDirectory(self::PREVIEW_DIR);
        $target = Storage::disk('local')->path($relative);

        $ok = false;

        try {
            $fpdi = new Fpdi();
            $count = $fpdi->setSourceFile($pdf);

            for ($i = 1; $i <= min(self::PREVIEW_PAGES, $count); $i++) {
                $tpl = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($tpl);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tpl);
            }

            $fpdi->Output('F', $target);
            $ok = is_file($target);

            if ($ok && ! $ebook->total_pages) {
                $ebook->forceFill(['total_pages' => $count])->saveQuietly();
            }
        } catch (\Throwable $e) {
            // ไฟล์บีบอัดแบบใหม่ FPDI รุ่นฟรีอ่านไม่ได้ → ลองเครื่องมืออื่น
        }

        if (! $ok && ($bin = $this->findBinary('qpdf', 'qpdf'))) {
            $this->run([$bin, '--empty', '--pages', $pdf, '1-'.self::PREVIEW_PAGES, '--', $target]);
            $ok = is_file($target) && filesize($target) > 0;
        }

        if (! $ok && ($bin = $this->findBinary(['gs', 'gswin64c', 'gswin32c'], 'ghostscript'))) {
            $this->run([$bin, '-q', '-dSAFER', '-dBATCH', '-dNOPAUSE', '-sDEVICE=pdfwrite', '-dFirstPage=1', '-dLastPage='.self::PREVIEW_PAGES, '-sOutputFile='.$target, $pdf]);
            $ok = is_file($target) && filesize($target) > 0;
        }

        if ($ok) {
            $ebook->forceFill(['preview_file' => $relative])->saveQuietly();
        }

        return $ok;
    }

    /** บันทึกไฟล์ตัวอย่างที่เบราว์เซอร์ของผู้ดูแลสร้างส่งมา (pdf-lib) */
    public function savePreviewFromUpload(Ebook $ebook, UploadedFile $file): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $magic = $handle ? fread($handle, 5) : '';
        if ($handle) {
            fclose($handle);
        }

        if ($magic !== '%PDF-') {
            throw new \InvalidArgumentException('ไฟล์ตัวอย่างไม่ใช่ PDF');
        }

        $relative = Storage::disk('local')->putFileAs(self::PREVIEW_DIR, $file, $ebook->id.'.pdf');
        $ebook->forceFill(['preview_file' => $relative])->saveQuietly();
    }

    public function deletePreview(Ebook $ebook): void
    {
        if ($ebook->preview_file) {
            Storage::disk('local')->delete($ebook->preview_file);
            Storage::disk('public')->delete($ebook->preview_file);
        }
        Storage::disk('local')->delete(self::PREVIEW_DIR.'/'.$ebook->id.'.pdf');
    }

    /** หลังอัปโหลด/เปลี่ยน PDF: ล้างไฟล์อนุพันธ์เดิมและพยายามสร้างใหม่ */
    public function refreshDerivedFiles(Ebook $ebook, bool $pdfChanged): void
    {
        if ($pdfChanged) {
            $this->deletePreview($ebook);
            $this->deleteAutoCover($ebook);
            $ebook->forceFill(['preview_file' => null])->saveQuietly();
        }

        if (! $ebook->preview_file) {
            $this->generatePreview($ebook);
        }

        if (! $this->hasUploadedCover($ebook) && ! $this->hasAutoCover($ebook)) {
            $this->generateAutoCover($ebook);
        }
    }

    /* ------------------------------------------------------------------ */

    private function findBinary(string|array $names, string $configKey): ?string
    {
        $configured = config('services.pdf_tools.'.$configKey);

        if ($configured && is_file($configured)) {
            return $configured;
        }

        if (! function_exists('proc_open')) {
            return null;
        }

        $finder = new ExecutableFinder();

        foreach ((array) $names as $name) {
            if ($found = $finder->find($name)) {
                return $found;
            }
        }

        return null;
    }

    private function run(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(60);
        $process->run();
    }
}
