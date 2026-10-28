@extends('layouts.app')

@section('title', 'นโยบายการยืม')

@section('content')

    <div class="flex items-center justify-between mb-6">

        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                นโยบายการยืม Ebook
            </h1>

            <p class="text-sm text-slate-500">
                กำหนดสิทธิ์การยืมตามประเภทผู้ใช้งาน
            </p>
        </div>

        <a href="{{ route('borrow-policies.create') }}"
            class="rounded-lg bg-ink-800 px-4 py-2 text-sm font-medium text-white hover:bg-ink-900 transition">

            เพิ่มนโยบาย

        </a>

    </div>


    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b bg-ink-800 px-6 py-4">

            <h2 class="font-semibold text-white">
                ตารางนโยบายการยืม
            </h2>

        </div>


        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead class="bg-slate-50">

                    <tr>

                        <th class="px-6 py-3 text-left text-sm font-semibold text-slate-600">
                            ประเภทผู้ใช้
                        </th>

                        <th class="px-6 py-3 text-center text-sm font-semibold text-slate-600">
                            จำนวนเล่ม
                        </th>

                        <th class="px-6 py-3 text-center text-sm font-semibold text-slate-600">
                            จำนวนวัน
                        </th>

                        <th class="px-6 py-3 text-center text-sm font-semibold text-slate-600 w-40">
                            จัดการ
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">

                    @forelse($policies as $policy)
                        @php

                            $color = 'bg-purple-100 text-purple-700';

                            if ($policy->role == 'admin') {
                                $color = 'bg-blue-100 text-blue-700';
                            } elseif ($policy->role == 'teacher') {
                                $color = 'bg-green-100 text-green-700';
                            } elseif ($policy->role == 'staff') {
                                $color = 'bg-yellow-100 text-yellow-700';
                            } elseif ($policy->role == 'alumni') {
                                $color = 'bg-pink-100 text-pink-700';
                            }

                            $roleName = match ($policy->role) {
                                'admin' => 'ผู้ดูแลระบบ',
                                'teacher' => 'อาจารย์',
                                'staff' => 'เจ้าหน้าที่',
                                'student' => 'นักศึกษา',
                                'alumni' => 'ศิษย์เก่า',
                                default => $policy->role,
                            };

                        @endphp

                        <tr class="hover:bg-slate-50">

                            <td class="px-6 py-4">

                                <span class="rounded-full {{ $color }} px-3 py-1 text-sm font-semibold">

                                    {{ $roleName }}

                                </span>

                            </td>

                            <td class="px-6 py-4 text-center">

                                <span class="inline-block min-w-[56px] rounded-lg bg-slate-100 px-3 py-1 font-semibold">

                                    {{ $policy->max_books }}

                                </span>

                            </td>

                            <td class="px-6 py-4 text-center">

                                <span class="inline-block min-w-[56px] rounded-lg bg-slate-100 px-3 py-1 font-semibold">

                                    {{ $policy->borrow_days }}

                                </span>

                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">

                                    <a href="{{ route('borrow-policies.edit', $policy) }}"
                                        class="inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100">

                                        <i class="fas fa-pen mr-1"></i>
                                        แก้ไข

                                    </a>

                                    <form action="{{ route('borrow-policies.destroy', $policy) }}" method="POST">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" onclick="return confirm('ยืนยันการลบ ?')"
                                            class="inline-flex items-center rounded-md border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">

                                            <i class="fas fa-trash-alt mr-1"></i>
                                            ลบ

                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="4" class="px-6 py-8 text-center text-slate-500">

                                ยังไม่มีข้อมูลนโยบายการยืม

                            </td>

                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

@endsection
