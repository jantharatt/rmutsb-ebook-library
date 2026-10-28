<x-guest-layout>
    <div class="min-h-screen bg-paper lg:grid lg:grid-cols-2">

        {{-- ส่วนแนะนำระบบ --}}
        <div class="relative hidden overflow-hidden bg-ink-900 px-12 py-14 text-white lg:flex lg:flex-col lg:justify-between xl:px-20">
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
                style="background-image: repeating-linear-gradient(90deg, #fff 0 2px, transparent 2px 40px), repeating-linear-gradient(0deg, #fff 0 1px, transparent 1px 40px);"></div>
            <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full bg-gold-400/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-40 -left-24 h-96 w-96 rounded-full bg-royal-500/30 blur-3xl"></div>

            <div class="relative z-10 flex items-center gap-3">
                <span class="flex h-12 w-[70px] items-center justify-center rounded-xl bg-white px-2 shadow">
                    <img src="{{ asset('images/logo_rus.png') }}" alt="RUS" class="h-8 w-auto">
                </span>
                <div class="leading-tight">
                    <div class="font-display text-lg font-bold">ห้องสมุดอิเล็กทรอนิกส์</div>
                    <div class="text-sm text-ink-200">สำนักวิทยบริการและเทคโนโลยีสารสนเทศ</div>
                </div>
            </div>

            <div class="relative z-10 max-w-xl py-12">
                <p class="mb-5 inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm text-ink-100">
                    Rajamangala University of Technology Suvarnabhumi
                </p>
                <h2 class="font-display text-4xl font-bold leading-tight text-white xl:text-5xl">
                    ห้องสมุดดิจิทัล
                    <span class="mt-2 block text-gold-300">ทุกที่ ทุกเวลา</span>
                </h2>
                <p class="mt-6 max-w-lg text-base leading-8 text-ink-200">
                    ค้นหา ยืม และอ่านหนังสืออิเล็กทรอนิกส์ของมหาวิทยาลัยได้ทันที
                    สำหรับนักศึกษา อาจารย์ บุคลากร และศิษย์เก่า
                </p>
                <ul class="mt-8 grid max-w-md grid-cols-3 gap-3 text-center text-sm">
                    <li class="rounded-xl bg-white/5 p-3"><i class="bi bi-search block text-xl text-gold-300"></i>ค้นหาง่าย</li>
                    <li class="rounded-xl bg-white/5 p-3"><i class="bi bi-phone block text-xl text-gold-300"></i>อ่านได้ทุกอุปกรณ์</li>
                    <li class="rounded-xl bg-white/5 p-3"><i class="bi bi-stars block text-xl text-gold-300"></i>ผู้ช่วยค้นหา</li>
                </ul>
            </div>

            <div class="relative z-10 border-t border-white/10 pt-5 text-sm text-ink-300">
                มหาวิทยาลัยเทคโนโลยีราชมงคลสุวรรณภูมิ
            </div>
        </div>

        {{-- ส่วนเข้าสู่ระบบ --}}
        <div class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8 lg:px-12">
            <div class="w-full max-w-md">

                {{-- โลโก้บนมือถือ --}}
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <span class="flex h-11 w-[64px] items-center justify-center rounded-lg bg-white px-1.5 shadow-sm"><img src="{{ asset('images/logo_rus.png') }}" alt="RUS" class="h-7 w-auto"></span>

                    <div>
                        <h1 class="font-display font-bold text-ink-900">ห้องสมุดอิเล็กทรอนิกส์</h1>
                        <p class="text-xs text-ink-500">มทร.สุวรรณภูมิ</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-ink-100 bg-white p-6 shadow-card sm:p-9">

                    <div class="mb-7">
                        <h2 class="font-display text-2xl font-bold text-ink-900">
                            เข้าสู่ระบบ
                        </h2>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            ลงชื่อเข้าใช้เพื่อเข้าถึงห้องสมุดดิจิทัล
                            ของมหาวิทยาลัย
                        </p>
                    </div>

                    {{-- สถานะจากระบบ --}}
                    <x-auth-session-status
                        class="mb-4"
                        :status="session('status')"
                    />

                    @php $emailOnly = \App\Support\UniversityAccount::emailOnlyLogin(); @endphp

                    @if ($emailOnly)
                        <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-800">
                            <p class="font-semibold"><i class="bi bi-cone-striped"></i> โหมดทดสอบ: เข้าสู่ระบบด้วยอีเมลอย่างเดียว</p>
                            <p class="mt-1">นักศึกษา: <b>6612345678-st@{{ \App\Support\UniversityAccount::domain() }}</b> · อาจารย์/บุคลากร: <b>ชื่อ.นามสกุล@{{ \App\Support\UniversityAccount::domain() }}</b></p>
                            <p class="mt-1 text-amber-700">ปิดโหมดนี้ก่อนเปิดใช้งานจริง (LOGIN_EMAIL_ONLY=false)</p>
                        </div>
                    @endif

                    {{-- ฟอร์มล็อกอินเดิม --}}
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        {{-- อีเมล --}}
                        <div>
                            <x-input-label
                                for="email"
                                :value="'อีเมลมหาวิทยาลัย'"
                                class="text-sm font-medium text-slate-700"
                            />

                            <x-text-input
                                id="email"
                                class="mt-2 block w-full rounded-lg border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-royal-600 focus:ring-royal-600"
                                type="email"
                                name="email"
                                :value="old('email')"
                                placeholder="{{ $emailOnly ? '6612345678-st@rmutsb.ac.th' : 'example@rmutsb.ac.th' }}"
                                required
                                autofocus
                                autocomplete="username"
                            />

                            <x-input-error
                                :messages="$errors->get('email')"
                                class="mt-2"
                            />
                        </div>

                        {{-- รหัสผ่าน --}}
                        @unless ($emailOnly)
                        <div class="mt-5">
                            <x-input-label
                                for="password"
                                :value="'รหัสผ่าน'"
                                class="text-sm font-medium text-slate-700"
                            />

                            <x-text-input
                                id="password"
                                class="mt-2 block w-full rounded-lg border-slate-300 px-4 py-3 text-sm shadow-sm focus:border-royal-600 focus:ring-royal-600"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                            />

                            <x-input-error
                                :messages="$errors->get('password')"
                                class="mt-2"
                            />
                        </div>
                        @endunless

                        {{-- จดจำการเข้าสู่ระบบ --}}
                        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                            <label
                                for="remember_me"
                                class="inline-flex items-center gap-2 text-sm text-slate-600"
                            >
                                <input
                                    id="remember_me"
                                    type="checkbox"
                                    name="remember"
                                    class="rounded border-slate-300 text-blue-800 shadow-sm focus:ring-blue-800"
                                >
                                จดจำการเข้าสู่ระบบ
                            </label>

                            @if (Route::has('password.request') && ! $emailOnly)
                                <a
                                    href="{{ route('password.request') }}"
                                    class="text-sm text-blue-800 hover:text-blue-950 hover:underline"
                                >
                                    ลืมรหัสผ่าน?
                                </a>
                            @endif
                        </div>

                        {{-- ปุ่มเข้าสู่ระบบ --}}
                        <div class="mt-7">
                            <x-primary-button
                                class="flex w-full justify-center rounded-lg border border-transparent bg-ink-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#1A3A6B] focus:bg-[#1A3A6B] active:bg-ink-900"
                            >
                                เข้าสู่ระบบ
                            </x-primary-button>
                        </div>
                    </form>

                    {{-- ตัวแบ่ง --}}
                    <div class="my-6 flex items-center gap-4">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <span class="text-xs text-slate-400">หรือ</span>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    {{-- เข้าสู่ระบบด้วย Google เดิม --}}
                    <a
                        href="{{ route('auth.google.redirect') }}"
                        class="flex w-full items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-800 focus:ring-offset-2"
                    >
                        <svg
                            class="h-5 w-5 shrink-0"
                            viewBox="0 0 48 48"
                            xmlns="http://www.w3.org/2000/svg"
                            aria-hidden="true"
                        >
                            <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12s5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24s8.955,20,20,20s20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                            <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                            <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                            <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-.792,2.237-2.231,4.166-4.087,5.571l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
                        </svg>

                        เข้าสู่ระบบด้วย Google
                    </a>

                    {{-- หมายเหตุความปลอดภัย --}}
                    <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50 p-3 text-xs leading-5 text-slate-600">
                        <p class="font-medium text-ink-900">
                            สำหรับสมาชิกมหาวิทยาลัย
                        </p>
                        <p class="mt-1">
                            โปรดใช้อีเมลมหาวิทยาลัยที่ได้รับอนุญาตให้เข้าใช้งานระบบ
                        </p>
                    </div>
                </div>

                <p class="mt-6 text-center text-xs leading-5 text-slate-400">
                    RUS E-Library<br>
                    Rajamangala University of Technology Suvarnabhumi
                </p>

            </div>
        </div>
    </div>
</x-guest-layout>
