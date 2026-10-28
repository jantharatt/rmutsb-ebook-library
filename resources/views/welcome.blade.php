<x-guest-layout>
    <div class="relative flex min-h-screen flex-col overflow-hidden bg-ink-900 text-white">
        <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
            style="background-image: repeating-linear-gradient(90deg, #fff 0 2px, transparent 2px 44px), repeating-linear-gradient(0deg, #fff 0 1px, transparent 1px 44px);"></div>
        <div class="pointer-events-none absolute -right-40 -top-40 h-[30rem] w-[30rem] rounded-full bg-gold-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-48 -left-24 h-[28rem] w-[28rem] rounded-full bg-royal-500/30 blur-3xl"></div>

        <header class="relative mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-[64px] items-center justify-center rounded-lg bg-white px-1.5"><img src="{{ asset('images/logo_rus.png') }}" alt="RUS" class="h-7 w-auto"></span>
                <div class="leading-tight">
                    <div class="font-display font-bold">ห้องสมุดอิเล็กทรอนิกส์</div>
                    <div class="text-xs text-ink-200">มหาวิทยาลัยเทคโนโลยีราชมงคลสุวรรณภูมิ</div>
                </div>
            </div>
            <a href="{{ route('login') }}" class="btn btn-gold">เข้าสู่ระบบ</a>
        </header>

        <main class="relative mx-auto flex w-full max-w-6xl flex-1 items-center px-6 pb-16">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold-300"></p>
                <h1 class="mt-4 font-display text-4xl font-bold leading-tight sm:text-6xl">
                    คลังความรู้ดิจิทัล<br><span class="text-gold-300">RUS E-book Library</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-ink-200">
                    ค้นหา ยืม และอ่านตำรา เอกสารประกอบการสอน และหนังสืออิเล็กทรอนิกส์ของมหาวิทยาลัยได้ทุกที่ ทุกเวลา
                </p>
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <a href="{{ route('login') }}" class="btn btn-gold btn-lg"><i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบด้วยอีเมลมหาวิทยาลัย</a>
                    <span class="text-sm text-ink-300">@rmutsb.ac.th</span>
                </div>
            </div>
        </main>

        <footer class="relative border-t border-white/10 py-5 text-center text-xs text-ink-300">
            สำนักวิทยบริการและเทคโนโลยีสารสนเทศ · โทร 035-709081 · library@rmutsb.ac.th
        </footer>
    </div>
</x-guest-layout>
