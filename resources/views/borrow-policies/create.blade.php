@extends('layouts.app')

@section('title', 'เพิ่มนโยบายการยืม')

@section('content')

<div class="px-5 py-4">


    {{-- Header --}}
    <div class="flex justify-between items-center mb-4">


        <div>

            <h1 class="text-lg font-bold text-gray-800">
                📖 เพิ่มนโยบายการยืม
            </h1>


            <p class="text-xs text-gray-500">
                กำหนดสิทธิ์การยืมตามประเภทผู้ใช้งาน
            </p>


        </div>


    </div>





    {{-- Error --}}
    @if ($errors->any())


    <div class="bg-red-50 border border-red-200
                text-red-700 text-sm
                rounded-lg p-3 mb-4">


        <ul class="list-disc pl-4">


            @foreach($errors->all() as $error)


                <li>{{ $error }}</li>


            @endforeach


        </ul>


    </div>


    @endif






    <form action="{{ route('borrow-policies.store') }}"
          method="POST">


        @csrf



        <div class="bg-white rounded-xl shadow-sm border">



            {{-- Header Card --}}
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

                        required


                        class="mt-1 w-full h-9
                               text-sm rounded-lg
                               border-gray-300">



                        <option value="student">
                            นักศึกษา
                        </option>


                        <option value="teacher">
                            อาจารย์
                        </option>


                        <option value="staff">
                            เจ้าหน้าที่
                        </option>


                        <option value="alumni">
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

                        required


                        class="mt-1 w-full h-9
                               text-sm rounded-lg
                               border-gray-300">



                </div>






                {{-- Borrow Days --}}
                <div>


                    <label class="text-sm text-gray-700">

                        จำนวนวันยืม

                    </label>



                    <input

                        type="number"

                        name="borrow_days"

                        required


                        class="mt-1 w-full h-9
                               text-sm rounded-lg
                               border-gray-300">



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



                    💾 บันทึก



                </button>



            </div>



        </div>



    </form>



</div>


@endsection