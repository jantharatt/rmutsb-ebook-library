@extends('layouts.app')

@section('title', 'ผู้ใช้งาน')

@php
    use App\Support\ThaiDate;

    $roles = ['student' => 'นักศึกษา', 'alumni' => 'ศิษย์เก่า', 'teacher' => 'อาจารย์', 'staff' => 'บุคลากร', 'admin' => 'ผู้ดูแลระบบ'];
    $percent = $stats['total'] ? round($stats['loggedIn'] / $stats['total'] * 100, 1) : 0;
@endphp

@section('content')

    <div class="page-head">
        <div>
            {{-- <p class="page-eyebrow">User management</p> --}}
            <h1 class="page-title">ผู้ใช้งาน</h1>
            <p class="page-sub">กำหนดสิทธิ์ผู้ใช้ (รวมถึงผู้ดูแลระบบ) และดูจำนวนผู้ที่เข้าใช้งานระบบแล้ว</p>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus"></i> เพิ่มผู้ใช้</a>
    </div>

    {{-- ===== สรุปการเข้าใช้งาน ===== --}}
    <section class="mb-6 grid gap-3 md:grid-cols-4">
        <div class="stat md:col-span-2">
            <div class="stat-label">เข้าใช้งานระบบแล้ว (นับครั้งเดียวต่อคน)</div>
            <div class="mt-1 flex items-end gap-2">
                <span class="stat-value">{{ number_format($stats['loggedIn']) }}</span>
                <span class="pb-1.5 text-ink-500">จากผู้ใช้ทั้งหมด {{ number_format($stats['total']) }} คน</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-ink-50">
                <div class="h-full rounded-full bg-[#4061b8]" style="width: {{ $percent }}%"></div>
            </div>
            <div class="mt-1 text-xs text-ink-400">{{ $percent }}% · ยังไม่เคยเข้าใช้ {{ number_format($stats['total'] - $stats['loggedIn']) }} คน</div>
        </div>
        <div class="stat">
            <div class="stat-label">เข้าใช้ครั้งแรกวันนี้</div>
            <div class="stat-value">{{ number_format($stats['today']) }}</div>
            <div class="text-xs text-ink-400">คน</div>
        </div>
        <div class="stat">
            <div class="stat-label">ตามประเภท (เข้าใช้แล้ว / ทั้งหมด)</div>
            <div class="mt-2 space-y-1 text-sm">
                @foreach ($roles as $key => $label)
                    @continue(! isset($stats['byRole'][$key]))
                    <div class="flex justify-between">
                        <span class="text-ink-600">{{ $label }}</span>
                        <span class="font-semibold tabular-nums">{{ (int) $stats['byRole'][$key]->logged_in }} / {{ $stats['byRole'][$key]->total }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===== ตัวกรอง ===== --}}
    <form method="GET" action="{{ route('users.index') }}" class="card mb-5 p-3">
        <div class="grid gap-2 md:grid-cols-[1fr_180px_200px_auto]">
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-300"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="ชื่อ อีเมล หรือคณะ" class="input pl-10">
            </div>
            <select name="role" class="select">
                <option value="">ทุกประเภทผู้ใช้</option>
                @foreach ($roles as $key => $label)
                    <option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="login" class="select">
                <option value="">ทุกสถานะการเข้าใช้</option>
                <option value="yes" @selected($login === 'yes')>เคยเข้าสู่ระบบแล้ว</option>
                <option value="no" @selected($login === 'no')>ยังไม่เคยเข้าสู่ระบบ</option>
            </select>
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> กรอง</button>
        </div>
    </form>

    {{-- ===== รายชื่อ ===== --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-clean">
                <thead>
                    <tr>
                        <th>ผู้ใช้</th>
                        <th>คณะ / หน่วยงาน</th>
                        <th>เข้าสู่ระบบครั้งแรก</th>
                        <th>สิทธิ์ผู้ใช้</th>
                        <th>สถานะ</th>
                        <th class="text-right"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="min-w-[220px]">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $user->role === 'admin' ? 'bg-gold-400 text-ink-950' : 'bg-ink-50 text-ink-600' }} font-bold">
                                        {{ mb_substr($user->name, 0, 1) }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-semibold">{{ $user->name }}</span>
                                        <span class="block truncate text-xs text-ink-400">{{ $user->email }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="text-ink-600">
                                {{ $user->faculty ?: '—' }}
                                @if ($user->department)
                                    <div class="text-xs text-ink-400">{{ $user->department }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                @if ($user->email_verified_at)
                                    <span class="badge badge-green"><i class="bi bi-check2"></i> เข้าใช้แล้ว</span>
                                    <div class="mt-0.5 text-xs text-ink-400">{{ ThaiDate::dateTime($user->email_verified_at) }}</div>
                                @else
                                    <span class="badge badge-ink">ยังไม่เคยเข้า</span>
                                @endif
                            </td>

                            {{-- เปลี่ยนสิทธิ์/สถานะได้ทันที --}}
                            <td colspan="2" class="whitespace-nowrap">
                                <form method="POST" action="{{ route('users.update', $user) }}" class="flex items-center gap-2"
                                    data-confirm="เปลี่ยนสิทธิ์/สถานะของ {{ $user->name }}" data-confirm-title="ยืนยันการเปลี่ยนแปลง?" data-confirm-button="บันทึก">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" class="select w-36 py-1.5 text-xs" onchange="this.form.requestSubmit()" @disabled($user->id === auth()->id()) aria-label="สิทธิ์ของ {{ $user->name }}">
                                        @foreach ($roles as $key => $label)
                                            <option value="{{ $key }}" @selected($user->role === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <select name="status" class="select w-28 py-1.5 text-xs {{ $user->status ? '' : 'border-red-300 text-red-700' }}" onchange="this.form.requestSubmit()" @disabled($user->id === auth()->id()) aria-label="สถานะของ {{ $user->name }}">
                                        <option value="1" @selected($user->status == 1)>ใช้งาน</option>
                                        <option value="0" @selected($user->status == 0)>ระงับ</option>
                                    </select>
                                    @if ($user->id === auth()->id())
                                        <span class="text-xs text-ink-400">(บัญชีของคุณ)</span>
                                    @endif
                                </form>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-ghost btn-sm"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-ink-400">ไม่พบผู้ใช้ตามเงื่อนไข</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">{{ $users->links() }}</div>

    <p class="mt-4 text-xs text-ink-400">
        <i class="bi bi-info-circle"></i>
        บัญชีใหม่ได้สิทธิ์อัตโนมัติจากอีเมล: ขึ้นต้นด้วยตัวเลข (เช่น 6612345678-st@rmutsb.ac.th) = นักศึกษา,
        ชื่อ.นามสกุล@rmutsb.ac.th = อาจารย์ · เปลี่ยนเป็นศิษย์เก่า บุคลากร หรือผู้ดูแลระบบได้จากตารางนี้
    </p>

@endsection
