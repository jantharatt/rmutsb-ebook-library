<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogArchiver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * ประวัติการใช้งานของผู้ดูแลระบบ (อ่านอย่างเดียว)
 */
class AuditLogController extends Controller
{
    public function __construct(private AuditLogArchiver $archiver) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'in:'.implode(',', array_keys(AuditLog::ACTIONS))],
            'subject_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(AuditLog::SUBJECTS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $logs = AuditLog::query()
            ->when($filters['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
            ->when($filters['action'] ?? null, fn ($q, $v) => $q->where('action', $v))
            ->when($filters['subject_type'] ?? null, fn ($q, $v) => $q->where('subject_type', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->where('created_at', '>=', \Illuminate\Support\Carbon::parse($v)->startOfDay()))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->where('created_at', '<=', \Illuminate\Support\Carbon::parse($v)->endOfDay()))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($q) use ($v) {
                    $q->where('subject_label', 'like', "%{$v}%")
                        ->orWhere('description', 'like', "%{$v}%")
                        ->orWhere('user_name', 'like', "%{$v}%");
                });
            })
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        // แอดมินทั้งหมด + ผู้ที่เคยมี log (เผื่อถูกลดสิทธิ์ไปแล้ว)
        $admins = User::query()
            ->where('role', 'admin')
            ->orWhereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->select('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('audit-logs.index', [
            'logs' => $logs,
            'admins' => $admins,
            'filters' => $filters,
            'archives' => $this->archiver->files(),
            'pendingArchive' => $this->archiver->pendingCount(),
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        return view('audit-logs.show', ['log' => $auditLog]);
    }

    /** ดาวน์โหลดไฟล์ log ที่เก็บถาวร */
    public function download(string $file)
    {
        abort_unless(preg_match(AuditLogArchiver::FILE_PATTERN, $file), 404);

        $path = AuditLogArchiver::DIRECTORY.'/'.$file;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $file, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** เก็บถาวรทันที (กรณี scheduler ไม่ได้ทำงาน) */
    public function archive(Request $request)
    {
        try {
            $result = $this->archiver->archive($request->user());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'เก็บถาวรไม่สำเร็จ: '.$e->getMessage());
        }

        return back()->with('success', $result['count']
            ? 'เก็บถาวร log '.number_format($result['count']).' รายการเรียบร้อยแล้ว'
            : 'ไม่มี log ที่เก่ากว่าไตรมาสปัจจุบัน');
    }
}
