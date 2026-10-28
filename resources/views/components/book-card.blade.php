@props(['ebook', 'badge' => null])

<a href="{{ route('ebooks.show', $ebook) }}" {{ $attributes->merge(['class' => 'book-card']) }}>
    <x-book-cover :ebook="$ebook">
        @if ($badge)
            <span class="absolute left-2 top-2 rounded-md bg-ink-950/80 px-2 py-0.5 text-[11px] font-semibold text-gold-300 backdrop-blur">
                {{ $badge }}
            </span>
        @endif
        @if ((int) $ebook->status !== 1)
            <span class="absolute right-2 top-2 rounded-md bg-red-600 px-2 py-0.5 text-[11px] font-semibold text-white">ปิดบริการ</span>
        @endif
    </x-book-cover>

    <div class="book-card-title" title="{{ $ebook->title }}">{{ $ebook->title }}</div>
    <div class="book-card-meta">{{ $ebook->author ?: '—' }}@if ($ebook->publish_year) · {{ $ebook->publish_year }}@endif</div>
</a>
