@extends('layouts.app')

@section('title', 'อ่าน: '.$ebook->title)

@section('content')

    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('ebooks.show', $ebook) }}" class="text-sm text-ink-500 hover:text-ink-900"><i class="bi bi-arrow-left"></i> รายละเอียดหนังสือ</a>
            <h1 class="mt-1 line-clamp-1 font-display text-xl font-bold">{{ $ebook->title }}</h1>
        </div>
        <div class="flex items-center gap-2 text-sm">
            @if ($activeBorrow)
                <span class="badge badge-green"><i class="bi bi-clock"></i> อ่านได้ถึง {{ \App\Support\ThaiDate::short($activeBorrow->due_date) }}</span>
            @elseif (auth()->user()->isAdmin())
                <span class="badge badge-ink"><i class="bi bi-shield-check"></i> สิทธิ์ผู้ดูแลระบบ</span>
            @endif
        </div>
    </div>

    @include('ebooks.partials.book-reader', ['src' => route('ebooks.stream', $ebook)])

    <p class="mt-3 text-center text-xs text-ink-400">
        เอกสารนี้สงวนลิขสิทธิ์ ใช้เพื่อการศึกษาภายในมหาวิทยาลัยเท่านั้น ห้ามเผยแพร่หรือทำซ้ำ
    </p>

@endsection
