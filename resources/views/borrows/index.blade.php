@extends('layouts.app')

@section('title', 'การยืม–คืน')

@php
    use App\Support\ThaiDate;

    $adminMode = auth()->user()->isAdmin() && session('view_mode') !== 'user';
    $items = collect($borrows->items());
    $statusTabs = ['' => 'ทั้งหมด', 'borrowed' => 'กำลังยืม', 'expired' => 'หมดอายุ', 'returned' => 'คืนแล้ว'];
@endphp

@section('content')

    <div class="page-head">
        <div>
            {{-- <p class="page-eyebrow">{{ $adminMode ? 'Circulation' : 'My bookshelf' }}</p> --}}
            <h1 class="page-title">{{ $adminMode ? 'รายการยืม–คืนทั้งหมด' : 'ชั้นหนังสือของฉัน' }}</h1>
            <p class="page-sub">
                {{ $adminMode ? 'ติดตามสถานะการยืม ต่ออายุ และคืนแทนผู้ใช้' : 'Ebook ที่คุณยืมอยู่และประวัติการยืมทั้งหมด' }}
            </p>
        </div>

        @if ($adminMode)
            <form action="{{ route('borrows.index') }}" method="GET" class="flex w-full gap-2 sm:w-auto">
                <input type="hidden" name="status" value="{{ request('status') }}">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="ชื่อ อีเมล หรือชื่อหนังสือ" class="input sm:w-72">
                <button class="btn btn-primary"><i class="bi bi-search"></i></button>
            </form>
        @endif
    </div>

    @if ($adminMode)
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($statusTabs as $value => $label)
                <a href="{{ route('borrows.index', array_filter(['status' => $value, 'search' => request('search')])) }}"
                    class="btn btn-sm {{ (string) request('status') === (string) $value ? 'btn-primary' : 'btn-outline' }}">{{ $label }}</a>
            @endforeach
        </div>
    @else
        <div class="mb-6 grid grid-cols-3 gap-3">
            <div class="stat"><div class="stat-label">กำลังยืม</div><div class="stat-value">{{ $items->where('status', 'borrowed')->count() }}</div></div>
            <div class="stat"><div class="stat-label">คืนแล้ว</div><div class="stat-value">{{ $items->where('status', 'returned')->count() }}</div></div>
            <div class="stat"><div class="stat-label">ทั้งหมด</div><div class="stat-value">{{ $borrows->total() }}</div></div>
        </div>
    @endif

    @if ($borrows->isEmpty())
        <div class="card px-6 py-14 text-center">
            <i class="bi bi-bookmark text-5xl text-ink-200"></i>
            <p class="mt-3 font-semibold">ยังไม่มีรายการยืม</p>
            @unless ($adminMode)
                <a href="{{ route('ebooks.index') }}" class="btn btn-primary mt-4"><i class="bi bi-search"></i> ค้นหา Ebook</a>
            @endunless
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead>
                        <tr>
                            <th>หนังสือ</th>
                            @if ($adminMode)<th>ผู้ยืม</th>@endif
                            <th>วันที่ยืม</th>
                            <th>กำหนดคืน</th>
                            <th>สถานะ</th>
                            <th class="text-right">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($borrows as $borrow)
                            @php
                                $active = $borrow->status === 'borrowed' && $borrow->days_left >= 0;
                                $lapsed = $borrow->status === 'expired' || ($borrow->status === 'borrowed' && $borrow->days_left < 0);
                            @endphp
                            <tr>
                                <td class="min-w-[260px]">
                                    @if ($borrow->ebook)
                                        <a href="{{ route('ebooks.show', $borrow->ebook) }}" class="flex items-center gap-3">
                                            <x-book-cover :ebook="$borrow->ebook" class="!w-11 shrink-0 !rounded" />
                                            <span class="min-w-0">
                                                <span class="line-clamp-2 font-semibold leading-snug hover:text-royal-700">{{ $borrow->ebook->title }}</span>
                                                <span class="block truncate text-xs text-ink-400">{{ $borrow->ebook->author }}</span>
                                            </span>
                                        </a>
                                    @else
                                        <span class="text-ink-400">(หนังสือถูกลบ)</span>
                                    @endif
                                </td>
                                @if ($adminMode)
                                    <td class="whitespace-nowrap">
                                        <div class="font-medium">{{ $borrow->user->name ?? '-' }}</div>
                                        <div class="text-xs text-ink-400">{{ $borrow->user->role_name ?? '' }}</div>
                                    </td>
                                @endif
                                <td class="whitespace-nowrap">{{ ThaiDate::short($borrow->borrow_date) }}</td>
                                <td class="whitespace-nowrap">
                                    {{ ThaiDate::short($borrow->due_date) }}
                                    @if ($borrow->renew_count)
                                        <div class="text-xs text-ink-400">ต่ออายุ {{ $borrow->renew_count }} ครั้ง</div>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">
                                    @if ($borrow->status === 'returned')
                                        <span class="badge badge-ink">คืนแล้ว</span>
                                        @if ($borrow->return_date)
                                            <div class="mt-0.5 text-xs text-ink-400">{{ ThaiDate::short($borrow->return_date) }}</div>
                                        @endif
                                    @elseif ($lapsed)
                                        <span class="badge badge-red">หมดอายุการยืม</span>
                                    @elseif ($borrow->days_left <= 2)
                                        <span class="badge badge-amber">เหลือ {{ $borrow->days_left }} วัน</span>
                                    @else
                                        <span class="badge badge-green">กำลังยืม · เหลือ {{ $borrow->days_left }} วัน</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($active && $borrow->ebook && ! $adminMode)
                                            <a href="{{ route('ebooks.reader', $borrow->ebook) }}" class="btn btn-primary btn-sm"><i class="bi bi-book"></i> อ่าน</a>
                                        @endif

                                        @if ($borrow->status === 'borrowed')
                                            <form action="{{ route('borrows.return', $borrow) }}" method="POST"
                                                data-confirm="{{ $adminMode ? 'คืนหนังสือแทนผู้ยืม' : 'เมื่อคืนแล้วจะอ่านเต็มเล่มไม่ได้จนกว่าจะยืมใหม่' }}" data-confirm-title="ยืนยันการคืน?" data-confirm-button="คืนหนังสือ">
                                                @csrf
                                                <button class="btn btn-outline btn-sm">คืน</button>
                                            </form>
                                        @endif

                                        @if ($adminMode && in_array($borrow->status, ['borrowed', 'expired'], true) && ! $borrow->return_date)
                                            <form action="{{ route('borrows.renew', $borrow) }}" method="POST">
                                                @csrf
                                                <button class="btn btn-royal btn-sm"><i class="bi bi-plus-circle"></i> ต่ออายุ 7 วัน</button>
                                            </form>
                                        @endif

                                        @if (! $adminMode && $lapsed && $borrow->ebook)
                                            <a href="{{ route('ebooks.show', $borrow->ebook) }}" class="btn btn-gold btn-sm">ยืมอีกครั้ง</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $borrows->links() }}</div>

        @unless ($adminMode)
            <p class="mt-4 text-sm text-ink-500"><i class="bi bi-info-circle"></i> ต้องการต่ออายุ? กรุณาติดต่อบรรณารักษ์ก่อนวันครบกำหนด โทร 035-709081</p>
        @endunless
    @endif

@endsection
