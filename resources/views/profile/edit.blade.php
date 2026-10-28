@extends('layouts.app')

@section('title', 'โปรไฟล์')

@section('content')

<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-800">
            โปรไฟล์ผู้ใช้งาน
        </h1>

        <p class="text-sm text-slate-500">
            จัดการข้อมูลส่วนตัว เปลี่ยนรหัสผ่าน และลบบัญชีผู้ใช้งาน
        </p>
    </div>

<div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

    <h2 class="text-lg font-semibold mb-6">
        ข้อมูลส่วนตัว
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

        <div>
            <label class="text-sm text-gray-500">
                ชื่อ
            </label>

            <div class="mt-1 p-3 rounded-lg bg-gray-100">
                {{ auth()->user()->name }}
            </div>
        </div>

        <div>
            <label class="text-sm text-gray-500">
                อีเมล
            </label>

            <div class="mt-1 p-3 rounded-lg bg-gray-100">
                {{ auth()->user()->email }}
            </div>
        </div>

        <div>
            <label class="text-sm text-gray-500">
                คณะ
            </label>

            <div class="mt-1 p-3 rounded-lg bg-gray-100">
                {{ auth()->user()->faculty }}
            </div>
        </div>

        <div>
            <label class="text-sm text-gray-500">
                สาขา
            </label>

            <div class="mt-1 p-3 rounded-lg bg-gray-100">
                {{ auth()->user()->department }}
            </div>
        </div>

        <div>
            <label class="text-sm text-gray-500">
                สิทธิ์ผู้ใช้งาน
            </label>

            <div class="mt-1 p-3 rounded-lg bg-gray-100 capitalize">
                {{ auth()->user()->role }}
            </div>
        </div>

    </div>

</div>
    {{-- เปลี่ยนรหัสผ่าน --}}
    {{-- <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-slate-700">
            เปลี่ยนรหัสผ่าน
        </h2>

        @include('profile.partials.update-password-form')
    </div> --}}

    {{-- ลบบัญชี --}}
    {{-- <div class="rounded-xl border border-red-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-semibold text-red-600">
            ลบบัญชีผู้ใช้งาน
        </h2>

        @include('profile.partials.delete-user-form')
    </div> --}}

</div>

@endsection