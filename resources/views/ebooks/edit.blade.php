@extends('layouts.app')

@section('title', 'แก้ไข Ebook')

@section('hide_global_errors', '1')

@section('content')

    <div class="px-5 py-4">


        {{-- HEADER --}}

        <div class="flex justify-between items-center mb-4">


            <h1 class="text-lg font-bold text-gray-800">
                ✏️ แก้ไข Ebook
            </h1>



            <a href="{{ route('ebooks.index') }}"
                class="px-3 py-1.5 text-xs bg-gray-600 text-white rounded-md hover:bg-gray-700">

                ← กลับ

            </a>


        </div>





        @if ($errors->any())


            <div class="bg-red-50 border border-red-200 text-red-600 text-xs rounded-md p-3 mb-3">


                <ul class="list-disc pl-5">

                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach

                </ul>


            </div>


        @endif





        <form action="{{ route('ebooks.update', $ebook) }}" method="POST" enctype="multipart/form-data">

            @csrf
            @method('PUT')





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





                        {{-- ศูนย์พื้นที่ --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                ศูนย์พื้นที่

                            </label>


                            <select name="campus" class="w-full h-8 text-xs rounded-md border-gray-300">


                                <option value="">
                                    เลือกศูนย์พื้นที่
                                </option>


                                <option value="นนทบุรี" {{ old('campus', $ebook->campus) == 'นนทบุรี' ? 'selected' : '' }}>
                                    นนทบุรี
                                </option>


                                <option value="วาสุกรี" {{ old('campus', $ebook->campus) == 'วาสุกรี' ? 'selected' : '' }}>
                                    วาสุกรี
                                </option>


                                <option value="สุพรรณบุรี" {{ old('campus', $ebook->campus) == 'สุพรรณบุรี' ? 'selected' : '' }}>
                                    สุพรรณบุรี
                                </option>


                                <option value="หันตรา" {{ old('campus', $ebook->campus) == 'หันตรา' ? 'selected' : '' }}>
                                    หันตรา
                                </option>


                            </select>


                        </div>







                        {{-- ชื่อเรื่อง --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                ชื่อเรื่อง <span class="text-red-500">*</span>

                            </label>


                            <input type="text" name="title" required value="{{ old('title', $ebook->title) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>






                        {{-- ผู้แต่ง --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                ชื่อผู้แต่ง <span class="text-red-500">*</span>

                            </label>


                            <input type="text" name="author" required value="{{ old('author', $ebook->author) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>






                        {{-- ISBN --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                ISBN

                            </label>


                            <input type="text" name="isbn" value="{{ old('isbn', $ebook->isbn) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>






                        {{-- ครั้งที่พิมพ์ --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                ครั้งที่พิมพ์

                            </label>


                            <input type="text" name="edition" value="{{ old('edition', $ebook->edition) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>






                        {{-- จำนวนหน้า --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                จำนวนหน้า

                            </label>


                            <input type="number" name="total_pages" value="{{ old('total_pages', $ebook->total_pages) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


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


                            <input type="text" name="imprint" value="{{ old('imprint', $ebook->imprint) }}"
                                placeholder="กรุงเทพฯ : สำนักพิมพ์, 2025"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>





                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                บรรณลักษณ์

                            </label>


                            <input type="text" name="physical_description"
                                value="{{ old('physical_description', $ebook->physical_description) }}"
                                placeholder="250 หน้า : ภาพประกอบ" class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>






                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                ชื่อชุด

                            </label>


                            <input type="text" name="series_name" value="{{ old('series_name', $ebook->series_name) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>






                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                หัวเรื่อง

                            </label>


                            <input type="text" name="subject_heading"
                                value="{{ old('subject_heading', $ebook->subject_heading) }}"
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





                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                เลขเรียกหนังสือ

                            </label>


                            <input type="text" name="call_number" value="{{ old('call_number', $ebook->call_number) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>







                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                เลขเรียก นว & รส

                            </label>


                            <input type="text" name="call_number_copy"
                                value="{{ old('call_number_copy', $ebook->call_number_copy) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>








                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                รายการเพิ่มชื่อบุคคล

                            </label>


                            <input type="text" name="added_personal_name"
                                value="{{ old('added_personal_name', $ebook->added_personal_name) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>







                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                รายการเพิ่มชื่อนิติบุคคล

                            </label>


                            <input type="text" name="added_corporate_name"
                                value="{{ old('added_corporate_name', $ebook->added_corporate_name) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>



                    </div>







                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5 mt-3">






                        {{-- หมวดหลัก --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                หมวดหลัก

                            </label>



                            <select id="main_category" name="main_category_code"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                                <option value="">
                                    เลือกหมวดหลัก
                                </option>



                                @foreach ($mainCategories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('main_category_code', $ebook->main_category_code) == $category->id ? 'selected' : '' }}>


                                        {{ $category->code }} - {{ $category->name }}


                                    </option>
                                @endforeach


                            </select>



                        </div>







                        {{-- หมวดย่อย --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                หมวดย่อย

                            </label>



                            <select id="sub_category" name="category_id" required
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                                <option value="">
                                    เลือกหมวดย่อย
                                </option>



                                @foreach ($subCategories as $category)
                                    <option value="{{ $category->id }}" data-parent="{{ $category->parent_id }}"
                                        {{ old('category_id', $ebook->category_id) == $category->id ? 'selected' : '' }}>


                                        {{ $category->code }} - {{ $category->name }}


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

                        ⚙️ ข้อมูลระบบ / การจัดซื้อ

                    </h3>


                </div>




                <div class="p-3">


                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">





                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                ปีงบประมาณที่จัดซื้อ

                            </label>


                            <input type="number" name="budget_year"
                                value="{{ old('budget_year', $ebook->budget_year) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>






                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                เลขทะเบียน

                            </label>


                            <input type="text" name="registration_number"
                                value="{{ old('registration_number', $ebook->registration_number) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>






                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                สถานที่เก็บ (ศูนย์พื้นที่)

                            </label>


                            {{-- <input type="text" name="campus" value="{{ old('campus', $ebook->campus) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300"> --}}


                        </div>






                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                คณะ / สาขา

                            </label>


                            <input type="text" name="faculty" value="{{ old('faculty', $ebook->faculty) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>







                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                ผู้ลงรายการ

                            </label>


                            <input type="text" name="cataloguer" value="{{ old('cataloguer', $ebook->cataloguer) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>






                        <div>

                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                แหล่งที่มา

                            </label>


                            <input type="text" name="source" value="{{ old('source', $ebook->source) }}"
                                class="w-full h-8 text-xs rounded-md border-gray-300">


                        </div>




                    </div>


                </div>


            </div>








            {{-- ========================= --}}
            {{-- FILE --}}
            {{-- ========================= --}}



            <div class="bg-white rounded-lg border border-gray-200 shadow-sm mb-3">


                <div class="px-3 py-2 border-b bg-gray-50">


                    <h3 class="text-xs font-semibold text-gray-700">

                        📁 ไฟล์ Ebook

                    </h3>


                </div>





                <div class="p-3">


                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">





                        {{-- COVER --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                รูปปก

                            </label>




                            <div class="mb-2 flex items-end gap-3">
                                <x-book-cover :ebook="$ebook" class="!w-24" />
                                <div class="text-[11px] text-ink-500">
                                    @if ($ebook->cover)
                                        รูปปกที่อัปโหลด
                                        <label class="mt-1 flex items-center gap-1.5 text-red-600">
                                            <input type="checkbox" name="remove_cover" value="1" class="rounded border-ink-300">
                                            ลบรูปปก (ใช้หน้าแรกของ PDF แทน)
                                        </label>
                                    @elseif ($ebook->cover_url)
                                        ใช้หน้าแรกของ PDF เป็นปกอัตโนมัติ
                                    @else
                                        ยังไม่มีปก — ระบบจะสร้างจากหน้าแรกของ PDF
                                    @endif
                                </div>
                            </div>

                            <input type="file" name="cover" accept="image/jpeg,image/png,image/webp"
                                class="block w-full text-xs border border-gray-300 rounded-md p-1">


                            <p class="text-[10px] text-gray-400 mt-1">

                                เลือกไฟล์ใหม่หากต้องการเปลี่ยนรูปปก

                            </p>


                        </div>







                        {{-- PDF --}}


                        <div>


                            <label class="block text-[11px] font-semibold text-gray-600 mb-1">

                                ไฟล์ PDF

                            </label>





                            @if ($ebook->file_path)
                                <a href="{{ route('ebooks.reader', $ebook) }}" target="_blank"
                                    class="inline-block mb-2 px-3 py-1.5 text-xs rounded-md bg-blue-500 text-white hover:bg-ink-800">


                                    📄 ดูไฟล์เดิม


                                </a>
                            @endif






                            <input type="file" name="pdf_file" accept="application/pdf" data-pdf-input
                                class="block w-full text-xs border border-gray-300 rounded-md p-1">
            <div data-auto-cover-wrap class="hidden mt-2 flex items-center gap-3 rounded-lg border border-ink-100 bg-ink-50/60 p-2">
                <img data-auto-cover-preview alt="ปกจากหน้าแรกของ PDF" class="h-24 w-auto rounded border border-ink-100 bg-white">
                <p class="text-xs text-ink-600">ภาพหน้าแรกของ PDF<br><span class="text-ink-400">จะใช้เป็นปกอัตโนมัติ ถ้าไม่ได้อัปโหลดรูปปก</span></p>
            </div>
            <p data-auto-cover-status class="mt-1 text-[11px] text-ink-500">เมื่อเปลี่ยน PDF ปกอัตโนมัติและไฟล์ตัวอย่างจะถูกสร้างใหม่</p>



                            <p class="text-[10px] text-gray-400 mt-1">

                                หากไม่เลือก ระบบจะใช้ไฟล์เดิม

                            </p>


                        </div>





                    </div>


                </div>


            </div>








            {{-- BUTTON --}}


            <div class="flex justify-end gap-2 mb-5">


                <a href="{{ route('ebooks.index') }}"
                    class="px-4 py-2 text-xs rounded-md bg-gray-200 text-gray-700 hover:bg-gray-300">


                    ยกเลิก


                </a>





                <button type="submit" class="px-4 py-2 text-xs rounded-md bg-ink-800 text-white hover:bg-ink-900">


                    💾 บันทึกการแก้ไข


                </button>



            </div>





        </form>


    </div>

@endsection



@push('scripts')
<script>
    const main = document.getElementById('main_category');
    const sub = document.getElementById('sub_category');

    function filterSub(resetSelection = false) {
        const parent = main.value;
        const previousValue = sub.value;

        [...sub.options].forEach(function (option) {
            if (option.value === '') {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.parent !== parent;
        });

        const selectedOption = [...sub.options].find(function (option) {
            return option.value === previousValue &&
                option.dataset.parent === parent;
        });

        if (resetSelection || !selectedOption) {
            sub.value = '';
        } else {
            sub.value = previousValue;
        }
    }

    main.addEventListener('change', function () {
        filterSub(true);
    });

    // ตรวจสอบหมวดหมู่เมื่อเปิดหน้าแก้ไข
    filterSub(false);
</script>
@endpush
