@extends('layouts.app')

@section('title', 'เพิ่มหมวดหมู่')

@section('content')

<div class="px-5 py-4">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-4">

        <div>
            <h1 class="text-lg font-bold text-gray-800">
                📚 เพิ่มหมวดหมู่หนังสือ
            </h1>

            <p class="text-xs text-gray-500">
                Dewey Decimal Classification
            </p>
        </div>


        <a href="{{ route('categories.index') }}"
           class="bg-gray-600 hover:bg-gray-700
                  text-white text-sm
                  px-3 py-1.5
                  rounded-lg">

            ← กลับ

        </a>

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



    <form action="{{ route('categories.store') }}"
          method="POST">

        @csrf


        <div class="bg-white rounded-xl shadow-sm border">


            <div class="px-5 py-3 border-b">

                <h2 class="text-sm font-semibold text-gray-700">
                    ข้อมูลหมวดหมู่
                </h2>

            </div>



            <div class="p-5">


                {{-- Code / Name --}}
                <div class="grid grid-cols-12 gap-4">


                    <div class="col-span-4">

                        <label class="text-sm text-gray-700">
                            รหัสหมวด
                        </label>

                        <input
                            type="text"
                            name="code"
                            maxlength="3"
                            value="{{ old('code') }}"
                            required

                            class="mt-1 w-full h-9
                                   text-sm rounded-lg
                                   border-gray-300">

                    </div>



                    <div class="col-span-8">

                        <label class="text-sm text-gray-700">
                            ชื่อหมวดหมู่
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required

                            class="mt-1 w-full h-9
                                   text-sm rounded-lg
                                   border-gray-300">

                    </div>


                </div>



                {{-- Parent / Status --}}
                <div class="grid grid-cols-2 gap-4 mt-4">


                    <div>

                        <label class="text-sm text-gray-700">
                            หมวดหลัก
                        </label>


                        <select
                            name="parent_id"

                            class="mt-1 w-full h-9
                                   text-sm rounded-lg
                                   border-gray-300">


                            <option value="">
                                -- หมวดหลัก --
                            </option>


                            @foreach($parents as $parent)

                            <option
                                value="{{ $parent->id }}"
                                {{ old('parent_id')==$parent->id ? 'selected':'' }}>

                                {{ $parent->code }} -
                                {{ $parent->name }}

                            </option>

                            @endforeach


                        </select>

                    </div>



                    <div>

                        <label class="text-sm text-gray-700">
                            สถานะ
                        </label>


                        <select

                            name="status"

                            class="mt-1 w-full h-9
                                   text-sm rounded-lg
                                   border-gray-300">


                            <option value="1">
                                ใช้งาน
                            </option>


                            <option value="0">
                                ปิดใช้งาน
                            </option>


                        </select>

                    </div>


                </div>



                {{-- Description --}}
                <div class="mt-4">

                    <label class="text-sm text-gray-700">
                        รายละเอียด
                    </label>


                    <textarea

                        name="description"

                        rows="3"

                        class="mt-1 w-full
                               text-sm rounded-lg
                               border-gray-300">{{ old('description') }}</textarea>


                </div>


            </div>




            <div class="px-5 py-3 border-t text-right">


                <button

                    type="submit"

                    class="bg-ink-800 hover:bg-ink-900
                           text-white text-sm
                           px-4 py-2
                           rounded-lg">


                    💾 บันทึกหมวดหมู่

                </button>


            </div>


        </div>


    </form>


</div>

@endsection