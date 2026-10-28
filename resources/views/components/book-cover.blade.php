@props(['ebook', 'eager' => false])

{{--
    ปกหนังสือ: รูปปกที่อัปโหลด → ปกจากหน้าแรก PDF (สร้างอัตโนมัติ) → ปกจำลองตามอัตลักษณ์มหาวิทยาลัย
    ถ้าผู้ดูแลระบบเปิดหน้านี้และยังไม่มีปก เบราว์เซอร์จะสร้างปกจากหน้าแรกของ PDF ให้อัตโนมัติ
--}}
@php
    $coverUrl = $ebook->cover_url;
    $canDerive = auth()->check() && auth()->user()->isAdmin() && $ebook->file_path;
    $needCover = ! $coverUrl;
    $needPreview = ! $ebook->preview_file;
@endphp

<div {{ $attributes->merge(['class' => 'book-cover']) }} aria-label="{{ $ebook->title }}"
    @if ($canDerive && $needCover)
        data-derive-url="{{ route('ebooks.derived', $ebook) }}"
        data-stream-url="{{ route('ebooks.stream', $ebook) }}"
        data-need-cover="1"
        data-need-preview="{{ $needPreview ? 1 : 0 }}"
    @endif
>
    @if ($coverUrl)
        <img src="{{ $coverUrl }}" alt="ปกหนังสือ {{ $ebook->title }}" @unless($eager) loading="lazy" @endunless decoding="async">
    @else
        <div class="book-cover-placeholder" aria-hidden="true">
            <div class="bc-title">{{ \Illuminate\Support\Str::limit($ebook->title, 80) }}</div>
            <div>
                <div class="bc-author">{{ $ebook->author }}</div>
                <div class="bc-mark mt-1">RUS E-LIBRARY</div>
            </div>
        </div>
    @endif

    {{ $slot }}
</div>
