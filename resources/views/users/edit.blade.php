@extends('layouts.app')

@section('title', 'แก้ไขผู้ใช้')

@section('content')

<div class="max-w-3xl mx-auto px-5 py-8">

    <div class="bg-white rounded-2xl shadow-lg p-8">

        <h1 class="text-2xl font-bold text-gray-800 mb-6">
            ✏️ แก้ไขข้อมูลผู้ใช้
        </h1>

        <form method="POST" action="{{ route('users.update', $user) }}">

            @csrf
            @method('PUT')

            {{-- ชื่อ --}}
            <div class="mb-5">
                <label class="block font-semibold mb-2">
                    ชื่อ
                </label>

                <input
                    type="text"
                    value="{{ $user->name }}"
                    class="w-full rounded-lg border-gray-300 bg-gray-100"
                    disabled>
            </div>

            {{-- Email --}}
            <div class="mb-5">
                <label class="block font-semibold mb-2">
                    อีเมล
                </label>

                <input
                    type="email"
                    value="{{ $user->email }}"
                    class="w-full rounded-lg border-gray-300 bg-gray-100"
                    disabled>
            </div>

            {{-- การเข้าใช้งาน --}}
            <div class="mb-5 rounded-lg bg-ink-50 p-3 text-sm">
                @if ($user->email_verified_at)
                    <i class="bi bi-check2-circle text-emerald-600"></i> เข้าสู่ระบบครั้งแรกเมื่อ {{ \App\Support\ThaiDate::dateTime($user->email_verified_at) }}
                @else
                    <i class="bi bi-clock text-ink-400"></i> ยังไม่เคยเข้าสู่ระบบ
                @endif
            </div>

            {{-- Role --}}
            <div class="mb-5">

                <label class="block font-semibold mb-2">

                    บทบาท

                </label>

                <select
                    name="role"
                    class="w-full rounded-lg border-gray-300">

                    <option value="student" @selected($user->role=='student')>นักศึกษา</option>

                    <option value="teacher" @selected($user->role=='teacher')>อาจารย์</option>

                    <option value="staff" @selected($user->role=='staff')>บุคลากร</option>

                    <option value="alumni" @selected($user->role=='alumni')>ศิษย์เก่า</option>

                    <option value="admin" @selected($user->role=='admin')>ผู้ดูแลระบบ (แอดมิน)</option>

                </select>

            </div>

            {{-- Status --}}
            <div class="mb-6">

                <label class="block font-semibold mb-2">

                    สถานะ

                </label>

                <select
                    name="status"
                    class="w-full rounded-lg border-gray-300">

<option value="1" {{ $user->status == 1 ? 'selected' : '' }}>
    ใช้งาน
</option>

<option value="0" {{ $user->status == 0 ? 'selected' : '' }}>
    ระงับ
</option>

                </select>

            </div>

            <div class="flex justify-between">

                <a
                    href="{{ route('users.index') }}"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded-lg">

                    ย้อนกลับ

                </a>

                <button
                    class="bg-ink-800 hover:bg-ink-900 text-white px-6 py-2 rounded-lg">

                    💾 บันทึก

                </button>

            </div>

        </form>

    </div>

</div>

@endsection