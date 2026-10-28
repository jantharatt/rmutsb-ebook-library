@extends('layouts.app')

@section('title', 'แดชบอร์ดรายงาน')

@php
    use App\Support\ThaiDate;
    use Carbon\Carbon;

    // ลิงก์เลื่อนช่วงรายงาน (ย้อนหลัง / ถัดไป)
    $nav = null;
    switch ($period) {
        case 'day':
            $d = Carbon::parse($selectedDay);
            $nav = [
                ['period' => 'day', 'day' => $d->copy()->subDay()->toDateString()],
                $d->lt(today()) ? ['period' => 'day', 'day' => $d->copy()->addDay()->toDateString()] : null,
            ];
            break;
        case 'month':
            $m = Carbon::createFromFormat('!Y-m', $selectedMonth);
            $nav = [
                ['period' => 'month', 'month' => $m->copy()->subMonth()->format('Y-m')],
                $m->copy()->addMonth()->lte(today()) ? ['period' => 'month', 'month' => $m->copy()->addMonth()->format('Y-m')] : null,
            ];
            break;
        case 'year':
            $nav = [
                ['period' => 'year', 'year' => $selectedYear - 1],
                $selectedYear < today()->year ? ['period' => 'year', 'year' => $selectedYear + 1] : null,
            ];
            break;
        case 'fiscal':
            $nav = [
                ['period' => 'fiscal', 'fiscal' => $selectedFiscal - 1],
                $selectedFiscal < $currentFiscal ? ['period' => 'fiscal', 'fiscal' => $selectedFiscal + 1] : null,
            ];
            break;
    }

    $presets = [
        ['วันนี้', ['period' => 'day', 'day' => today()->toDateString()]],
        ['7 วัน', ['period' => 'last7']],
        ['30 วัน', ['period' => 'last30']],
        ['เดือนนี้', ['period' => 'month', 'month' => today()->format('Y-m')]],
        ['เดือนก่อน', ['period' => 'month', 'month' => today()->subMonthNoOverflow()->format('Y-m')]],
        ['ปีนี้', ['period' => 'year', 'year' => today()->year]],
        ['ปีงบฯ '.$currentFiscal, ['period' => 'fiscal', 'fiscal' => $currentFiscal]],
    ];

    $isActivePreset = function ($params) use ($period, $selectedDay, $selectedMonth, $selectedYear, $selectedFiscal) {
        if ($params['period'] !== $period) return false;
        return match ($period) {
            'day' => $params['day'] === $selectedDay,
            'month' => $params['month'] === $selectedMonth,
            'year' => (int) $params['year'] === (int) $selectedYear,
            'fiscal' => (int) $params['fiscal'] === (int) $selectedFiscal,
            default => true,
        };
    };

    $kpiIcons = [
        'borrows' => 'bi-box-arrow-in-right', 'returns' => 'bi-box-arrow-left', 'borrowers' => 'bi-people',
        'books' => 'bi-journal-bookmark', 'renewals' => 'bi-arrow-repeat', 'newBooks' => 'bi-journal-plus',
        'newUsers' => 'bi-person-plus', 'avgLoan' => 'bi-hourglass-split',
    ];

    $heatMax = max(1, max(array_map('max', $heatmap)));
    $timeSlots = ['00–03', '03–06', '06–09', '09–12', '12–15', '15–18', '18–21', '21–24'];
    $dayOrder = [1, 2, 3, 4, 5, 6, 0];

    $chartData = [
        'trend' => $trend,
        'category' => ['labels' => $byCategory->pluck('label'), 'values' => $byCategory->pluck('total')],
        'collection' => ['labels' => $collectionByCategory->pluck('label'), 'values' => $collectionByCategory->pluck('total')],
    ];

    $query = request()->query();
@endphp

@section('content')
<div data-dashboard>

    {{-- ===== หัวรายงาน ===== --}}
    <div class="page-head">
        <div>
            {{-- <p class="page-eyebrow">Library analytics</p> --}}
            <h1 class="page-title">{{ $reportTitle }}</h1>
            <p class="page-sub">
                <i class="bi bi-calendar3"></i> {{ $rangeLabel }}
                <span class="text-ink-300">·</span>
                เทียบกับ {{ ThaiDate::short($prevStart) }}@unless($prevStart->isSameDay($prevEnd)) – {{ ThaiDate::short($prevEnd) }}@endunless
            </p>
        </div>
        <div class="no-print flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline"><i class="bi bi-printer"></i> พิมพ์</button>
            <a href="{{ route('dashboard.export', $query) }}" class="btn btn-primary"><i class="bi bi-filetype-csv"></i> ส่งออก CSV</a>
        </div>
    </div>

    {{-- ===== ตัวกรองช่วงเวลา ===== --}}
    <form method="GET" action="{{ route('dashboard') }}" class="card no-print mb-6 p-4"
        x-data="{ period: '{{ in_array($period, ['last7', 'last30']) ? 'range' : $period }}' }">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
            <div>
                <span class="label">รูปแบบรายงาน</span>
                <div class="inline-flex rounded-xl bg-ink-50 p-1">
                    @foreach (['day' => 'รายวัน', 'month' => 'รายเดือน', 'year' => 'รายปี', 'fiscal' => 'ปีงบประมาณ', 'range' => 'กำหนดเอง'] as $value => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="period" value="{{ $value }}" x-model="period" class="peer sr-only">
                            <span class="block rounded-lg px-3 py-1.5 text-sm font-medium text-ink-500 transition peer-checked:bg-white peer-checked:text-ink-900 peer-checked:shadow-sm">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-1 flex-wrap items-end gap-3">
                <label x-show="period === 'day'" class="block">
                    <span class="label">วันที่</span>
                    <input type="date" name="day" value="{{ $selectedDay }}" max="{{ today()->toDateString() }}" class="input" :disabled="period !== 'day'">
                </label>
                <label x-show="period === 'month'" class="block">
                    <span class="label">เดือน</span>
                    <input type="month" name="month" value="{{ $selectedMonth }}" max="{{ today()->format('Y-m') }}" class="input" :disabled="period !== 'month'">
                </label>
                <label x-show="period === 'year'" class="block">
                    <span class="label">ปี พ.ศ.</span>
                    <select name="year" class="select w-36" :disabled="period !== 'year'">
                        @foreach ($yearOptions as $y)
                            <option value="{{ $y }}" @selected($y === (int) $selectedYear)>{{ $y + 543 }}</option>
                        @endforeach
                    </select>
                </label>
                <label x-show="period === 'fiscal'" class="block">
                    <span class="label">ปีงบประมาณ (1 ต.ค. – 30 ก.ย.)</span>
                    <select name="fiscal" class="select w-36" :disabled="period !== 'fiscal'">
                        @for ($f = $currentFiscal; $f >= $currentFiscal - 10; $f--)
                            <option value="{{ $f }}" @selected($f === (int) $selectedFiscal)>{{ $f }}</option>
                        @endfor
                    </select>
                </label>
                <template x-if="period === 'range'">
                    <div class="flex flex-wrap items-end gap-3">
                        <label class="block">
                            <span class="label">ตั้งแต่วันที่</span>
                            <input type="date" name="start_date" value="{{ $startDateInput }}" max="{{ today()->toDateString() }}" class="input">
                        </label>
                        <label class="block">
                            <span class="label">ถึงวันที่</span>
                            <input type="date" name="end_date" value="{{ $endDateInput }}" max="{{ today()->toDateString() }}" class="input">
                        </label>
                    </div>
                </template>

                <button class="btn btn-primary"><i class="bi bi-funnel"></i> แสดงรายงาน</button>

                @if ($nav)
                    <div class="ml-auto flex gap-1">
                        <a href="{{ route('dashboard', $nav[0]) }}" class="btn btn-outline btn-sm"><i class="bi bi-chevron-left"></i> ย้อนหลัง</a>
                        @if ($nav[1])
                            <a href="{{ route('dashboard', $nav[1]) }}" class="btn btn-outline btn-sm">ถัดไป <i class="bi bi-chevron-right"></i></a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-ink-100 pt-3">
            <span class="text-xs font-semibold text-ink-400">ช่วงด่วน</span>
            @foreach ($presets as [$label, $params])
                <a href="{{ route('dashboard', $params) }}"
                    class="rounded-full px-3 py-1 text-xs font-medium transition {{ $isActivePreset($params) ? 'bg-ink-900 text-white' : 'bg-ink-50 text-ink-600 hover:bg-ink-100' }}">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    {{-- ===== ตัวชี้วัดหลักของช่วงรายงาน ===== --}}
    <section class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach ($kpis as $key => $kpi)
            <div class="stat">
                <div class="flex items-start justify-between gap-2">
                    <div class="stat-label">{{ $kpi['label'] }}</div>
                    <i class="bi {{ $kpiIcons[$key] }} text-ink-300"></i>
                </div>
                <div class="stat-value">{{ is_float($kpi['value']) ? number_format($kpi['value'], 1) : number_format($kpi['value']) }}</div>
                @if ($kpi['previous'] !== null && (float) $kpi['previous'] === 0.0 && $kpi['value'] > 0)
                    <div class="mt-1 text-xs text-ink-400">ช่วงก่อนหน้าไม่มีข้อมูล</div>
                @elseif ($kpi['change'] !== null)
                    @php $up = $kpi['change'] > 0; $flat = (float) $kpi['change'] === 0.0; @endphp
                    <div class="mt-1 flex items-center gap-1.5 text-xs">
                        <span class="inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 font-semibold {{ $flat ? 'bg-ink-50 text-ink-500' : ($up ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700') }}">
                            <i class="bi {{ $flat ? 'bi-dash' : ($up ? 'bi-arrow-up-right' : 'bi-arrow-down-right') }}"></i>
                            {{ $flat ? '0' : ($up ? '+' : '').$kpi['change'] }}%
                        </span>
                        <span class="text-ink-400">ช่วงก่อน {{ number_format($kpi['previous']) }}</span>
                    </div>
                @else
                    <div class="mt-1 text-xs text-ink-400">จากรายการที่คืนในช่วงนี้</div>
                @endif
            </div>
        @endforeach
    </section>

    {{-- ===== แนวโน้ม + สถานะปัจจุบัน ===== --}}
    <section class="mb-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-head">
                <h2 class="card-title"><i class="bi bi-graph-up text-gold-600"></i> แนวโน้มการยืม–คืน (ราย{{ $trend['unit'] }})</h2>
                <span class="text-xs text-ink-400">รวม ยืม {{ number_format(array_sum($trend['borrows'])) }} · คืน {{ number_format(array_sum($trend['returns'])) }}</span>
            </div>
            <div class="card-body">
                <div class="relative h-72"><canvas id="trendChart" aria-label="กราฟแนวโน้มการยืมและคืน" role="img"></canvas></div>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h2 class="card-title"><i class="bi bi-activity text-gold-600"></i> สถานะการยืม ณ วันนี้</h2>
            </div>
            <div class="card-body space-y-3">
                @foreach ([
                    ['กำลังยืม (อยู่ในกำหนด)', $loanStatus['active'], 'bi-book', 'text-royal-700 bg-royal-50'],
                    ['ครบกำหนดภายใน 3 วัน', $loanStatus['dueSoon'], 'bi-hourglass-split', 'text-amber-700 bg-amber-50'],
                    ['เลยกำหนด / หมดอายุ', $loanStatus['overdue'], 'bi-exclamation-triangle', 'text-red-700 bg-red-50'],
                    ['คืนแล้วทั้งหมด', $loanStatus['returned'], 'bi-check2-circle', 'text-emerald-700 bg-emerald-50'],
                ] as [$label, $value, $icon, $tone])
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $tone }}"><i class="bi {{ $icon }}"></i></span>
                        <span class="flex-1 text-sm text-ink-600">{{ $label }}</span>
                        <span class="font-display text-xl font-bold tabular-nums">{{ number_format($value) }}</span>
                    </div>
                @endforeach

                <div class="border-t border-ink-100 pt-3">
                    <div class="mb-1 flex justify-between text-xs text-ink-500">
                        <span>อัตราการใช้งานคอลเลกชัน</span>
                        <span class="font-semibold text-ink-800">{{ $overview['utilization'] }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-ink-50">
                        <div class="h-full rounded-full bg-[#4061b8]" style="width: {{ min(100, $overview['utilization']) }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-ink-400">หนังสือที่เคยถูกยืมอย่างน้อย 1 ครั้ง · ยังไม่เคยถูกยืม {{ number_format($overview['neverBorrowed']) }} เล่ม</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ===== มิติการใช้งาน ===== --}}
    <section class="mb-6 grid gap-6 lg:grid-cols-3">
        <div class="card">
            <div class="card-head"><h2 class="card-title"><i class="bi bi-diagram-3 text-gold-600"></i> การยืมตามหมวดหมู่หลัก</h2></div>
            <div class="card-body">
                @if ($byCategory->isEmpty())
                    <p class="py-10 text-center text-sm text-ink-400">ไม่มีการยืมในช่วงนี้</p>
                @else
                    <div class="relative" style="height: {{ max(160, $byCategory->count() * 34) }}px"><canvas id="categoryChart" role="img" aria-label="การยืมตามหมวดหมู่"></canvas></div>
                @endif
            </div>
        </div>

        @foreach ([
            ['การยืมตามประเภทผู้ใช้', 'bi-person-badge', $byRole, true],
            ['การยืมตามคณะ / หน่วยงาน', 'bi-building', $byFaculty, false],
        ] as [$title, $icon, $rows, $showUsers])
            @php $max = max(1, $rows->max('total') ?? 1); @endphp
            <div class="card">
                <div class="card-head"><h2 class="card-title"><i class="bi {{ $icon }} text-gold-600"></i> {{ $title }}</h2></div>
                <div class="card-body space-y-3">
                    @forelse ($rows as $row)
                        <div>
                            <div class="mb-1 flex justify-between gap-2 text-sm">
                                <span class="truncate text-ink-700" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                <span class="shrink-0 font-semibold tabular-nums">
                                    {{ number_format($row['total']) }} ครั้ง
                                    @if ($showUsers)<span class="font-normal text-ink-400">· {{ $row['users'] }} คน</span>@endif
                                </span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-ink-50">
                                <div class="h-full rounded-full bg-[#4061b8]" style="width: {{ $row['total'] / $max * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="py-10 text-center text-sm text-ink-400">ไม่มีการยืมในช่วงนี้</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </section>

    {{-- ===== หนังสือยอดนิยม + ช่วงเวลาใช้งาน ===== --}}
    <section class="mb-6 grid gap-6 lg:grid-cols-5">
        <div class="card lg:col-span-3">
            <div class="card-head">
                <h2 class="card-title"><i class="bi bi-trophy text-gold-600"></i> หนังสือที่ถูกยืมมากที่สุดในช่วงนี้</h2>
            </div>
            @if ($topBooks->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-ink-400">ไม่มีการยืมในช่วงนี้</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table-clean">
                        <thead><tr><th class="w-10">#</th><th>หนังสือ</th><th class="text-right">ยืม (ครั้ง)</th><th class="text-right">ผู้ยืม (คน)</th></tr></thead>
                        <tbody>
                            @foreach ($topBooks as $i => $book)
                                <tr>
                                    <td class="font-display font-bold text-ink-300">{{ $i + 1 }}</td>
                                    <td>
                                        <a href="{{ route('ebooks.show', $book['id']) }}" class="line-clamp-1 font-semibold hover:text-royal-700">{{ $book['title'] }}</a>
                                        <div class="text-xs text-ink-400">{{ $book['category'] }}</div>
                                    </td>
                                    <td class="text-right font-semibold tabular-nums">{{ number_format($book['borrows']) }}</td>
                                    <td class="text-right tabular-nums text-ink-500">{{ number_format($book['borrowers']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="card lg:col-span-2">
            <div class="card-head">
                <h2 class="card-title"><i class="bi bi-calendar-week text-gold-600"></i> วันและช่วงเวลาที่มีการยืม</h2>
            </div>
            <div class="card-body overflow-x-auto">
                <table class="w-full border-separate border-spacing-[3px] text-[11px]">
                    <thead>
                        <tr>
                            <th></th>
                            @foreach ($timeSlots as $ts)
                                <th class="pb-1 font-medium text-ink-400">{{ $ts }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dayOrder as $dow)
                            <tr>
                                <th class="pr-1 text-left font-medium text-ink-500">{{ ThaiDate::DAYS_SHORT[$dow] }}</th>
                                @foreach ($heatmap[$dow] as $slotIndex => $value)
                                    @php $alpha = $value ? 0.12 + 0.88 * ($value / $heatMax) : 0; @endphp
                                    <td class="h-7 rounded text-center font-semibold tabular-nums {{ $alpha > 0.55 ? 'text-white' : 'text-ink-700' }}"
                                        style="background: {{ $value ? 'rgba(64,97,184,'.round($alpha, 2).')' : '#f2f5fb' }}"
                                        title="{{ ThaiDate::DAYS_SHORT[$dow] }} {{ $timeSlots[$slotIndex] }} น. : {{ $value }} ครั้ง">
                                        {{ $value ?: '' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-2 text-xs text-ink-400">ใช้วางแผนเวลาให้บริการและบำรุงรักษาระบบ (สีเข้ม = ยืมมาก)</p>
            </div>
        </div>
    </section>

    {{-- ===== ภาพรวมระบบ ===== --}}
    <h2 class="section-title mb-3 mt-10">ภาพรวมระบบ ณ ปัจจุบัน</h2>
    <section class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['Ebook ทั้งหมด', $overview['ebooks'], 'เปิดบริการ '.number_format($overview['ebooksActive'])],
            ['หมวดหมู่', $overview['categories'], 'หมวดหลัก '.$overview['mainCategories']],
            ['เข้าใช้งานแล้ว', $overview['loggedIn'], 'จากผู้ใช้ '.number_format($overview['users']).' คน'.($overview['online'] !== null ? ' · ออนไลน์ '.$overview['online'] : '')],
            ['การยืมสะสม', $overview['borrowsAll'], 'ตั้งแต่เปิดระบบ'],
            ['การเปิดอ่านสะสม', $overview['views'], 'ทุกเล่มรวมกัน'],
            ['อัตราการใช้งาน', $overview['utilization'].'%', 'ของคอลเลกชัน'],
        ] as [$label, $value, $sub])
            <div class="stat">
                <div class="stat-label">{{ $label }}</div>
                <div class="stat-value text-2xl">{{ is_numeric($value) ? number_format($value) : $value }}</div>
                <div class="text-xs text-ink-400">{{ $sub }}</div>
            </div>
        @endforeach
    </section>

    <section class="mb-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-head"><h2 class="card-title"><i class="bi bi-collection text-gold-600"></i> จำนวน Ebook ตามหมวดหมู่หลัก</h2></div>
            <div class="card-body">
                <div class="relative" style="height: {{ max(180, $collectionByCategory->count() * 32) }}px"><canvas id="collectionChart" role="img" aria-label="จำนวน Ebook ตามหมวด"></canvas></div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="card-head"><h2 class="card-title"><i class="bi bi-people text-gold-600"></i> ผู้ใช้ที่เข้าใช้งานแล้ว</h2></div>
                <div class="card-body space-y-2 text-sm">
                    <div class="flex justify-between text-xs text-ink-400"><span>ประเภท</span><span>เข้าใช้แล้ว / ทั้งหมด</span></div>
                    @foreach ($usersByRole as $role => $row)
                        <div class="flex justify-between"><span class="text-ink-600">{{ $role }}</span><span class="font-semibold tabular-nums">{{ number_format($row['loggedIn']) }} / {{ number_format($row['total']) }}</span></div>
                    @endforeach
                    <a href="{{ route('users.index') }}" class="no-print mt-2 block text-xs font-semibold text-royal-700 hover:underline">จัดการสิทธิ์ผู้ใช้ →</a>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2 class="card-title"><i class="bi bi-clipboard-check text-gold-600"></i> ความสมบูรณ์ของข้อมูล</h2></div>
                <div class="card-body space-y-2 text-sm">
                    @foreach ([
                        ['ยังไม่มีปก (ใช้หน้าแรก PDF แทน)', $dataHealth['noCover'], false],
                        ['ยังไม่มีทั้งปกและภาพหน้าแรก', $dataHealth['noCoverAtAll'], true],
                        ['ยังไม่มีไฟล์ตัวอย่าง 5 หน้า', $dataHealth['noPreview'], true],
                        ['ยังไม่มีบทคัดย่อ', $dataHealth['noDescription'], false],
                        ['ยังไม่มีคำสำคัญ', $dataHealth['noKeywords'], false],
                    ] as [$label, $value, $important])
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-ink-600">{{ $label }}</span>
                            <span class="badge {{ $value === 0 ? 'badge-green' : ($important ? 'badge-amber' : 'badge-ink') }}">{{ $value === 0 ? 'ครบ' : number_format($value).' เล่ม' }}</span>
                        </div>
                    @endforeach
                    @if ($dataHealth['noCoverAtAll'] || $dataHealth['noPreview'])
                        <a href="{{ route('ebooks.index') }}" class="mt-2 block text-xs font-semibold text-royal-700 hover:underline">เปิดหน้ารายการ Ebook เพื่อให้ระบบสร้างให้อัตโนมัติ →</a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- ===== รายการที่ต้องติดตาม (ไม่แสดงชื่อผู้ยืม) ===== --}}
    <section class="card mb-6">
        <div class="card-head">
            <h2 class="card-title"><i class="bi bi-bell text-gold-600"></i> รายการที่ครบกำหนด / เลยกำหนด</h2>
            <a href="{{ route('borrows.index', ['status' => 'borrowed']) }}" class="no-print text-xs font-semibold text-royal-700 hover:underline">จัดการการยืม →</a>
        </div>
        @if ($attention->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-ink-400"><i class="bi bi-check2-circle text-emerald-600"></i> ไม่มีรายการที่ต้องติดตาม</p>
        @else
            <div class="overflow-x-auto">
                <table class="table-clean">
                    <thead><tr><th>หนังสือ</th><th>กลุ่มผู้ยืม</th><th>กำหนดคืน</th><th class="text-right">สถานะ</th></tr></thead>
                    <tbody>
                        @foreach ($attention as $row)
                            <tr>
                                <td class="font-medium"><span class="line-clamp-1">{{ $row['title'] }}</span></td>
                                <td class="text-ink-500">{{ $row['who'] }}</td>
                                <td class="whitespace-nowrap">{{ $row['due'] }}@if ($row['renewed']) <span class="text-xs text-ink-400">(ต่ออายุ {{ $row['renewed'] }})</span>@endif</td>
                                <td class="text-right">
                                    @if ($row['days'] < 0)
                                        <span class="badge badge-red">เลยกำหนด {{ abs($row['days']) }} วัน</span>
                                    @elseif ($row['days'] === 0)
                                        <span class="badge badge-amber">ครบกำหนดวันนี้</span>
                                    @else
                                        <span class="badge badge-amber">อีก {{ $row['days'] }} วัน</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <p class="flex items-center gap-2 text-xs text-ink-400">
        <i class="bi bi-shield-lock"></i>
        รายงานนี้แสดงเฉพาะข้อมูลสถิติแบบรวม ไม่ระบุชื่อหรือข้อมูลส่วนบุคคลของผู้ยืม (สอดคล้อง พ.ร.บ.คุ้มครองข้อมูลส่วนบุคคล)
        · ออกรายงาน {{ ThaiDate::dateTime(now()) }}
    </p>

    <script type="application/json" id="dashboard-data">@json($chartData)</script>
</div>
@endsection
