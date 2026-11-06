@extends('layouts.app')

@section('title', 'ประวัติการใช้งาน')

@php
    use App\Models\AuditLog;
    use App\Support\ThaiDate;
@endphp

@section('content')

    <div class="page-head">
        <div>
            <h1 class="page-title">ประวัติการใช้งาน</h1>
            <p class="page-sub">บันทึกการเพิ่ม แก้ไข ลบข้อมูล และการเข้าสู่ระบบของผู้ดูแลระบบทุกคน (แก้ไขหรือลบไม่ได้)</p>
        </div>
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <form method="GET" action="{{ route('audit-logs.index') }}" class="card mb-5 p-3">
        <div class="grid gap-2 md:grid-cols-3 lg:grid-cols-[1fr_170px_150px_150px_140px_140px_auto]">
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-300"></i>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ชื่อรายการ รายละเอียด หรือชื่อแอดมิน" class="input pl-10">
            </div>
            <select name="user_id" class="select" aria-label="ผู้ดูแลระบบ">
                <option value="">แอดมินทุกคน</option>
                @foreach ($admins as $admin)
                    <option value="{{ $admin->id }}" @selected(($filters['user_id'] ?? '') == $admin->id)>{{ $admin->name }}</option>
                @endforeach
            </select>
            <select name="action" class="select" aria-label="การกระทำ">
                <option value="">ทุกการกระทำ</option>
                @foreach (AuditLog::ACTIONS as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['action'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="subject_type" class="select" aria-label="ประเภทข้อมูล">
                <option value="">ทุกประเภทข้อมูล</option>
                @foreach (AuditLog::SUBJECTS as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['subject_type'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input" aria-label="ตั้งแต่วันที่" title="ตั้งแต่วันที่">
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input" aria-label="ถึงวันที่" title="ถึงวันที่">
            <div class="flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-funnel"></i> กรอง</button>
                @if (array_filter($filters))
                    <a href="{{ route('audit-logs.index') }}" class="btn btn-outline" title="ล้างตัวกรอง"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </div>
    </form>

    {{-- ===== รายการ log ===== --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>วันเวลา</th>
                        <th>ผู้ดูแลระบบ</th>
                        <th>การกระทำ</th>
                        <th>ข้อมูล</th>
                        <th>สิ่งที่เปลี่ยน</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php $fields = $log->action === 'updated' ? $log->changedFields() : []; @endphp
                        <tr>
                            <td class="whitespace-nowrap text-ink-600">{{ ThaiDate::dateTime($log->created_at) }}</td>
                            <td class="whitespace-nowrap font-semibold">
                                {{ $log->user_name ?? '—' }}
                                @if ($log->ip_address)
                                    <div class="text-xs font-normal text-ink-400">{{ $log->ip_address }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap"><span class="badge {{ $log->action_badge }}">{{ $log->action_name }}</span></td>
                            <td class="min-w-[220px]">
                                @if ($log->subject_type)
                                    <span class="text-xs text-ink-400">{{ $log->subject_name }}</span>
                                    <div class="font-medium">{{ $log->subject_label ?? '#'.$log->subject_id }}</div>
                                @else
                                    <span class="text-ink-400">—</span>
                                @endif
                            </td>
                            <td class="min-w-[200px] text-sm text-ink-600">
                                @if ($fields)
                                    {{ collect($fields)->map(fn ($f) => AuditLog::fieldName($f))->take(4)->implode(', ') }}@if (count($fields) > 4) และอีก {{ count($fields) - 4 }} ฟิลด์@endif
                                @endif
                                @if ($log->description)
                                    <div class="text-xs text-ink-500">{{ $log->description }}</div>
                                @endif
                            </td>
                            <td class="text-right">
                                @if ($log->old_values || $log->new_values)
                                    <a href="{{ route('audit-logs.show', $log) }}" class="btn btn-ghost btn-sm" title="ดูรายละเอียด"><i class="bi bi-eye"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-ink-400">ยังไม่มีประวัติตามเงื่อนไข</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $logs->links() }}</div>

    {{-- ===== ไฟล์ log ย้อนหลัง ===== --}}
    <section class="card mt-8">
        <div class="card-head flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="card-title"><i class="bi bi-archive"></i> ไฟล์ log ย้อนหลัง</h2>
                <p class="text-xs text-ink-400">ทุกต้นไตรมาส ระบบจะย้าย log ของไตรมาสก่อนหน้าไปเก็บเป็นไฟล์ CSV (เปิดด้วย Excel ได้) เพื่อไม่ให้ตารางใหญ่เกินไป</p>
            </div>
            @if ($pendingArchive)
                <form method="POST" action="{{ route('audit-logs.archive') }}"
                    data-confirm="ย้าย log ที่เก่ากว่าไตรมาสนี้ {{ number_format($pendingArchive) }} รายการไปเก็บเป็นไฟล์ แล้วลบออกจากตาราง"
                    data-confirm-title="เก็บถาวรตอนนี้?" data-confirm-button="เก็บถาวร">
                    @csrf
                    <button class="btn btn-outline btn-sm"><i class="bi bi-box-arrow-in-down"></i> เก็บถาวรตอนนี้ ({{ number_format($pendingArchive) }})</button>
                </form>
            @endif
        </div>
        <div class="card-body">
            @forelse ($archives as $archive)
                <div class="flex items-center justify-between gap-3 border-b border-ink-50 py-2 last:border-0">
                    <div>
                        <div class="font-medium">{{ $archive['quarter'] }}</div>
                        <div class="text-xs text-ink-400">{{ $archive['name'] }} · {{ number_format($archive['size'] / 1024, 1) }} KB · อัปเดต {{ ThaiDate::dateTime($archive['modified']) }}</div>
                    </div>
                    <a href="{{ route('audit-logs.archives.download', $archive['name']) }}" class="btn btn-outline btn-sm"><i class="bi bi-download"></i> ดาวน์โหลด</a>
                </div>
            @empty
                <p class="text-sm text-ink-400">ยังไม่มีไฟล์เก็บถาวร</p>
            @endforelse
        </div>
    </section>

@endsection
