@extends('layouts.app')

@section('title', 'หน้าแรก')

@php
    use App\Support\ThaiDate;

    $user = auth()->user();
    $hour = now()->hour;
    $greet = $hour < 12 ? 'สวัสดีตอนเช้า' : ($hour < 17 ? 'สวัสดีตอนบ่าย' : 'สวัสดีตอนเย็น');

    // ไอคอนประจำหมวดหลักระบบทศนิยมดิวอี้
    $deweyIcons = [
        '000' => 'bi-pc-display', '100' => 'bi-lightbulb', '200' => 'bi-flower1', '300' => 'bi-people',
        '400' => 'bi-translate', '500' => 'bi-radioactive', '600' => 'bi-gear-wide-connected',
        '700' => 'bi-palette', '800' => 'bi-feather', '900' => 'bi-globe-asia-australia',
    ];

    $maxBooks = $policy->max_books ?? 0;
    $borrowedNow = $myBorrows->count();
@endphp

@section('content')

    {{-- ===== Hero ===== --}}
    <section class="relative -mx-5 -mt-7 mb-10 overflow-hidden bg-ink-900 px-5 pb-14 pt-12 text-white sm:mx-0 sm:mt-0 sm:rounded-3xl sm:px-10">
        <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
            style="background-image: repeating-linear-gradient(90deg, #fff 0 2px, transparent 2px 36px), repeating-linear-gradient(0deg, #fff 0 1px, transparent 1px 36px);"></div>
        <div class="pointer-events-none absolute -right-24 -top-24 h-80 w-80 rounded-full bg-gold-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-32 left-1/3 h-72 w-72 rounded-full bg-royal-500/30 blur-3xl"></div>

        <div class="relative grid items-center gap-10 lg:grid-cols-[1.4fr_1fr]">
            <div>
                <p class="text-sm text-gold-300">{{ $greet }}, คุณ{{ \Illuminate\Support\Str::before($user->name, ' ') }}</p>
                <h1 class="mt-2 font-display text-3xl font-bold leading-tight text-white sm:text-[42px]">
                    ค้นคว้า อ่าน และเรียนรู้<br class="hidden sm:block">
                    <span class="text-gold-300">ได้ทุกที่ ทุกเวลา</span>
                </h1>
                <p class="mt-3 max-w-xl text-ink-200">
                    คลังหนังสืออิเล็กทรอนิกส์ของมหาวิทยาลัย {{ number_format($totalEbooks) }} รายการ
                    ยืมออนไลน์ได้ทันทีด้วยบัญชีมหาวิทยาลัย
                </p>

                <form action="{{ route('ebooks.index') }}" method="GET" class="mt-7 max-w-2xl" role="search">
                    <div class="flex flex-col gap-2 rounded-2xl bg-white p-2 shadow-lift sm:flex-row">
                        <select name="type" class="select rounded-xl border-0 bg-ink-50 sm:w-40" aria-label="ค้นหาจาก">
                            <option value="">ทุกช่อง</option>
                            <option value="title">ชื่อเรื่อง</option>
                            <option value="author">ผู้แต่ง</option>
                            <option value="subject">หัวเรื่อง</option>
                            <option value="isbn">ISBN</option>
                            <option value="call">เลขเรียกหนังสือ</option>
                        </select>
                        <input type="search" name="search" placeholder="พิมพ์คำค้น เช่น ระบบควบคุม, สาหร่าย, 9786..."
                            class="input flex-1 border-0 text-base focus:ring-0" aria-label="คำค้น">
                        <button class="btn btn-gold btn-lg"><i class="bi bi-search"></i> ค้นหา</button>
                    </div>
                </form>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-ink-200">
                    <span>ค้นหายอดนิยม:</span>
                    @foreach ($popularBooks->take(3) as $book)
                        <a href="{{ route('ebooks.show', $book) }}" class="rounded-full bg-white/10 px-3 py-1 hover:bg-white/20">
                            {{ \Illuminate\Support\Str::limit($book->title, 28) }}
                        </a>
                    @endforeach
                    <button type="button" data-open-chat="" class="rounded-full border border-gold-400/50 px-3 py-1 text-gold-300 hover:bg-gold-400/10">
                        <i class="bi bi-stars"></i> ถามผู้ช่วยห้องสมุด
                    </button>
                </div>
            </div>

            {{-- การ์ดสิทธิ์ของฉัน --}}
            <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-6 backdrop-blur">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-ink-200">สิทธิ์การยืมของฉัน</div>
                        <div class="mt-1 font-semibold">{{ $user->role_name }}@if ($user->faculty) · {{ $user->faculty }}@endif</div>
                    </div>
                    <i class="bi bi-person-badge text-3xl text-gold-300"></i>
                </div>

                @if ($policy)
                    <div class="mt-5 flex items-end gap-2">
                        <span class="font-display text-5xl font-bold text-white">{{ $borrowedNow }}</span>
                        <span class="pb-2 text-ink-200">/ {{ $maxBooks }} เล่ม ที่ยืมอยู่</span>
                    </div>
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/10">
                        <div class="h-full rounded-full bg-gold-400" style="width: {{ $maxBooks ? min(100, $borrowedNow / $maxBooks * 100) : 0 }}%"></div>
                    </div>
                    <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-white/5 p-3">
                            <dt class="text-xs text-ink-200">ยืมเพิ่มได้อีก</dt>
                            <dd class="text-lg font-bold">{{ max(0, $maxBooks - $borrowedNow) }} เล่ม</dd>
                        </div>
                        <div class="rounded-xl bg-white/5 p-3">
                            <dt class="text-xs text-ink-200">ระยะเวลายืม</dt>
                            <dd class="text-lg font-bold">{{ $policy->borrow_days }} วัน</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-5 text-sm text-ink-200">
                        @if ($user->isAdmin())
                            ผู้ดูแลระบบเปิดอ่านได้ทุกเล่มโดยไม่ต้องยืม
                        @else
                            ยังไม่มีนโยบายการยืมสำหรับประเภทผู้ใช้ของคุณ กรุณาติดต่อห้องสมุด
                        @endif
                    </p>
                @endif

                <p class="mt-4 text-xs text-ink-300">ยืมมาแล้วทั้งหมด {{ number_format($totalBorrowed) }} ครั้ง</p>
            </div>
        </div>
    </section>

    {{-- ===== กำลังอ่าน ===== --}}
    @if ($myBorrows->isNotEmpty())
        <section class="mb-12">
            <div class="mb-4 flex items-end justify-between">
                <div>
                    <p class="page-eyebrow">Continue reading</p>
                    <h2 class="section-title">กำลังอ่านอยู่</h2>
                </div>
                <a href="{{ route('borrows.index') }}" class="text-sm font-semibold text-royal-700 hover:underline">ชั้นหนังสือของฉัน →</a>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($myBorrows as $borrow)
                    @continue(! $borrow->ebook)
                    @php
                        $daysLeft = (int) today()->diffInDays($borrow->due_date, false);
                    @endphp
                    <div class="card flex gap-4 p-4">
                        <a href="{{ route('ebooks.show', $borrow->ebook) }}" class="w-20 shrink-0">
                            <x-book-cover :ebook="$borrow->ebook" />
                        </a>
                        <div class="flex min-w-0 flex-1 flex-col">
                            <a href="{{ route('ebooks.show', $borrow->ebook) }}" class="line-clamp-2 font-semibold leading-snug hover:text-royal-700">
                                {{ $borrow->ebook->title }}
                            </a>
                            <div class="mt-1 text-xs text-ink-500">ครบกำหนด {{ ThaiDate::short($borrow->due_date) }}</div>
                            <div class="mt-1">
                                @if ($daysLeft <= 2)
                                    <span class="badge badge-amber"><i class="bi bi-hourglass-split"></i> เหลือ {{ $daysLeft }} วัน</span>
                                @else
                                    <span class="badge badge-green">เหลือ {{ $daysLeft }} วัน</span>
                                @endif
                            </div>
                            <a href="{{ route('ebooks.reader', $borrow->ebook) }}" class="btn btn-primary btn-sm mt-auto self-start">
                                <i class="bi bi-book"></i> อ่านต่อ
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== หมวดหมู่ ===== --}}
    <section class="mb-12">
        <div class="mb-4">
            <p class="page-eyebrow">Browse by subject</p>
            <h2 class="section-title">เลือกตามหมวดหมู่ (ระบบทศนิยมดิวอี้)</h2>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($mainCategories as $category)
                <a href="{{ route('ebooks.index', ['category' => $category->id]) }}"
                    class="card group flex items-center gap-3 p-4 transition hover:-translate-y-0.5 hover:border-gold-300 hover:shadow-lift">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-ink-50 text-xl text-ink-700 transition group-hover:bg-gold-400 group-hover:text-ink-950">
                        <i class="bi {{ $deweyIcons[$category->code] ?? 'bi-bookmark' }}"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-[11px] font-bold tracking-wider text-gold-600">{{ $category->code }}</span>
                        <span class="block truncate text-sm font-semibold leading-tight" title="{{ $category->name }}">{{ $category->name }}</span>
                        <span class="block text-xs text-ink-400">{{ number_format($categoryCounts[$category->id] ?? 0) }} เล่ม</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ===== มาใหม่ ===== --}}
    <section class="mb-12">
        <div class="mb-4 flex items-end justify-between">
            <div>
                <p class="page-eyebrow">New arrivals</p>
                <h2 class="section-title">Ebook มาใหม่</h2>
            </div>
            <a href="{{ route('ebooks.index', ['sort' => 'latest']) }}" class="text-sm font-semibold text-royal-700 hover:underline">ดูทั้งหมด →</a>
        </div>

        @if ($latestEbooks->isEmpty())
            <div class="card p-10 text-center text-ink-400">ยังไม่มี Ebook ในระบบ</div>
        @else
            <div class="grid grid-cols-2 gap-x-5 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($latestEbooks as $ebook)
                    <x-book-card :ebook="$ebook" :badge="$ebook->created_at && $ebook->created_at->gt(now()->subDays(30)) ? 'ใหม่' : null" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- ===== ยอดนิยม ===== --}}
    @if ($popularBooks->isNotEmpty())
        <section class="mb-6">
            <div class="mb-4 flex items-end justify-between">
                <div>
                    <p class="page-eyebrow">Most borrowed</p>
                    <h2 class="section-title">หนังสือยอดนิยม</h2>
                </div>
                <a href="{{ route('ebooks.index', ['sort' => 'popular']) }}" class="text-sm font-semibold text-royal-700 hover:underline">ดูทั้งหมด →</a>
            </div>

            <div class="grid grid-cols-2 gap-x-5 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($popularBooks as $i => $ebook)
                    <x-book-card :ebook="$ebook" :badge="'#'.($i + 1).' · ยืม '.$ebook->borrows_count.' ครั้ง'" />
                @endforeach
            </div>
        </section>
    @endif

@endsection
