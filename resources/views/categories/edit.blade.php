@extends('layouts.app')

@section('title', 'แก้ไขหมวดหมู่')

@section('content')

<div class="px-5 py-4">


    {{-- Header --}}
    <div class="flex justify-between items-center mb-4">


        <div>

            <h1 class="text-lg font-bold text-gray-800">
                ✏️ แก้ไขหมวดหมู่
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





    <form action="{{ route('categories.update',$category) }}"
          method="POST">


        @csrf
        @method('PUT')



        <div class="bg-white rounded-xl shadow-sm border">



            {{-- Card Header --}}
            <div class="px-5 py-3 border-b">


                <h2 class="text-sm font-semibold text-gray-700">

                    ข้อมูลหมวดหมู่

                </h2>


            </div>





            <div class="p-5">



                {{-- Code --}}
                <div class="mb-4">


                    <label class="text-sm text-gray-700">

                        รหัสหมวด

                    </label>


                    <input

                        type="text"

                        name="code"

                        value="{{ old('code',$category->code) }}"

                        required


                        class="mt-1 w-full h-9
                               text-sm rounded-lg
                               border-gray-300">


                </div>





                {{-- Name --}}
                <div class="mb-4">


                    <label class="text-sm text-gray-700">

                        ชื่อหมวด

                    </label>


                    <input

                        type="text"

                        name="name"

                        value="{{ old('name',$category->name) }}"

                        required


                        class="mt-1 w-full h-9
                               text-sm rounded-lg
                               border-gray-300">


                </div>





                {{-- Description --}}
                <div class="mb-4">


                    <label class="text-sm text-gray-700">

                        รายละเอียด

                    </label>


                    <textarea

                        name="description"

                        rows="3"


                        class="mt-1 w-full
                               text-sm rounded-lg
                               border-gray-300">{{ old('description',$category->description) }}</textarea>


                </div>





                {{-- Parent --}}
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

                            เป็นหมวดหลัก

                        </option>




                        @foreach($parents as $parent)



                        <option

                            value="{{ $parent->id }}"

                            {{ old('parent_id',$category->parent_id)==$parent->id ? 'selected':'' }}>


                            {{ $parent->code }}

                            -

                            {{ $parent->name }}


                        </option>



                        @endforeach



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



                    💾 บันทึก



                </button>



            </div>




        </div>



    </form>



</div>


@endsection