<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f1a35">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', 'หน้าหลัก') · RUS E-Library</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo_rus.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">
        ข้ามไปเนื้อหาหลัก
    </a>

    @include('layouts.navigation')

    {{-- Header (สำหรับหน้าที่ใช้ component layout) --}}
    @isset($header)
        <section class="mx-auto mt-6 w-full max-w-7xl px-5">
            <div class="card px-6 py-5">
                {{ $header }}
            </div>
        </section>
    @endisset

    <main id="main" class="w-full flex-1">
        <div class="mx-auto max-w-7xl px-5 py-7">

            {{-- ข้อผิดพลาดจากการตรวจสอบฟอร์ม --}}
            @if ($errors->any() && ! View::hasSection('hide_global_errors'))
                <div class="alert alert-error mb-5" role="alert">
                    <i class="bi bi-exclamation-octagon mt-0.5"></i>
                    <div>
                        <div class="font-semibold">กรุณาตรวจสอบข้อมูลอีกครั้ง</div>
                        <ul class="mt-1 list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @yield('content')

            {{ $slot ?? '' }}
        </div>
    </main>

    <footer class="site-footer mt-auto border-t-4 border-gold-400 bg-ink-950 text-ink-200">
        <div class="mx-auto max-w-7xl px-5 py-10">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-12">
                <div class="md:col-span-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-[58px] items-center justify-center rounded-lg bg-white px-1.5">
                            <img src="{{ asset('images/logo_rus.png') }}" alt="RUS" class="h-7 w-auto">
                        </span>
                        <div class="leading-tight">
                            <div class="font-display text-lg font-bold text-white">ห้องสมุดอิเล็กทรอนิกส์</div>
                            <div class="text-xs">RUS E-Library</div>
                        </div>
                    </div>
                    <p class="mt-4 max-w-md text-sm leading-7 text-ink-300">
                        บริการหนังสืออิเล็กทรอนิกส์สำหรับนักศึกษา อาจารย์ บุคลากร และศิษย์เก่า
                        สำนักวิทยบริการและเทคโนโลยีสารสนเทศ มหาวิทยาลัยเทคโนโลยีราชมงคลสุวรรณภูมิ
                    </p>
                </div>

                <div class="md:col-span-4">
                    <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-gold-300">ติดต่อเรา</h3>
                    <ul class="space-y-2 text-sm">
                        <li class="flex gap-3"><i class="bi bi-geo-alt text-gold-400"></i> ศูนย์พระนครศรีอยุธยา หันตรา</li>
                        <li class="flex gap-3"><i class="bi bi-telephone text-gold-400"></i> 035-709081</li>
                        {{-- <li class="flex gap-3"><i class="bi bi-envelope text-gold-400"></i> library@rmutsb.ac.th</li> --}}
                    </ul>
                </div>

                <div class="md:col-span-3">
                    <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-gold-300">เมนูด่วน</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('ebooks.index') }}" class="hover:text-white">ค้นหา Ebook</a></li>
                        <li><a href="{{ route('ebooks.index', ['sort' => 'latest']) }}" class="hover:text-white">หนังสือมาใหม่</a></li>
                        <li><a href="{{ route('borrows.index') }}" class="hover:text-white">ชั้นหนังสือของฉัน</a></li>
                        <li><a href="{{ route('profile.edit') }}" class="hover:text-white">ข้อมูลบัญชี</a></li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-2 border-t border-white/10 pt-5 text-xs text-ink-400 sm:flex-row sm:justify-between">
                <p>© {{ date('Y') + 543 }} สำนักวิทยบริการและเทคโนโลยีสารสนเทศ มทร.สุวรรณภูมิ</p>
                <p>เอกสารทุกเล่มสงวนลิขสิทธิ์ ใช้เพื่อการศึกษาภายในมหาวิทยาลัยเท่านั้น</p>
            </div>
        </div>
    </footer>

    {{-- ผู้ช่วยห้องสมุด --}}
    @include('chatbot.widget')

    {{-- ข้อความแจ้งผล (แสดงเป็น Toast) --}}
    @if (session('success') || session('error'))
        <script type="application/json" id="flash-data">@json(['success' => session('success'), 'error' => session('error')])</script>
    @endif

    @stack('scripts')

    <script>
        // กดย้อนกลับหลังออกจากระบบ ให้โหลดหน้าใหม่ (ไม่แสดงข้อมูลจากแคช)
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) location.reload();
        });
    </script>
</body>

</html>
