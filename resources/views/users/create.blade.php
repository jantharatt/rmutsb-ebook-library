@extends('layouts.app')

@section('title', 'เพิ่มผู้ใช้')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="page-head">
            <div>
                <p class="page-eyebrow">User management</p>
                <h1 class="page-title">เพิ่มผู้ใช้</h1>
                <p class="page-sub">ผู้ใช้ที่เข้าสู่ระบบด้วยอีเมลมหาวิทยาลัยจะถูกสร้างอัตโนมัติ ใช้หน้านี้เมื่อต้องการเพิ่มไว้ล่วงหน้า</p>
            </div>
        </div>

        <form method="POST" action="{{ route('users.store') }}" class="card space-y-5 p-6"
            x-data="{ email: '{{ old('email') }}', role: '{{ old('role', 'student') }}' }"
            x-init="$watch('email', v => { if (/^\d/.test(v)) role = 'student'; else if (/^[a-z]/i.test(v)) role = role === 'student' ? 'teacher' : role })">
            @csrf
            <div>
                <label class="label" for="email">อีเมลมหาวิทยาลัย</label>
                <input id="email" type="email" name="email" x-model="email" required class="input" placeholder="6612345678-st@rmutsb.ac.th หรือ ชื่อ.นามสกุล@rmutsb.ac.th">
            </div>
            <div>
                <label class="label" for="name">ชื่อ–นามสกุล</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required class="input">
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="role">สิทธิ์ผู้ใช้</label>
                    <select id="role" name="role" x-model="role" class="select">
                        <option value="student">นักศึกษา</option>
                        <option value="alumni">ศิษย์เก่า</option>
                        <option value="teacher">อาจารย์</option>
                        <option value="staff">บุคลากร</option>
                        <option value="admin">ผู้ดูแลระบบ</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="password">รหัสผ่านเริ่มต้น</label>
                    <input id="password" type="password" name="password" required minlength="8" class="input" autocomplete="new-password">
                </div>
                <div>
                    <label class="label" for="faculty">คณะ / หน่วยงาน</label>
                    <input id="faculty" type="text" name="faculty" value="{{ old('faculty') }}" class="input">
                </div>
                <div>
                    <label class="label" for="department">สาขา / ฝ่าย</label>
                    <input id="department" type="text" name="department" value="{{ old('department') }}" class="input">
                </div>
            </div>
            <div class="flex justify-between border-t border-ink-100 pt-5">
                <a href="{{ route('users.index') }}" class="btn btn-outline">ยกเลิก</a>
                <button class="btn btn-primary"><i class="bi bi-check2"></i> บันทึก</button>
            </div>
        </form>
    </div>
@endsection
