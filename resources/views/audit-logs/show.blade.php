@extends('layouts.app')

@section('title', 'รายละเอียดประวัติการใช้งาน')

@php
    use App\Models\AuditLog;
    use App\Support\ThaiDate;

    $old = $log->old_values ?? [];
    $new = $log->new_values ?? [];
@endphp

@section('content')

    <div class="page-head">
        <div>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('audit-logs.index') }}" class="text-sm text-ink-500 hover:underline"><i class="bi bi-arrow-left"></i> ประวัติการใช้งาน</a>
            <h1 class="page-title">
                <span class="badge {{ $log->action_badge }} align-middle">{{ $log->action_name }}</span>
                {{ $log->subject_name }} {{ $log->subject_label }}
            </h1>
        </div>
    </div>

    <section class="card mb-5">
        <div class="card-body grid gap-4 text-sm md:grid-cols-4">
            <div>
                <div class="text-xs text-ink-400">ผู้ดูแลระบบ</div>
                <div class="font-semibold">{{ $log->user_name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-ink-400">วันเวลา</div>
                <div class="font-semibold">{{ ThaiDate::dateTime($log->created_at) }}</div>
            </div>
            <div>
                <div class="text-xs text-ink-400">IP</div>
                <div class="font-semibold">{{ $log->ip_address ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs text-ink-400">ID ข้อมูล</div>
                <div class="font-semibold">{{ $log->subject_id ?? '—' }}</div>
            </div>
            @if ($log->description)
                <div class="md:col-span-4">
                    <div class="text-xs text-ink-400">รายละเอียด</div>
                    <div>{{ $log->description }}</div>
                </div>
            @endif
            @if ($log->user_agent)
                <div class="md:col-span-4">
                    <div class="text-xs text-ink-400">เบราว์เซอร์</div>
                    <div class="break-all text-ink-500">{{ $log->user_agent }}</div>
                </div>
            @endif
        </div>
    </section>

    @if ($log->changedFields())
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th class="w-1/5">ฟิลด์</th>
                            @if ($log->action !== 'created')<th>ค่าเดิม</th>@endif
                            @if ($log->action !== 'deleted')<th>ค่าใหม่</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($log->changedFields() as $field)
                            <tr>
                                <td class="align-top font-medium">
                                    {{ AuditLog::fieldName($field) }}
                                    <div class="text-xs font-normal text-ink-400">{{ $field }}</div>
                                </td>
                                @if ($log->action !== 'created')
                                    <td class="align-top whitespace-pre-line break-words {{ $log->action === 'updated' ? 'bg-red-50/60 text-red-800' : '' }}">{{ AuditLog::display($old[$field] ?? null) }}</td>
                                @endif
                                @if ($log->action !== 'deleted')
                                    <td class="align-top whitespace-pre-line break-words {{ $log->action === 'updated' ? 'bg-green-50/60 text-green-800' : '' }}">{{ AuditLog::display($new[$field] ?? null) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection
