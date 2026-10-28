<?php
namespace App\Http\Controllers;
use App\Models\Ebook;
use App\Models\Category;
use App\Models\Borrow;
use App\Models\BorrowPolicy;
use App\Services\EbookFileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class EbookController extends Controller
{
    public function __construct(private EbookFileService $files)
    {
    }

public function index(Request $request)
{
    $search = $request->search;
    $type = $request->type;
    $category = $request->category;

    $query = Ebook::with('category');

    // ผู้ใช้ทั่วไปเห็นเฉพาะหนังสือที่เปิดให้บริการ
    if (! $this->isAdminView()) {
        $query->available();
    }

    /*
    |--------------------------------------------------------------------------
    | ค้นหาตามหมวด
    |--------------------------------------------------------------------------
    */

    if ($category) {

        $query->whereHas('category', function ($q) use ($category) {

            $q->where('parent_id', $category)
              ->orWhere('id', $category);

        });

    }

    /*
    |--------------------------------------------------------------------------
    | ค้นหา
    |--------------------------------------------------------------------------
    */

    if ($search) {

        switch ($type) {

            case 'title':

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('title_en', 'like', "%{$search}%");
                });
                break;

        case 'author':

            $query->where(function ($q) use ($search) {
                $q->where('author', 'like', "%{$search}%")
                ->orWhere('added_personal_name', 'like', "%{$search}%");
            });

            break;

            case 'isbn':

                $query->where('isbn','like',"%{$search}%");
                break;

            case 'call':

                $query->where('call_number','like',"%{$search}%");

                break;

            case 'subject':

                $query->where('subject_heading','like',"%{$search}%");

                break;

            default:

                $query->where(function($q) use ($search){

                    $q->where('title','like',"%{$search}%")
                      ->orWhere('author','like',"%{$search}%")
                      ->orWhere('added_personal_name','like',"%{$search}%")
                      ->orWhere('isbn','like',"%{$search}%")
                      ->orWhere('publisher','like',"%{$search}%")
                      ->orWhere('call_number','like',"%{$search}%")
                      ->orWhere('keywords','like',"%{$search}%")
                      ->orWhere('subject_heading','like',"%{$search}%")
                      ->orWhere('series_name','like',"%{$search}%")
                      ->orWhere('registration_number','like',"%{$search}%")
                      ->orWhere('faculty','like',"%{$search}%")
                      ->orWhere('campus','like',"%{$search}%")
                      ->orWhereHas('category', function($qq) use ($search){

                          $qq->where('name','like',"%{$search}%")
                             ->orWhere('code','like',"%{$search}%");

                      });

                });

        }

    }

    $sort = $request->query('sort', 'latest');

    match ($sort) {
        'popular' => $query->orderByDesc('borrow_count')->orderByDesc('view_count'),
        'title' => $query->orderBy('title'),
        'year' => $query->orderByDesc('publish_year'),
        default => $query->latest(),
    };

    $ebooks = $query
        ->paginate(12)
        ->withQueryString();

    $totalEbooks = Ebook::count();
    $activeEbooks = Ebook::where('status',1)->count();
    $inactiveEbooks = Ebook::where('status',0)->count();

    $mainCategories = Category::whereNull('parent_id')
        ->orderBy('code')
        ->get();

    return view(
        'ebooks.index',
        compact(
            'ebooks',
            'search',
            'type',
            'category',
            'sort',
            'mainCategories',
            'totalEbooks',
            'activeEbooks',
            'inactiveEbooks'
        )
    );
}

    public function create()
    {
        $mainCategories = Category::whereNull('parent_id')
            ->where('status', 1)
            ->get();

        $subCategories = Category::whereNotNull('parent_id')
            ->where('status', 1)
            ->get();

        return view(
            'ebooks.create',
            compact(
                'mainCategories',
                'subCategories'
            )
        );
    }


public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'author' => 'required|string|max:255',
        'category_id' => 'required|exists:categories,id',
        'pdf_file' => 'required|file|mimes:pdf|max:163840',
        'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        'auto_cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        'auto_preview' => 'nullable|file|mimes:pdf|max:51200',
        'publish_year' => 'nullable|integer|min:1000|max:2700',
        'isbn' => ['nullable', 'regex:/^[0-9]{10,13}$/'],

        'publisher' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'keywords' => 'nullable|string',
        'total_pages' => 'nullable|integer|min:0',
        'status' => 'nullable|in:0,1',
        'call_number' => 'nullable|string|max:255',
        'call_number_copy' => 'nullable|string|max:255',
        'edition' => 'nullable|string|max:255',
        'main_category_code' => 'nullable|string|max:255',
        'imprint' => 'nullable|string|max:255',
        'physical_description' => 'nullable|string|max:255',
        'series_name' => 'nullable|string|max:255',
        'subject_heading' => 'nullable|string',
        'added_personal_name' => 'nullable|string|max:255',
        'added_corporate_name' => 'nullable|string|max:255',
        'budget_year' => 'nullable|string|max:20',
        'registration_number' => 'nullable|string|max:255',
        'campus' => 'nullable|string|max:255',
        'faculty' => 'nullable|string|max:255',
        'cataloguer' => 'nullable|string|max:255',
        'source' => 'nullable|string|max:255',
    ], [
        'title.required' => 'กรุณากรอกชื่อ Ebook',
        'author.required' => 'กรุณากรอกชื่อผู้แต่ง',
        'category_id.required' => 'กรุณาเลือกหมวดหมู่',
        'category_id.exists' => 'ไม่พบหมวดหมู่ที่เลือก',
        'pdf_file.required' => 'กรุณาเลือกไฟล์ PDF',
        'pdf_file.mimes' => 'ไฟล์ Ebook ต้องเป็น PDF เท่านั้น',
        'pdf_file.max' => 'ไฟล์ PDF ต้องมีขนาดไม่เกิน 160 MiB',
        'cover.image' => 'ไฟล์ปกต้องเป็นไฟล์รูปภาพ',

        'isbn.required' => 'กรุณากรอก ISBN',
        'isbn.regex' => 'ISBN ต้องเป็นตัวเลข 10-13 หลัก',
    ]);

    $pdfPath = null;
    $coverPath = null;

    try {
        // เก็บ PDF ในพื้นที่ส่วนตัว (เปิดผ่าน URL ตรงไม่ได้)
        $pdfPath = $this->files->storePdf($request->file('pdf_file'));

        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')
                ->store('ebooks/covers', 'public');

            if (!$coverPath) {
                throw new \RuntimeException('ไม่สามารถบันทึกไฟล์ปกได้');
            }
        }

        $data = $validated;

        unset($data['pdf_file'], $data['cover'], $data['auto_cover'], $data['auto_preview']);

        $data['cover'] = $coverPath;
        $data['file_path'] = $pdfPath;
        $data['preview_file'] = null;
        $data['total_pages'] = $data['total_pages'] ?? 0;
        $data['status'] = $data['status'] ?? 1;
        $data['view_count'] = 0;
        $data['borrow_count'] = 0;
        $data['source'] = trim((string) ($validated['source'] ?? ''));


        $ebook = Ebook::create($data);
    } catch (\Throwable $e) {
        if ($pdfPath) {
            $this->files->deletePdf($pdfPath);
        }

        if ($coverPath) {
            Storage::disk('public')->delete($coverPath);
        }

        throw $e;
    }

    $this->storeDerivedFromRequest($request, $ebook);
    $this->files->refreshDerivedFiles($ebook, false);

    return redirect()
        ->route('ebooks.show', $ebook)
        ->with('success', 'เพิ่ม Ebook สำเร็จ');
}


    public function show(Ebook $ebook)
    {
        $this->ensureVisible($ebook);

        $user = Auth::user();

        $activeBorrow = Borrow::where('user_id', $user->id)
            ->where('ebook_id', $ebook->id)
            ->where('status', 'borrowed')
            ->latest('id')
            ->first();

        $policy = BorrowPolicy::where('role', $user->role)->first();

        $currentBorrowCount = Borrow::where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->count();

        $relatedBooks = Ebook::available()
            ->where('id', '!=', $ebook->id)
            ->where('category_id', $ebook->category_id)
            ->latest()
            ->take(6)
            ->get();

        // ผู้ดูแลเปิดดู: ลองสร้างปก/ตัวอย่างฝั่งเซิร์ฟเวอร์ก่อน (ถ้ามีเครื่องมือ) ที่เหลือให้เบราว์เซอร์ทำ
        if ($user->isAdmin()) {
            $this->files->refreshDerivedFiles($ebook, false);
            $ebook->refresh();
        }

        $needsDerived = $user->isAdmin() && (
            ! $ebook->preview_file
            || (! $this->files->hasUploadedCover($ebook) && ! $this->files->hasAutoCover($ebook))
        );

        return view(
            'ebooks.show',
            compact('ebook', 'activeBorrow', 'policy', 'currentBorrowCount', 'relatedBooks', 'needsDerived')
        );
    }

public function edit(Ebook $ebook)
{
    $mainCategories = Category::whereNull('parent_id')
        ->where('status', 1)
        ->get();

    $subCategories = Category::whereNotNull('parent_id')
        ->where('status', 1)
        ->get();

    return view(
        'ebooks.edit',
        compact(
            'ebook',
            'mainCategories',
            'subCategories'
        )
    );
}


public function update(Request $request, Ebook $ebook)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'author' => 'required|string|max:255',
        'category_id' => 'required|exists:categories,id',
       'isbn' => ['nullable', 'regex:/^[0-9]{10,13}$/'],

        'campus' => 'nullable|string|max:255',
        'edition' => 'nullable|string|max:255',
        'total_pages' => 'nullable|integer|min:0',
        'imprint' => 'nullable|string|max:255',
        'physical_description' => 'nullable|string|max:255',
        'series_name' => 'nullable|string|max:255',
        'subject_heading' => 'nullable|string',
        'call_number' => 'nullable|string|max:255',
        'call_number_copy' => 'nullable|string|max:255',
        'added_personal_name' => 'nullable|string|max:255',
        'added_corporate_name' => 'nullable|string|max:255',
        'main_category_code' => 'nullable|string|max:255',
        'budget_year' => 'nullable|string|max:20',
        'registration_number' => 'nullable|string|max:255',
        'faculty' => 'nullable|string|max:255',
        'cataloguer' => 'nullable|string|max:255',
        'source' => 'nullable|string|max:200',

        'publisher' => 'nullable|string|max:255',
        'publish_year' => 'nullable|integer|min:1000|max:2700',
        'description' => 'nullable|string',
        'keywords' => 'nullable|string',
        'status' => 'nullable|in:0,1',

        'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        'auto_cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        'auto_preview' => 'nullable|file|mimes:pdf|max:51200',
        'remove_cover' => 'nullable|boolean',
        'pdf_file' => 'nullable|file|mimes:pdf|max:163840',
    ], [
        'title.required' => 'กรุณากรอกชื่อหนังสือ',
        'author.required' => 'กรุณากรอกชื่อผู้แต่ง',
        'category_id.required' => 'กรุณาเลือกหมวดย่อย',
        'category_id.exists' => 'ไม่พบหมวดหมู่ที่เลือก',
        // 'isbn.required' => 'กรุณากรอก ISBN',
        'isbn.regex' => 'ISBN ต้องเป็นตัวเลข 10-13 หลัก',
        'cover.image' => 'ไฟล์ปกต้องเป็นรูปภาพ',

        'pdf_file.mimes' => 'ไฟล์ Ebook ต้องเป็น PDF เท่านั้น',
        'pdf_file.max' => 'ไฟล์ PDF ต้องมีขนาดไม่เกิน 160 MiB',
    ]);

    $newCoverPath = null;
    $newPdfPath = null;

    // เก็บตำแหน่งไฟล์เดิมไว้ก่อน
    $oldCoverPath = $ebook->cover;
    $oldPdfPath = $ebook->file_path;

    try {
        // บันทึกไฟล์ปกใหม่ โดยยังไม่ลบปกเดิม
        if ($request->hasFile('cover')) {
            $newCoverPath = $request->file('cover')
                ->store('ebooks/covers', 'public');

            if (!$newCoverPath) {
                throw new \RuntimeException('บันทึกไฟล์ปกไม่สำเร็จ');
            }

            $validated['cover'] = $newCoverPath;
        }

        // บันทึก PDF ใหม่ โดยยังไม่ลบ PDF เดิม
        if ($request->hasFile('pdf_file')) {
            $newPdfPath = $this->files->storePdf($request->file('pdf_file'));

            $validated['file_path'] = $newPdfPath;
        }

        // ไม่ส่งข้อมูลไฟล์เข้าเป็นฟิลด์ทั่วไป
        $removeCover = $request->boolean('remove_cover') && ! $newCoverPath;

        unset(
            $validated['cover'],
            $validated['pdf_file'],
            $validated['auto_cover'],
            $validated['auto_preview'],
            $validated['remove_cover']
        );

        if ($removeCover) {
            $validated['cover'] = null;
        }

        // กำหนดค่าไฟล์เฉพาะเมื่อมีการอัปโหลดไฟล์ใหม่
        if ($newCoverPath) {
            $validated['cover'] = $newCoverPath;
        }

        if ($newPdfPath) {
            $validated['file_path'] = $newPdfPath;
        }

        // ป้องกัน source เป็น null ในฐานข้อมูล
        $validated['source'] = trim(
            (string) ($validated['source'] ?? '')
        );

        // อัปเดตข้อมูลหนังสือ
        $ebook->update($validated);

    } catch (\Throwable $e) {
        // ถ้าบันทึกไม่สำเร็จ ให้ลบเฉพาะไฟล์ใหม่ที่เพิ่งอัปโหลด
        if ($newCoverPath) {
            Storage::disk('public')->delete($newCoverPath);
        }

        if ($newPdfPath) {
            $this->files->deletePdf($newPdfPath);
        }

        throw $e;
    }

    // ลบไฟล์เดิมหลังจากอัปเดตข้อมูลสำเร็จแล้วเท่านั้น
    if (($newCoverPath || $removeCover) && $oldCoverPath) {
        Storage::disk('public')->delete($oldCoverPath);
    }

    if ($newPdfPath && $oldPdfPath) {
        $this->files->deletePdf($oldPdfPath);
    }

    // PDF ใหม่ → ล้างปกอัตโนมัติ/ไฟล์ตัวอย่างเดิม แล้วสร้างใหม่
    $ebook->refresh();
    $this->files->refreshDerivedFiles($ebook, (bool) $newPdfPath);
    $this->storeDerivedFromRequest($request, $ebook);

    return redirect()
        ->route('ebooks.show', $ebook)
        ->with('success', 'แก้ไข Ebook สำเร็จ');
}


        public function destroy(Ebook $ebook)
    {
        // มีผู้ยืมอยู่ → ห้ามลบ
        if ($ebook->borrows()->where('status', 'borrowed')->exists()) {
            return back()->with('error', 'ยังมีผู้ยืม Ebook เล่มนี้อยู่ ไม่สามารถลบได้ (แนะนำให้ปิดการให้บริการแทน)');
        }

        // ลบรูปปก
        if ($ebook->cover && Storage::disk('public')->exists($ebook->cover)) {
            Storage::disk('public')->delete($ebook->cover);
        }

        // ลบไฟล์ PDF ปกอัตโนมัติ และไฟล์ตัวอย่าง
        $this->files->deletePdf($ebook->file_path);
        $this->files->deleteAutoCover($ebook);
        $this->files->deletePreview($ebook);

        // ลบประวัติการยืมของ Ebook นี้
        $ebook->borrows()->delete();

        // ลบข้อมูล Ebook
        $ebook->delete();

        return redirect()
            ->route('ebooks.index')
            ->with('success', 'ลบ Ebook สำเร็จ');
    }

public function preview(Ebook $ebook)
{
    $this->ensureVisible($ebook);

    if (! $ebook->file_path) {
        return back()->with('error', 'ไม่พบไฟล์ PDF');
    }

    return view('ebooks.preview', compact('ebook'));
}

/**
 * ส่งไฟล์ตัวอย่าง 5 หน้าแรก (ไม่ส่งไฟล์ฉบับเต็มให้ผู้ที่ยังไม่ได้ยืม)
 */
public function sample(Ebook $ebook)
{
    $this->ensureVisible($ebook);

    $path = $this->files->previewAbsolutePath($ebook);

    if (! $path && $this->files->generatePreview($ebook->refresh())) {
        $path = $this->files->previewAbsolutePath($ebook);
    }

    // ผู้ดูแลระบบ/ผู้ที่ยืมอยู่ ดูจากไฟล์เต็มได้
    if (! $path && $this->canReadFull($ebook)) {
        $path = $this->files->pdfAbsolutePath($ebook->file_path);
    }

    if (! $path) {
        abort(404, 'ตัวอย่างของหนังสือเล่มนี้ยังไม่พร้อม');
    }

    return $this->pdfResponse($path, 'preview-'.$ebook->id.'.pdf');
}

public function reader(Ebook $ebook)
{
    $this->ensureVisible($ebook);

    if (! $this->canReadFull($ebook)) {
        $borrow = Borrow::where('user_id', Auth::id())
            ->where('ebook_id', $ebook->id)
            ->where('status', 'borrowed')
            ->latest('id')
            ->first();

        if ($borrow && ! $borrow->isActive()) {
            $borrow->update(['status' => 'expired']);

            return redirect()
                ->route('ebooks.show', $ebook)
                ->with('error', 'Ebook หมดอายุการยืมแล้ว สามารถยืมใหม่ได้');
        }

        return redirect()
            ->route('ebooks.show', $ebook)
            ->with('error', 'กรุณายืม Ebook ก่อนเปิดอ่าน');
    }

    $ebook->increment('view_count');

    $activeBorrow = Borrow::where('user_id', Auth::id())
        ->where('ebook_id', $ebook->id)
        ->where('status', 'borrowed')
        ->latest('id')
        ->first();

    return view('ebooks.reader', compact('ebook', 'activeBorrow'));
}

/**
 * ส่งไฟล์ PDF ฉบับเต็ม: เฉพาะผู้ดูแลระบบ หรือผู้ที่ยืมอยู่และยังไม่ครบกำหนด
 */
public function stream(Ebook $ebook)
{
    if (! $this->canReadFull($ebook)) {
        abort(403, 'กรุณายืม Ebook ก่อนเปิดอ่าน');
    }

    $path = $this->files->pdfAbsolutePath($ebook->file_path);

    if (! $path) {
        abort(404);
    }

    return $this->pdfResponse($path, 'ebook-'.$ebook->id.'.pdf');
}

/**
 * ส่งรูปปก (ไม่ต้องพึ่งลิงก์ public/storage)
 */
public function cover(Ebook $ebook)
{
    $path = $this->files->coverAbsolutePath($ebook);

    if (! $path || ! is_file($path)) {
        abort(404);
    }

    $mime = function_exists('mime_content_type') ? (@mime_content_type($path) ?: null) : null;
    $mime ??= match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        default => 'image/jpeg',
    };

    if (! str_starts_with($mime, 'image/')) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => $mime,
        'Cache-Control' => 'private, max-age=604800',
        'X-Content-Type-Options' => 'nosniff',
    ]);
}

/**
 * รับปก/ไฟล์ตัวอย่างที่เบราว์เซอร์ของผู้ดูแลสร้างจาก PDF (กรณีเซิร์ฟเวอร์ไม่มีเครื่องมือแปลง)
 */
public function storeDerived(Request $request, Ebook $ebook)
{
    $request->validate([
        'auto_cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        'auto_preview' => 'nullable|file|mimes:pdf|max:51200',
        'total_pages' => 'nullable|integer|min:1|max:100000',
    ]);

    $this->storeDerivedFromRequest($request, $ebook);

    if ($request->filled('total_pages') && ! $ebook->total_pages) {
        $ebook->forceFill(['total_pages' => (int) $request->input('total_pages')])->saveQuietly();
    }

    return response()->json([
        'ok' => true,
        'cover_url' => $ebook->fresh()->cover_url,
        'has_preview' => (bool) $ebook->fresh()->preview_file,
    ]);
}

/* ----------------------------------------------------------------------
 | Helpers
 * ---------------------------------------------------------------------- */

private function storeDerivedFromRequest(Request $request, Ebook $ebook): void
{
    try {
        if ($request->hasFile('auto_cover') && ! $this->files->hasUploadedCover($ebook)) {
            $this->files->saveAutoCoverFromUpload($ebook, $request->file('auto_cover'));
        }

        if ($request->hasFile('auto_preview') && ! $this->files->previewAbsolutePath($ebook)) {
            $this->files->savePreviewFromUpload($ebook, $request->file('auto_preview'));
        }
    } catch (\Throwable $e) {
        report($e);
    }
}

private function isAdminView(): bool
{
    return Auth::user()?->isAdmin() && session('view_mode') !== 'user';
}

/** หนังสือที่ปิดให้บริการ ผู้ใช้ทั่วไปมองไม่เห็น */
private function ensureVisible(Ebook $ebook): void
{
    if ((int) $ebook->status !== 1 && ! Auth::user()?->isAdmin()) {
        abort(404);
    }
}

private function canReadFull(Ebook $ebook): bool
{
    $user = Auth::user();

    if (! $user) {
        return false;
    }

    if ($user->isAdmin()) {
        return true;
    }

    if ((int) $ebook->status !== 1) {
        return false;
    }

    $borrow = Borrow::where('user_id', $user->id)
        ->where('ebook_id', $ebook->id)
        ->where('status', 'borrowed')
        ->latest('id')
        ->first();

    return $borrow && $borrow->isActive();
}

private function pdfResponse(string $path, string $downloadName)
{
    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
        'Cache-Control' => 'private, no-store, max-age=0',
        'X-Robots-Tag' => 'noindex, nofollow',
    ]);
}

}
