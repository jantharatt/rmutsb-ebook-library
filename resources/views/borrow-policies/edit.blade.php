@extends('layouts.app')

@section('title','แก้ไขนโยบายการยืม')

@section('content')

<div class="px-5 py-4">


    {{-- Header --}}
    <div class="flex justify-between items-center mb-4">


        <div>

            <h1 class="text-lg font-bold text-gray-800">
                ✏️ แก้ไขนโยบายการยืม
            </h1>


            <p class="text-xs text-gray-500">
                กำหนดสิทธิ์การยืมตามประเภทผู้ใช้งาน
            </p>


        </div>



        <a href="{{ route('borrow-policies.index') }}"
           class="bg-gray-600 hover:bg-gray-700
                  text-white text-sm
                  px-3 py-1.5
                  rounded-lg">


            ← กลับ


        </a>


    </div>






    <form action="{{ route('borrow-policies.update',$borrowPolicy) }}"
          method="POST">


        @csrf
        @method('PUT')



        <div class="bg-white rounded-xl shadow-sm border">



            {{-- Card Header --}}
            <div class="px-5 py-3 border-b">


                <h2 class="text-sm font-semibold text-gray-700">

                    ข้อมูลนโยบายการยืม

                </h2>


            </div>






            <div class="p-5">



                {{-- Role --}}
                <div class="mb-4">


                    <label class="text-sm text-gray-700">

                        ประเภทผู้ใช้

                    </label>



                    <select

                        name="role"


                        class="mt-1 w-full h-9
                               text-sm rounded-lg
                               border-gray-300">



                        <option value="student"
                        {{ old('role',$borrowPolicy->role)=='student'?'selected':'' }}>

                            นักศึกษา

                        </option>


                        <option value="teacher"
                        {{ old('role',$borrowPolicy->role)=='teacher'?'selected':'' }}>

                            อาจารย์

                        </option>


                        <option value="staff"
                        {{ old('role',$borrowPolicy->role)=='staff'?'selected':'' }}>

                            เจ้าหน้าที่

                        </option>


                        <option value="alumni"
                        {{ old('role',$borrowPolicy->role)=='alumni'?'selected':'' }}>

                            ศิษย์เก่า

                        </option>



                    </select>


                </div>







                {{-- Max Books --}}
                <div class="mb-4">


                    <label class="text-sm text-gray-700">

                        จำนวนเล่มสูงสุด

                    </label>



                    <input

                        type="number"

                        name="max_books"

                        value="{{ old('max_books',$borrowPolicy->max_books) }}"


                        class="mt-1 w-full h-9
                               text-sm rounded-lg
                               border-gray-300">



                </div>







                {{-- Borrow Days --}}
                <div class="mb-4">


                    <label class="text-sm text-gray-700">

                        จำนวนวันยืม

                    </label>



                    <input

                        type="number"

                        name="borrow_days"

                        value="{{ old('borrow_days',$borrowPolicy->borrow_days) }}"


                        class="mt-1 w-full h-9
                               text-sm rounded-lg
                               border-gray-300">



                </div>







                {{-- Status --}}
                <div>


                    <label class="text-sm text-gray-700">

                        สถานะ

                    </label>



                    <select

                        name="active"


                        class="mt-1 w-full h-9
                               text-sm rounded-lg
                               border-gray-300">



                        <option value="1"
                        {{ $borrowPolicy->active ? 'selected':'' }}>

                            ใช้งาน

                        </option>



                        <option value="0"
                        {{ !$borrowPolicy->active ? 'selected':'' }}>

                            ปิดใช้งาน

                        </option>



                    </select>


                </div>



            </div>








            {{-- Footer --}}
            <div class="px-5 py-3 border-t text-right">



                <button

                    type="submit"


                    class="bg-ink-800 hover:bg-ink-900
                           text-white text-sm
                           px-4 py-2
                           rounded-lg">



                    💾 บันทึกการแก้ไข



                </button>



            </div>




        </div>




    </form>




</div>


@endsection