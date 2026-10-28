@extends('layouts.app')

@section('title','เพิ่ม Ebook')

@section('content')

<div class="px-5 py-4">


{{-- HEADER --}}

<div class="mb-4">

    <h1 class="text-lg font-bold text-gray-800">
        📚 เพิ่ม Ebook
    </h1>

</div>


<form action="{{ route('ebooks.store') }}"
      method="POST"
      enctype="multipart/form-data">

@csrf




{{-- ========================= --}}
{{-- ข้อมูลบรรณานุกรม --}}
{{-- ========================= --}}


<div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-3">


    <div class="px-3 py-2 border-b bg-gray-50">

        <h3 class="text-xs font-semibold text-gray-700">
            📖 ข้อมูลบรรณานุกรม
        </h3>

    </div>



    <div class="p-3">


        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">



            {{-- ชื่อเรื่อง --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    ชื่อเรื่อง
                    <span class="text-red-500">*</span>
                </label>


                <input type="text"
                name="title"
                required
                class="w-full h-8 text-xs rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">


            </div>





            {{-- ชื่อผู้แต่ง --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    ชื่อผู้แต่ง
                    <span class="text-red-500">*</span>
                </label>


                <input type="text"
                name="author"
                required
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>






            {{-- ISBN --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    ISBN
                </label>


                <input type="text"
                name="isbn"
                maxlength="13"
                minlength="10"
                pattern="[0-9]{10,13}"
                placeholder="10-13 หลัก"
                required
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>






            {{-- ครั้งที่พิมพ์ --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    ครั้งที่พิมพ์
                </label>


                <input type="text"
                name="edition"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>






            {{-- จำนวนหน้า --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    จำนวนหน้า
                </label>


                <input type="number"
                name="total_pages"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>







            {{-- ศูนย์พื้นที่ --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    ศูนย์พื้นที่
                </label>


                <select name="campus"
                class="w-full h-8 text-xs rounded-md border-gray-300">


                    <option value="">
                        เลือกศูนย์พื้นที่
                    </option>


                    <option value="นนทบุรี">
                        นนทบุรี
                    </option>


                    <option value="วาสุกรี">
                        วาสุกรี
                    </option>


                    <option value="สุพรรณบุรี">
                        สุพรรณบุรี
                    </option>


                    <option value="หันตรา">
                        หันตรา
                    </option>


                </select>


            </div>



        </div>


    </div>


</div>







{{-- ========================= --}}
{{-- CATALOGING --}}
{{-- ========================= --}}



<div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-3">


    <div class="px-3 py-2 border-b bg-gray-50">


        <h3 class="text-xs font-semibold text-gray-700">
            📚 ข้อมูลบรรณานุกรม (Cataloging)
        </h3>


    </div>




    <div class="p-3">


        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">





            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    พิมพลักษณ์
                </label>


                <input type="text"
                name="imprint"
                placeholder="กรุงเทพฯ : สำนักพิมพ์, 2025"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>






            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    บรรณลักษณ์
                </label>


                <input type="text"
                name="physical_description"
                placeholder="250 หน้า : ภาพประกอบ"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>






            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    ชื่อชุด
                </label>


                <input type="text"
                name="series_name"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>






            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    หัวเรื่อง
                </label>


                <input type="text"
                name="subject_heading"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>



        </div>


    </div>


</div>
{{-- ========================= --}}
{{-- CLASSIFICATION --}}
{{-- ========================= --}}


<div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-3">


    <div class="px-3 py-2 border-b bg-gray-50">


        <h3 class="text-xs font-semibold text-gray-700">
            🏷 การจัดหมวดหมู่
        </h3>


    </div>




    <div class="p-3">


        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">





            {{-- เลขเรียกหนังสือ --}}

            <div>


                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    เลขเรียกหนังสือ
                </label>


                <input type="text"
                name="call_number"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>






            {{-- เลขเรียก นว & รส --}}

            <div>


                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    เลขเรียก นว & รส
                </label>


                <input type="text"
                name="call_number_copy"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>







            {{-- รายการเพิ่มชื่อบุคคล --}}

            <div>


                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    รายการเพิ่มชื่อบุคคล
                </label>


                <input type="text"
                name="added_personal_name"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>







            {{-- รายการเพิ่มชื่อนิติบุคคล --}}

            <div>


                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    รายการเพิ่มชื่อนิติบุคคล
                </label>


                <input type="text"
                name="added_corporate_name"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>



        </div>





        {{-- หมวดหมู่ --}}


        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 mt-3">





            {{-- หมวดหลัก --}}

            <div>


                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    หมวดหลัก
                </label>


                <select 
                id="main_category"
                name="main_category_code"
                class="w-full h-8 text-xs rounded-md border-gray-300">


                    <option value="">
                        เลือกหมวดหลัก
                    </option>


                    @foreach ($mainCategories as $category)

                        <option value="{{ $category->id }}">

                            {{ $category->code }} -
                            {{ $category->name }}

                        </option>

                    @endforeach


                </select>


            </div>







            {{-- หมวดย่อย --}}

            <div>


                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    หมวดย่อย
                </label>


                <select 
                id="sub_category"
                name="category_id"
                required
                class="w-full h-8 text-xs rounded-md border-gray-300">


                    <option value="">
                        เลือกหมวดย่อย
                    </option>



                    @foreach ($subCategories as $category)


                        <option 
                        value="{{ $category->id }}"
                        data-parent="{{ $category->parent_id }}"
                        style="display:none">


                            {{ $category->code }} -
                            {{ $category->name }}


                        </option>


                    @endforeach



                </select>


            </div>





        </div>


    </div>


</div>
{{-- ========================= --}}
{{-- ADMIN / PROCUREMENT --}}
{{-- ========================= --}}


<div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-3">


    <div class="px-3 py-2 border-b bg-gray-50">


        <h3 class="text-xs font-semibold text-gray-700">
            ⚙ ข้อมูลระบบ / การจัดซื้อ
        </h3>


    </div>




    <div class="p-3">


        <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">



            {{-- ปีงบประมาณ --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    ปีงบประมาณที่จัดซื้อ
                </label>


                <input type="number"
                name="budget_year"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>







            {{-- เลขทะเบียน --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    เลขทะเบียน
                </label>


                <input type="text"
                name="registration_number"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>







            {{-- สถานที่เก็บ --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    สถานที่เก็บ (ศูนย์พื้นที่)
                </label>


                <input type="text"
                name="campus"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>







            {{-- คณะ --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    คณะ / สาขา
                </label>


                <input type="text"
                name="faculty"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>







            {{-- ผู้ลงรายการ --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    ผู้ลงรายการ
                </label>


                <input type="text"
                name="cataloguer"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>







            {{-- แหล่งที่มา --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    แหล่งที่มา
                </label>


                <input type="text"
                name="source"
                class="w-full h-8 text-xs rounded-md border-gray-300">


            </div>




        </div>


    </div>


</div>







{{-- ========================= --}}
{{-- FILE UPLOAD --}}
{{-- ========================= --}}



<div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-3">


    <div class="px-3 py-2 border-b bg-gray-50">


        <h3 class="text-xs font-semibold text-gray-700">
            📄 ไฟล์ Ebook
        </h3>


    </div>




    <div class="p-3">


        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">





            {{-- รูปปก --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    รูปปก
                </label>


                <input type="file"
                name="cover"
                accept="image/jpeg,image/png,image/webp"
                class="w-full h-8 text-xs rounded-md border-gray-300 file:bg-gray-100 file:border-0">


            </div>







            {{-- PDF --}}

            <div>

                <label class="block text-[11px] font-semibold text-gray-600 mb-1">
                    ไฟล์ PDF
                    <span class="text-red-500">*</span>
                </label>


                <input type="file"
                name="pdf_file"
                required
                accept="application/pdf"
                data-pdf-input
                class="w-full h-8 text-xs rounded-md border-gray-300 file:bg-gray-100 file:border-0">
            <div data-auto-cover-wrap class="hidden mt-2 flex items-center gap-3 rounded-lg border border-ink-100 bg-ink-50/60 p-2">
                <img data-auto-cover-preview alt="ปกจากหน้าแรกของ PDF" class="h-24 w-auto rounded border border-ink-100 bg-white">
                <p class="text-xs text-ink-600">ภาพหน้าแรกของ PDF<br><span class="text-ink-400">จะใช้เป็นปกอัตโนมัติ ถ้าไม่ได้อัปโหลดรูปปก</span></p>
            </div>
            <p data-auto-cover-status class="mt-1 text-[11px] text-ink-500">ไม่ต้องมีรูปปกก็ได้ ระบบจะใช้หน้าแรกของ PDF เป็นปกให้อัตโนมัติ</p>


            </div>




        </div>


    </div>


</div>







{{-- ========================= --}}
{{-- BUTTON --}}
{{-- ========================= --}}



<div class="flex justify-end mb-4">


    <button type="submit"
    class="px-4 py-2 text-xs font-semibold text-white bg-ink-800 rounded-md hover:bg-ink-900">


        💾 บันทึก Ebook


    </button>



</div>





</form>



</div>





{{-- ========================= --}}
{{-- SCRIPT CATEGORY --}}
{{-- ========================= --}}


<script>

document
.getElementById('main_category')
.addEventListener('change', function(){


    let parentId = this.value;


    let options = document.querySelectorAll('#sub_category option');



    options.forEach(function(option){


        if(option.value == '')
            return;



        option.style.display =
        (option.dataset.parent == parentId)
        ? ''
        : 'none';



    });



    document.getElementById('sub_category').value = '';



});


</script>


@endsection