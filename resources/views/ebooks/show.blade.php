@extends('layouts.app')

@section('title', $ebook->title)

@php
    use App\Support\ThaiDate;

    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $adminMode = $isAdmin && session('view_mode') !== 'user';
    $canRead = $isAdmin || ($activeBorrow && $activeBorrow->isActive());
    $daysLeft = $activeBorrow ? (int) today()->diffInDays($activeBorrow->due_date, false) : null;
    $limitReached = $policy && $currentBorrowCount >= $policy->max_books;

    $fields = [
        'ข้อมูลบรรณานุกรม' => [
            'ชื่อเรื่อง (อังกฤษ)' => $ebook->title_en !== $ebook->title ? $ebook->title_en : null,
            'ผู้แต่งร่วม' => $ebook->co_author,
            'พิมพลักษณ์' => $ebook->imprint,
            'ครั้งที่พิมพ์' => $ebook->edition,
            'บรรณลักษณ์' => $ebook->physical_description,
            'ชื่อชุด' => $ebook->series_name,
            'หัวเรื่อง' => $ebook->subject_heading,
            'รายการเพิ่มชื่อบุคคล' => $ebook->added_personal_name,
            'รายการเพิ่มชื่อนิติบุคคล' => $ebook->added_corporate_name,
            'ภาษา' => $ebook->language,
        ],
        'การจัดหมู่และทะเบียน' => [
            'เลขเรียกหนังสือ' => $ebook->call_number,
            'เลขเรียก นว & รส' => $ebook->call_number_copy,
            'หมวดหมู่' => $ebook->category ? $ebook->category->code.' '.$ebook->category->name : null,
            'เลขทะเบียน' => $ebook->registration_number,
            'ปีงบประมาณที่จัดซื้อ' => $ebook->budget_year,
            'ศูนย์พื้นที่' => $ebook->campus,
            'คณะ / สาขา' => $ebook->faculty,
            'แหล่งที่มา' => $ebook->source,
            'ผู้ลงรายการ' => $adminMode ? $ebook->cataloguer : null,
        ],
    ];
@endphp

@section('content')

    {{-- breadcrumb --}}
    <nav class="mb-5 flex flex-wrap items-center gap-1.5 text-sm text-ink-400" aria-label="breadcrumb">
        <a href="{{ route('ebooks.index') }}" class="hover:text-ink-800">Ebook</a>
        @if ($ebook->category)
            <i class="bi bi-chevron-right text-[10px]"></i>
            <a href="{{ route('ebooks.index', ['category' => $ebook->category->parent_id ?? $ebook->category->id]) }}" class="hover:text-ink-800">{{ $ebook->category->name }}</a>
        @endif
        <i class="bi bi-chevron-right text-[10px]"></i>
        <span class="line-clamp-1 text-ink-600">{{ $ebook->title }}</span>
    </nav>

    @if ((int) $ebook->status !== 1)
        <div class="alert alert-error mb-5"><i class="bi bi-eye-slash"></i> Ebook เล่มนี้ปิดให้บริการ ผู้ใช้ทั่วไปจะมองไม่เห็น</div>
    @endif

    <div class="grid gap-8 lg:grid-cols-[300px_1fr]">

        {{-- ===== ปก + การกระทำ ===== --}}
        <div>
            <div class="mx-auto max-w-[300px] lg:sticky lg:top-36">
                <x-book-cover :ebook="$ebook" eager class="rounded-xl shadow-lift" />

                @if (! $ebook->cover && $ebook->cover_url)
                    <p class="mt-2 text-center text-xs text-ink-400"><i class="bi bi-file-earmark-pdf"></i> ปกจากหน้าแรกของไฟล์ PDF</p>
                @endif

                <div id="borrow" class="mt-5 scroll-mt-40 space-y-2.5">
                    @if ($canRead)
                        <a href="{{ route('ebooks.reader', $ebook) }}" class="btn btn-primary btn-lg w-full">
                            <i class="bi bi-book-half"></i> อ่านเต็มเล่ม
                        </a>
                    @else
                        <form action="{{ route('ebooks.borrow', $ebook) }}" method="POST"
                            data-confirm="{{ $policy ? 'ยืมได้ '.$policy->borrow_days.' วัน ครบกำหนดระบบจะคืนให้อัตโนมัติ' : 'ยืนยันการยืม' }}"
                            data-confirm-title="ยืม Ebook เล่มนี้?" data-confirm-button="ยืนยันการยืม">
                            @csrf
                            <button class="btn btn-gold btn-lg w-full" @disabled($limitReached || ! $policy)>
                                <i class="bi bi-bookmark-plus"></i> ยืม Ebook
                            </button>
                        </form>
                        @if ($limitReached)
                            <p class="text-center text-xs text-red-600">คุณยืมครบ {{ $policy->max_books }} เล่มตามสิทธิ์แล้ว กรุณาคืนเล่มที่อ่านจบก่อน</p>
                        @elseif ($policy)
                            <p class="text-center text-xs text-ink-500">ยืมได้ {{ $policy->borrow_days }} วัน · ยืมอยู่ {{ $currentBorrowCount }}/{{ $policy->max_books }} เล่ม</p>
                        @endif
                    @endif

                    <a href="{{ route('ebooks.preview', $ebook) }}" class="btn btn-outline w-full">
                        <i class="bi bi-eye"></i> ดูตัวอย่าง 5 หน้าแรก
                    </a>

                    @if ($activeBorrow)
                        <div class="rounded-xl border {{ $daysLeft <= 2 ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' }} p-3 text-sm">
                            <div class="font-semibold {{ $daysLeft <= 2 ? 'text-amber-800' : 'text-emerald-800' }}">
                                <i class="bi bi-check2-circle"></i> คุณยืมเล่มนี้อยู่
                            </div>
                            <div class="mt-0.5 text-ink-600">อ่านได้ถึง {{ ThaiDate::long($activeBorrow->due_date) }} (เหลือ {{ max(0, $daysLeft) }} วัน)</div>
                            <form action="{{ route('borrows.return', $activeBorrow) }}" method="POST" class="mt-2"
                                data-confirm="เมื่อคืนแล้วจะเปิดอ่านเต็มเล่มไม่ได้จนกว่าจะยืมใหม่" data-confirm-title="คืน Ebook เล่มนี้?" data-confirm-button="คืนหนังสือ">
                                @csrf
                                <button class="text-xs font-semibold text-ink-600 underline underline-offset-2 hover:text-red-600">คืนหนังสือก่อนกำหนด</button>
                            </form>
                        </div>
                    @endif

                    @if ($adminMode)
                        <div class="flex gap-2 pt-2">
                            <a href="{{ route('ebooks.edit', $ebook) }}" class="btn btn-outline btn-sm flex-1"><i class="bi bi-pencil"></i> แก้ไขข้อมูล</a>
                        </div>
                        @if ($needsDerived)
                            <p class="text-xs text-ink-400" data-derive-url="{{ route('ebooks.derived', $ebook) }}"
                                data-stream-url="{{ route('ebooks.stream', $ebook) }}"
                                data-need-cover="{{ $ebook->cover_url ? 0 : 1 }}" data-need-preview="{{ $ebook->preview_file ? 0 : 1 }}">
                                <i class="bi bi-arrow-repeat"></i> ระบบกำลังสร้างปก/ไฟล์ตัวอย่างจาก PDF ให้อัตโนมัติ
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== รายละเอียด ===== --}}
        <div class="min-w-0">
            @if ($ebook->category)
                <span class="badge badge-gold">{{ $ebook->category->code }} · {{ $ebook->category->name }}</span>
            @endif

            <h1 class="mt-3 font-display text-2xl font-bold leading-snug text-ink-900 sm:text-3xl">{{ $ebook->title }}</h1>

            <p class="mt-2 text-ink-600">
                <span class="text-ink-400">โดย</span> <span class="font-semibold">{{ $ebook->author ?: 'ไม่ระบุผู้แต่ง' }}</span>
                @if ($ebook->publisher)
                    <span class="text-ink-300">·</span> {{ $ebook->publisher }}
                @endif
                @if ($ebook->publish_year)
                    <span class="text-ink-300">·</span> {{ $ebook->publish_year }}
                @endif
            </p>

            {{-- ข้อมูลสั้น --}}
            <dl class="mt-6 grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-ink-100 bg-ink-100 sm:grid-cols-4">
                @foreach ([
                    ['ISBN', $ebook->isbn ?: '—', 'bi-upc'],
                    ['จำนวนหน้า', $ebook->total_pages ? number_format($ebook->total_pages).' หน้า' : '—', 'bi-file-earmark-text'],
                    ['เปิดอ่าน', number_format($ebook->view_count).' ครั้ง', 'bi-eye'],
                    ['ถูกยืม', number_format($ebook->borrow_count).' ครั้ง', 'bi-arrow-left-right'],
                ] as [$label, $value, $icon])
                    <div class="bg-white p-4">
                        <dt class="flex items-center gap-1.5 text-xs text-ink-400"><i class="bi {{ $icon }}"></i> {{ $label }}</dt>
                        <dd class="mt-1 truncate font-semibold text-ink-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            {{-- บทคัดย่อ --}}
            @if ($ebook->description)
                <section class="mt-8">
                    <h2 class="section-title mb-2">เกี่ยวกับหนังสือ</h2>
                    <div class="max-w-3xl whitespace-pre-line leading-8 text-ink-700">{{ $ebook->description }}</div>
                </section>
            @endif

            {{-- คำสำคัญ --}}
            @if ($ebook->keywords)
                <section class="mt-6">
                    <h2 class="mb-2 text-sm font-bold text-ink-700">คำสำคัญ</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach (array_filter(array_map('trim', explode(',', $ebook->keywords))) as $keyword)
                            <a href="{{ route('ebooks.index', ['search' => $keyword]) }}" class="badge badge-blue hover:bg-royal-100">#{{ $keyword }}</a>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ข้อมูลรายการ --}}
            @foreach ($fields as $group => $items)
                @php $items = array_filter($items, fn ($v) => filled($v)); @endphp
                @continue(empty($items))
                <section class="card mt-6">
                    <div class="card-head py-3">
                        <h2 class="card-title text-sm">{{ $group }}</h2>
                    </div>
                    <dl class="grid gap-x-8 gap-y-4 p-5 sm:grid-cols-2">
                        @foreach ($items as $label => $value)
                            <div>
                                <dt class="text-xs text-ink-400">{{ $label }}</dt>
                                <dd class="mt-0.5 text-sm font-medium text-ink-800">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach

            {{-- อ้างอิง --}}
            <section class="card mt-6" x-data="{ copied: false }">
                <div class="card-head py-3">
                    <h2 class="card-title text-sm"><i class="bi bi-quote text-gold-600"></i> รูปแบบการอ้างอิง (APA)</h2>
                    <button type="button" class="btn btn-ghost btn-sm"
                        @click="navigator.clipboard.writeText($refs.cite.innerText); copied = true; setTimeout(() => copied = false, 2000)">
                        <i class="bi" :class="copied ? 'bi-check2' : 'bi-clipboard'"></i> <span x-text="copied ? 'คัดลอกแล้ว' : 'คัดลอก'"></span>
                    </button>
                </div>
                <p x-ref="cite" class="p-5 text-sm leading-7 text-ink-700">
                    {{ $ebook->author ?: 'ไม่ปรากฏผู้แต่ง' }}. ({{ $ebook->publish_year ?: 'ม.ป.ป.' }}). <i>{{ $ebook->title }}</i>@if ($ebook->edition) ({{ $ebook->edition }})@endif. {{ $ebook->publisher ?: 'ม.ป.ท.' }}.
                </p>
            </section>
        </div>
    </div>

    {{-- ===== เล่มอื่นในหมวดเดียวกัน ===== --}}
    @if ($relatedBooks->isNotEmpty())
        <section class="mt-14">
            <h2 class="section-title mb-4">หนังสือในหมวดเดียวกัน</h2>
            <div class="grid grid-cols-2 gap-x-5 gap-y-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($relatedBooks as $book)
                    <x-book-card :ebook="$book" />
                @endforeach
            </div>
        </section>
    @endif

@endsection
