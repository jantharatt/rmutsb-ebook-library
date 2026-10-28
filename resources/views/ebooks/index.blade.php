@extends('layouts.app')

@section('title', 'ค้นหา Ebook')

@php
    $adminMode = auth()->user()->isAdmin() && session('view_mode') !== 'user';
    $selectedCategory = $mainCategories->firstWhere('id', (int) $category);
    $typeLabels = ['' => 'ทุกช่อง', 'title' => 'ชื่อเรื่อง', 'author' => 'ผู้แต่ง', 'subject' => 'หัวเรื่อง', 'isbn' => 'ISBN', 'call' => 'เลขเรียกหนังสือ'];
    $sortLabels = ['latest' => 'เพิ่มล่าสุด', 'popular' => 'ยอดนิยม', 'title' => 'ชื่อเรื่อง ก–ฮ', 'year' => 'ปีพิมพ์ใหม่–เก่า'];
@endphp

@section('content')

    <div class="page-head">
        <div>
            {{-- <p class="page-eyebrow">{{ $adminMode ? 'Collection management' : 'Search the collection' }}</p> --}}
            <h1 class="page-title">{{ $adminMode ? 'จัดการ Ebook' : 'ค้นหา Ebook' }}</h1>
            <p class="page-sub">ค้นหาจากชื่อเรื่อง ผู้แต่ง หัวเรื่อง คำสำคัญ ISBN หรือเลขเรียกหนังสือ</p>
        </div>

        @if ($adminMode)
            <div class="flex flex-wrap items-center gap-2">
                <span class="badge badge-ink">ทั้งหมด {{ number_format($totalEbooks) }}</span>
                <span class="badge badge-green">เปิดบริการ {{ number_format($activeEbooks) }}</span>
                <span class="badge badge-red">ปิดบริการ {{ number_format($inactiveEbooks) }}</span>
                <a href="{{ route('ebooks.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> เพิ่ม Ebook</a>
            </div>
        @endif
    </div>

    {{-- ===== แถบค้นหา ===== --}}
    <form method="GET" action="{{ route('ebooks.index') }}" class="card mb-6 p-3" role="search">
        @if ($category)
            <input type="hidden" name="category" value="{{ $category }}">
        @endif
        <div class="grid gap-2 md:grid-cols-[170px_1fr_170px_auto]">
            <select name="type" class="select" aria-label="ค้นหาจาก">
                @foreach ($typeLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($type ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-300"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="พิมพ์คำค้น..." class="input pl-10" aria-label="คำค้น">
            </div>
            <select name="sort" class="select" aria-label="เรียงลำดับ" onchange="this.form.submit()">
                @foreach ($sortLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($sort ?? 'latest') === $value)>เรียง: {{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary"><i class="bi bi-search"></i> ค้นหา</button>
        </div>
    </form>

    <div class="grid gap-6 lg:grid-cols-[250px_1fr]">

        {{-- ===== หมวดหมู่ ===== --}}
        <aside>
            <div class="card sticky top-36 overflow-hidden">
                <div class="card-head py-3">
                    <h2 class="card-title text-sm"><i class="bi bi-diagram-3 text-gold-600"></i> หมวดหมู่</h2>
                </div>
                <nav class="max-h-[60vh] overflow-y-auto p-2 text-sm">
                    <a href="{{ route('ebooks.index', array_filter(['search' => $search, 'type' => $type, 'sort' => $sort])) }}"
                        class="flex items-center justify-between rounded-lg px-3 py-2 {{ ! $category ? 'bg-ink-900 font-semibold text-white' : 'hover:bg-ink-50' }}">
                        ทุกหมวดหมู่
                    </a>
                    @foreach ($mainCategories as $main)
                        <a href="{{ route('ebooks.index', array_filter(['category' => $main->id, 'search' => $search, 'type' => $type, 'sort' => $sort])) }}"
                            class="flex items-center gap-2 rounded-lg px-3 py-2 {{ (int) $category === $main->id ? 'bg-ink-900 font-semibold text-white' : 'text-ink-700 hover:bg-ink-50' }}">
                            <span class="w-8 shrink-0 text-xs font-bold {{ (int) $category === $main->id ? 'text-gold-300' : 'text-gold-600' }}">{{ $main->code }}</span>
                            <span class="truncate" title="{{ $main->name }}">{{ $main->name }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        {{-- ===== ผลลัพธ์ ===== --}}
        <section>
            <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-ink-500">
                <span>พบ <b class="text-ink-900">{{ number_format($ebooks->total()) }}</b> รายการ</span>
                @if ($search)
                    <span class="badge badge-blue">“{{ $search }}” <a href="{{ route('ebooks.index', array_filter(['category' => $category, 'sort' => $sort])) }}" class="ml-1" aria-label="ล้างคำค้น">×</a></span>
                @endif
                @if ($selectedCategory)
                    <span class="badge badge-gold">{{ $selectedCategory->code }} {{ $selectedCategory->name }} <a href="{{ route('ebooks.index', array_filter(['search' => $search, 'type' => $type, 'sort' => $sort])) }}" class="ml-1" aria-label="ล้างหมวด">×</a></span>
                @endif
            </div>

            @if ($ebooks->isEmpty())
                <div class="card px-6 py-14 text-center">
                    <i class="bi bi-journal-x text-5xl text-ink-200"></i>
                    <p class="mt-3 font-semibold text-ink-800">ไม่พบ Ebook ที่ตรงกับเงื่อนไข</p>
                    <p class="mt-1 text-sm text-ink-500">ลองใช้คำค้นที่สั้นลง เปลี่ยนช่องที่ค้นหา หรือเลือก “ทุกหมวดหมู่”</p>
                    <button type="button" data-open-chat="{{ $search }}" class="btn btn-outline mt-5"><i class="bi bi-stars text-gold-600"></i> ให้ผู้ช่วยห้องสมุดช่วยหา</button>
                </div>
            @else
                <div class="grid grid-cols-2 gap-x-5 gap-y-8 sm:grid-cols-3 xl:grid-cols-4">
                    @foreach ($ebooks as $ebook)
                        <div class="flex flex-col">
                            <x-book-card :ebook="$ebook" />
                            @if ($ebook->category)
                                <div class="mt-1.5">
                                    <span class="badge badge-ink max-w-full truncate" title="{{ $ebook->category->name }}">{{ $ebook->category->name }}</span>
                                </div>
                            @endif

                            @if ($adminMode)
                                <div class="mt-auto flex gap-1.5 pt-3">
                                    <a href="{{ route('ebooks.edit', $ebook) }}" class="btn btn-outline btn-sm flex-1"><i class="bi bi-pencil"></i> แก้ไข</a>
                                    <form action="{{ route('ebooks.destroy', $ebook) }}" method="POST"
                                        data-confirm="ไฟล์ PDF ปก และข้อมูลเล่มนี้จะถูกลบถาวร"
                                        data-confirm-title="ลบ “{{ \Illuminate\Support\Str::limit($ebook->title, 40) }}” ?"
                                        data-confirm-button="ลบ Ebook" data-confirm-danger="1" data-confirm-icon="warning">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm border border-red-200 bg-white text-red-600 hover:bg-red-50" aria-label="ลบ"><i class="bi bi-trash3"></i></button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $ebooks->links() }}
                </div>
            @endif
        </section>
    </div>

@endsection
