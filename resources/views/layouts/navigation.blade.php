@php
    $user = auth()->user();
    $isAdmin = $user && $user->isAdmin();
    $adminMode = $isAdmin && session('view_mode') !== 'user';

    // เมนูตามบทบาท
    $menu = $adminMode
        ? [
            ['route' => 'dashboard', 'match' => 'dashboard*', 'icon' => 'bi-speedometer2', 'label' => 'แดชบอร์ด'],
            ['route' => 'ebooks.index', 'match' => 'ebooks.*', 'icon' => 'bi-journal-bookmark', 'label' => 'จัดการ Ebook'],
            ['route' => 'borrows.index', 'match' => 'borrows.*', 'icon' => 'bi-arrow-left-right', 'label' => 'ยืม–คืน'],
            ['route' => 'categories.index', 'match' => 'categories.*', 'icon' => 'bi-diagram-3', 'label' => 'หมวดหมู่'],
            ['route' => 'borrow-policies.index', 'match' => 'borrow-policies.*', 'icon' => 'bi-sliders', 'label' => 'นโยบายการยืม'],
            ['route' => 'users.index', 'match' => 'users.*', 'icon' => 'bi-people', 'label' => 'ผู้ใช้งาน'],
        ]
        : [
            ['route' => $isAdmin ? 'library' : 'user.home', 'match' => ['user.home', 'library'], 'icon' => 'bi-house-door', 'label' => 'หน้าแรก'],
            ['route' => 'ebooks.index', 'match' => 'ebooks.*', 'icon' => 'bi-search', 'label' => 'ค้นหา Ebook'],
            ['route' => 'borrows.index', 'match' => 'borrows.*', 'icon' => 'bi-bookmark-heart', 'label' => 'ชั้นหนังสือของฉัน'],
        ];

    $homeRoute = $adminMode ? 'dashboard' : ($isAdmin ? 'library' : 'user.home');
@endphp

<header class="site-header sticky top-0 z-40" x-data="{ mobile: false, account: false }">

    {{-- แถบอัตลักษณ์มหาวิทยาลัย --}}
    <div class="hidden bg-ink-950 text-[12px] text-ink-200 sm:block">
        <div class="mx-auto flex h-8 max-w-7xl items-center justify-between px-5">
            <span>มหาวิทยาลัยเทคโนโลยีราชมงคลสุวรรณภูมิ · Rajamangala University of Technology Suvarnabhumi</span>
            {{-- <span class="flex items-center gap-4">
                <span><i class="bi bi-telephone"></i> 035-709081</span>
                <span><i class="bi bi-envelope"></i> library@rmutsb.ac.th</span>
            </span> --}}
        </div>
    </div>

    <div class="border-b border-ink-950/40 bg-ink-900 text-white shadow-lg shadow-ink-950/10">
        <div class="mx-auto max-w-7xl px-5">
            <div class="flex h-[68px] items-center gap-4">

                {{-- โลโก้ --}}
                <a href="{{ route($homeRoute) }}" class="flex shrink-0 items-center gap-3">
                    <span class="flex h-10 w-[58px] items-center justify-center rounded-lg bg-white px-1.5 shadow-sm">
                        <img src="{{ asset('images/logo_rus.png') }}" alt="RUS" class="h-7 w-auto">
                    </span>
                    <span class="hidden leading-tight sm:block">
                        <span class="block font-display text-[17px] font-bold tracking-wide">ห้องสมุดอิเล็กทรอนิกส์</span>
                        <span class="block text-[11px] text-ink-200">สำนักวิทยบริการและเทคโนโลยีสารสนเทศ</span>
                    </span>
                </a>

                {{-- ค้นหาด่วน --}}
                <form action="{{ route('ebooks.index') }}" method="GET" class="ml-auto hidden max-w-sm flex-1 md:block" role="search">
                    <label class="relative block">
                        <span class="sr-only">ค้นหา Ebook</span>
                        <i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-ink-300"></i>
                        <input type="search" name="search" value="{{ request()->routeIs('ebooks.index') ? request('search') : '' }}"
                            placeholder="ค้นหาชื่อเรื่อง ผู้แต่ง ISBN หัวเรื่อง..."
                            class="w-full rounded-xl border-0 bg-white/10 py-2.5 pl-10 pr-3 text-sm text-white placeholder:text-ink-300 focus:bg-white focus:text-ink-900 focus:ring-2 focus:ring-gold-400">
                    </label>
                </form>

                {{-- บัญชีผู้ใช้ --}}
                <div class="relative ml-auto md:ml-0">
                    <button type="button" @click="account = !account" @keydown.escape.window="account = false"
                        class="flex items-center gap-2.5 rounded-xl px-2 py-1.5 transition hover:bg-white/10"
                        :aria-expanded="account">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gold-400 font-bold text-ink-950">
                            {{ mb_substr($user->name, 0, 1) }}
                        </span>
                        <span class="hidden text-left leading-tight lg:block">
                            <span class="block max-w-[150px] truncate text-sm font-semibold">{{ $user->name }}</span>
                            <span class="block text-[11px] text-ink-200">
                                {{ $user->role_name }}@if ($isAdmin) · {{ $adminMode ? 'โหมดผู้ดูแล' : 'มุมมองผู้ใช้' }}@endif
                            </span>
                        </span>
                        <i class="bi bi-chevron-down hidden text-xs text-ink-200 lg:block"></i>
                    </button>

                    <div x-cloak x-show="account" x-transition.origin.top.right @click.outside="account = false"
                        class="absolute right-0 mt-2 w-64 overflow-hidden rounded-2xl border border-ink-100 bg-white text-ink-800 shadow-lift">
                        <div class="border-b border-ink-100 bg-ink-50/60 px-4 py-3">
                            <div class="truncate text-sm font-semibold">{{ $user->name }}</div>
                            <div class="truncate text-xs text-ink-500">{{ $user->email }}</div>
                            @if ($user->faculty)
                                <div class="mt-1 truncate text-xs text-ink-500">{{ $user->faculty }}</div>
                            @endif
                        </div>
                        <nav class="p-1.5 text-sm">
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-ink-50">
                                <i class="bi bi-person-circle text-ink-400"></i> โปรไฟล์และรหัสผ่าน
                            </a>
                            <a href="{{ route('borrows.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-ink-50">
                                <i class="bi bi-bookmark-heart text-ink-400"></i> {{ $adminMode ? 'รายการยืม–คืนทั้งหมด' : 'ชั้นหนังสือของฉัน' }}
                            </a>
                            @if ($isAdmin)
                                @if ($adminMode)
                                    <a href="{{ route('library') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-ink-50">
                                        <i class="bi bi-eye text-ink-400"></i> ดูหน้าเว็บแบบผู้ใช้
                                    </a>
                                @else
                                    <a href="{{ route('admin.mode') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 font-semibold text-royal-700 hover:bg-royal-50">
                                        <i class="bi bi-shield-lock"></i> กลับโหมดผู้ดูแลระบบ
                                    </a>
                                @endif
                            @endif
                        </nav>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-ink-100 p-1.5">
                            @csrf
                            <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                            </button>
                        </form>
                    </div>
                </div>

                {{-- ปุ่มเมนูมือถือ --}}
                <button type="button" class="rounded-lg p-2 text-xl hover:bg-white/10 md:hidden" @click="mobile = !mobile" aria-label="เมนู">
                    <i class="bi" :class="mobile ? 'bi-x-lg' : 'bi-list'"></i>
                </button>
            </div>

            {{-- เมนูหลัก (เดสก์ท็อป) --}}
            <nav class="hidden h-12 items-center gap-1 md:flex" aria-label="เมนูหลัก">
                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                        class="nav-link {{ request()->routeIs(...(array) $item['match']) ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
                    </a>
                @endforeach

                @if ($adminMode)
                    <a href="{{ route('ebooks.create') }}" class="btn btn-gold btn-sm ml-auto">
                        <i class="bi bi-plus-lg"></i> เพิ่ม Ebook
                    </a>
                @elseif ($isAdmin)
                    <span class="ml-auto rounded-full bg-gold-400/15 px-3 py-1 text-xs font-semibold text-gold-300">
                        <i class="bi bi-eye"></i> กำลังดูในมุมมองผู้ใช้
                    </span>
                @endif
            </nav>
        </div>

        {{-- เมนูมือถือ --}}
        <div x-cloak x-show="mobile" x-transition class="border-t border-white/10 md:hidden">
            <div class="space-y-1 px-5 py-3">
                <form action="{{ route('ebooks.index') }}" method="GET" class="mb-3">
                    <input type="search" name="search" placeholder="ค้นหา Ebook..."
                        class="w-full rounded-xl border-0 bg-white/10 px-4 py-2.5 text-sm text-white placeholder:text-ink-300 focus:bg-white focus:text-ink-900">
                </form>
                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs(...(array) $item['match']) ? 'bg-white/10 font-semibold text-gold-300' : 'text-ink-100' }}">
                        <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</header>
