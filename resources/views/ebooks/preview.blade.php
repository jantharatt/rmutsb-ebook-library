@extends('layouts.app')

@section('title', 'ตัวอย่าง: '.$ebook->title)

@section('content')

    <div class="page-head">
        <div class="min-w-0">
            <p class="page-eyebrow">ตัวอย่าง 5 หน้าแรก</p>
            <h1 class="page-title line-clamp-2">{{ $ebook->title }}</h1>
            <p class="page-sub">{{ $ebook->author }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('ebooks.show', $ebook) }}" class="btn btn-outline"><i class="bi bi-arrow-left"></i> กลับ</a>
            <a href="{{ route('ebooks.show', $ebook) }}#borrow" class="btn btn-gold"><i class="bi bi-bookmark-plus"></i> ยืมเพื่ออ่านเต็มเล่ม</a>
        </div>
    </div>

    <div class="card overflow-hidden">
        <iframe src="{{ route('ebooks.sample', $ebook) }}#toolbar=0&navpanes=0&view=FitH"
            title="ตัวอย่าง {{ $ebook->title }}" class="block h-[78vh] w-full bg-ink-50" loading="lazy"></iframe>
    </div>

    <div class="alert alert-info mt-4">
        <i class="bi bi-info-circle mt-0.5"></i>
        <div>ตัวอย่างแสดงเฉพาะ 5 หน้าแรก หากต้องการอ่านฉบับเต็ม กรุณายืม Ebook เล่มนี้ (ไม่มีค่าใช้จ่าย)</div>
    </div>

@endsection
