<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use App\Models\Category;
use App\Models\Ebook;
use App\Models\User;
use App\Services\EbookFileService;
use App\Support\ThaiDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * แดชบอร์ดรายงานสำหรับผู้ดูแลระบบ
 *
 * - เลือกช่วงรายงานได้: รายวัน / รายเดือน / รายปี (พ.ศ.) / ปีงบประมาณ / กำหนดช่วงเอง (ย้อนหลังได้สูงสุด 10 ปี)
 * - เปรียบเทียบกับช่วงก่อนหน้าที่มีความยาวเท่ากัน
 * - ไม่แสดงชื่อผู้ยืม: ใช้เฉพาะข้อมูลรวม (จำนวน, บทบาท, คณะ, หน่วยงาน)
 * - คิวรีไม่ใช้ฟังก์ชันเฉพาะของ MySQL จึงทำงานได้ทั้ง MySQL/MariaDB และ SQLite
 */
class DashboardController extends Controller
{
    private const ROLE_NAMES = [
        'student' => 'นักศึกษา',
        'teacher' => 'อาจารย์',
        'staff' => 'บุคลากร',
        'alumni' => 'ศิษย์เก่า',
        'admin' => 'ผู้ดูแลระบบ',
    ];

    public function index(Request $request)
    {
        // ผู้ใช้ทั่วไปไปหน้าแรกห้องสมุด
        if (! Auth::user()->isAdmin()) {
            return redirect()->route('user.home');
        }

        // แอดมินที่อยู่ในโหมดผู้ใช้
        if (session('view_mode') === 'user') {
            return redirect()->route('library');
        }

        $report = $this->buildReport($request);

        return view('dashboard', $report);
    }

    /**
     * ส่งออกรายงาน (CSV, เปิดใน Excel ได้ภาษาไทยถูกต้อง)
     */
    public function export(Request $request)
    {
        $r = $this->buildReport($request);

        $filename = 'ebook-report-'.$r['startDate']->format('Ymd').'-'.$r['endDate']->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($r) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM สำหรับ Excel

            fputcsv($out, ['รายงานระบบห้องสมุด Ebook มหาวิทยาลัยเทคโนโลยีราชมงคลสุวรรณภูมิ']);
            fputcsv($out, [$r['reportTitle'], $r['rangeLabel']]);
            fputcsv($out, ['ออกรายงานเมื่อ', ThaiDate::dateTime(now())]);
            fputcsv($out, []);

            fputcsv($out, ['ตัวชี้วัด', 'ช่วงนี้', 'ช่วงก่อนหน้า', 'เปลี่ยนแปลง (%)']);
            foreach ($r['kpis'] as $kpi) {
                fputcsv($out, [$kpi['label'], $kpi['value'], $kpi['previous'] ?? '-', $kpi['previous'] ? $kpi['change'] : '-']);
            }
            fputcsv($out, []);

            fputcsv($out, ['แนวโน้ม', 'ยืม', 'คืน']);
            foreach ($r['trend']['labels'] as $i => $label) {
                fputcsv($out, [$label, $r['trend']['borrows'][$i], $r['trend']['returns'][$i]]);
            }
            fputcsv($out, []);

            fputcsv($out, ['หนังสือที่ถูกยืมมากที่สุด', 'หมวดหมู่', 'จำนวนครั้งที่ยืม', 'จำนวนผู้ยืม (คน)']);
            foreach ($r['topBooks'] as $row) {
                fputcsv($out, [$row['title'], $row['category'], $row['borrows'], $row['borrowers']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['การยืมตามหมวดหมู่หลัก', 'จำนวนครั้ง']);
            foreach ($r['byCategory'] as $row) {
                fputcsv($out, [$row['label'], $row['total']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['การยืมตามประเภทผู้ใช้', 'จำนวนครั้ง', 'จำนวนผู้ยืม (คน)']);
            foreach ($r['byRole'] as $row) {
                fputcsv($out, [$row['label'], $row['total'], $row['users']]);
            }
            fputcsv($out, []);

            fputcsv($out, ['การยืมตามคณะ/หน่วยงาน', 'จำนวนครั้ง']);
            foreach ($r['byFaculty'] as $row) {
                fputcsv($out, [$row['label'], $row['total']]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ======================================================================
     | สร้างข้อมูลรายงาน
     * ====================================================================== */

    private function buildReport(Request $request): array
    {
        [$period, $startDate, $endDate, $reportTitle, $inputs] = $this->resolvePeriod($request);

        $days = $startDate->diffInDays($endDate) + 1;
        $prevEnd = $startDate->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1);

        $rangeLabel = $startDate->isSameDay($endDate)
            ? ThaiDate::long($startDate)
            : ThaiDate::short($startDate).' – '.ThaiDate::short($endDate);

        /* ---------- ข้อมูลการยืมในช่วงรายงาน (ไม่ดึงชื่อผู้ใช้) ---------- */

        $borrows = $this->borrowRows($startDate, $endDate);
        $prevBorrows = $this->borrowRows($prevStart, $prevEnd);

        $returnsCount = $this->countBetween(Borrow::query(), 'return_date', $startDate, $endDate);
        $prevReturnsCount = $this->countBetween(Borrow::query(), 'return_date', $prevStart, $prevEnd);

        $newBooks = $this->countBetween(Ebook::query(), 'created_at', $startDate, $endDate, true);
        $prevNewBooks = $this->countBetween(Ebook::query(), 'created_at', $prevStart, $prevEnd, true);

        // ผู้ใช้ที่เข้าสู่ระบบครั้งแรกในช่วงนี้ (นับคนละครั้งเดียว — เวลาเข้าครั้งแรกเก็บใน email_verified_at)
        $newUsers = $this->countBetween(User::query(), 'email_verified_at', $startDate, $endDate, true);
        $prevNewUsers = $this->countBetween(User::query(), 'email_verified_at', $prevStart, $prevEnd, true);

        $returnedRows = Borrow::query()
            ->whereNotNull('return_date')
            ->whereBetween('return_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['borrow_date', 'return_date']);

        $avgLoanDays = $returnedRows->isEmpty()
            ? 0
            : round($returnedRows->avg(fn ($b) => $b->borrow_date->diffInDays($b->return_date)), 1);

        $uniqueBorrowers = $borrows->pluck('user_id')->unique()->count();
        $uniqueBooks = $borrows->pluck('ebook_id')->unique()->count();

        $kpis = [
            'borrows' => $this->kpi('การยืม (ครั้ง)', $borrows->count(), $prevBorrows->count()),
            'returns' => $this->kpi('การคืน (ครั้ง)', $returnsCount, $prevReturnsCount),
            'borrowers' => $this->kpi('ผู้ใช้บริการ (คน)', $uniqueBorrowers, $prevBorrows->pluck('user_id')->unique()->count()),
            'books' => $this->kpi('หนังสือที่ถูกยืม (เล่ม)', $uniqueBooks, $prevBorrows->pluck('ebook_id')->unique()->count()),
            'renewals' => $this->kpi('การต่ออายุ (ครั้ง)', (int) $borrows->sum('renew_count'), (int) $prevBorrows->sum('renew_count')),
            'newBooks' => $this->kpi('Ebook ใหม่ (เล่ม)', $newBooks, $prevNewBooks),
            'newUsers' => $this->kpi('เข้าใช้ระบบครั้งแรก (คน)', $newUsers, $prevNewUsers),
            'avgLoan' => $this->kpi('ระยะเวลายืมเฉลี่ย (วัน)', $avgLoanDays, null),
        ];

        /* ---------- ภาพรวมทั้งระบบ (ณ ปัจจุบัน) ---------- */

        $ebookTotal = Ebook::count();
        $ebookActive = Ebook::where('status', 1)->count();
        $everBorrowed = Borrow::distinct()->count('ebook_id');

        $overview = [
            'ebooks' => $ebookTotal,
            'ebooksActive' => $ebookActive,
            'ebooksInactive' => $ebookTotal - $ebookActive,
            'categories' => Category::count(),
            'mainCategories' => Category::whereNull('parent_id')->count(),
            'users' => User::count(),
            'usersInactive' => User::where('status', 0)->count(),
            'loggedIn' => User::whereNotNull('email_verified_at')->count(),
            'online' => $this->onlineUsers(),
            'borrowsAll' => Borrow::count(),
            'views' => (int) Ebook::sum('view_count'),
            'utilization' => $ebookTotal ? round($everBorrowed / $ebookTotal * 100, 1) : 0,
            'neverBorrowed' => max(0, $ebookTotal - $everBorrowed),
        ];

        // สถานะการยืม ณ วันนี้
        $today = today()->toDateString();

        $loanStatus = [
            'active' => Borrow::where('status', 'borrowed')->whereDate('due_date', '>=', $today)->count(),
            'dueSoon' => Borrow::where('status', 'borrowed')
                ->whereBetween('due_date', [$today, today()->addDays(3)->toDateString()])
                ->count(),
            'overdue' => Borrow::where('status', 'borrowed')->whereDate('due_date', '<', $today)->count()
                + Borrow::where('status', 'expired')->whereNull('return_date')->count(),
            'returned' => Borrow::where('status', 'returned')->count(),
        ];

        /* ---------- กราฟแนวโน้ม ---------- */

        $trend = $this->buildTrend($startDate, $endDate, $borrows, $period);

        /* ---------- การยืมตามมิติต่าง ๆ (ไม่ระบุตัวบุคคล) ---------- */

        $byRole = $borrows->groupBy('role')->map(fn ($rows, $role) => [
            'label' => self::ROLE_NAMES[$role] ?? 'อื่น ๆ',
            'total' => $rows->count(),
            'users' => $rows->pluck('user_id')->unique()->count(),
        ])->sortByDesc('total')->values();

        $byFaculty = $this->groupCount($borrows, 'faculty', 10);
        $byDepartment = $this->groupCount($borrows, 'department', 10);

        $mainCategories = Category::whereNull('parent_id')->get(['id', 'code', 'name'])->keyBy('id');
        $categoryParents = Category::pluck('parent_id', 'id');

        $mainOf = fn ($categoryId) => $categoryParents[$categoryId] ?? $categoryId;

        $byCategory = $borrows->groupBy(fn ($r) => $mainOf($r->category_id))
            ->map(fn ($rows, $mainId) => [
                'label' => isset($mainCategories[$mainId])
                    ? $mainCategories[$mainId]->code.' '.$mainCategories[$mainId]->name
                    : 'ไม่ระบุหมวด',
                'total' => $rows->count(),
            ])->sortByDesc('total')->values()->take(10);

        // วัน × ช่วงเวลา (ใช้เวลาที่บันทึกการยืมจริง created_at)
        $heatmap = array_fill(0, 7, array_fill(0, 8, 0)); // 8 ช่วง ช่วงละ 3 ชั่วโมง
        foreach ($borrows as $row) {
            if ($row->created_at) {
                $t = Carbon::parse($row->created_at)->setTimezone(config('app.timezone'));
                $heatmap[$t->dayOfWeek][intdiv($t->hour, 3)]++;
            }
        }

        /* ---------- หนังสือยอดนิยม ---------- */

        $topBookRows = $borrows->groupBy('ebook_id')
            ->map(fn ($rows, $id) => ['id' => $id, 'borrows' => $rows->count(), 'borrowers' => $rows->pluck('user_id')->unique()->count()])
            ->sortByDesc('borrows')
            ->take(10);

        $bookInfo = Ebook::with('category:id,name')
            ->whereIn('id', $topBookRows->pluck('id'))
            ->get()
            ->keyBy('id');

        $topBooks = $topBookRows->filter(fn ($r) => isset($bookInfo[$r['id']]))
            ->map(fn ($r) => $r + [
                'title' => $bookInfo[$r['id']]->title,
                'author' => $bookInfo[$r['id']]->author,
                'category' => $bookInfo[$r['id']]->category->name ?? '-',
                'cover' => $bookInfo[$r['id']]->cover_url,
            ])->values();

        $topViewed = Ebook::with('category:id,name')
            ->where('view_count', '>', 0)
            ->orderByDesc('view_count')
            ->take(8)
            ->get();

        /* ---------- คอลเลกชัน ---------- */

        $collectionByCategory = Ebook::join('categories', 'ebooks.category_id', '=', 'categories.id')
            ->selectRaw('COALESCE(categories.parent_id, categories.id) as main_id, COUNT(*) as total')
            ->groupBy('main_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'label' => isset($mainCategories[$r->main_id])
                    ? $mainCategories[$r->main_id]->code.' '.$mainCategories[$r->main_id]->name
                    : 'ไม่ระบุหมวด',
                'total' => (int) $r->total,
            ]);

        $usersByRole = User::selectRaw('role, COUNT(*) as total, SUM(CASE WHEN email_verified_at IS NULL THEN 0 ELSE 1 END) as logged_in')
            ->groupBy('role')
            ->get()
            ->mapWithKeys(fn ($r) => [self::ROLE_NAMES[$r->role] ?? $r->role => ['total' => (int) $r->total, 'loggedIn' => (int) $r->logged_in]]);

        /* ---------- รายการที่ต้องติดตาม (ไม่แสดงชื่อผู้ยืม) ---------- */

        $attention = Borrow::query()
            ->join('ebooks', 'borrows.ebook_id', '=', 'ebooks.id')
            ->join('users', 'borrows.user_id', '=', 'users.id')
            ->where('borrows.status', 'borrowed')
            ->where('borrows.due_date', '<=', today()->addDays(3)->toDateString())
            ->orderBy('borrows.due_date')
            ->limit(10)
            ->get([
                'borrows.id',
                'borrows.due_date',
                'borrows.renew_count',
                'ebooks.title',
                'users.role',
                'users.faculty',
            ])
            ->map(function ($row) {
                $due = Carbon::parse($row->due_date);

                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'due' => ThaiDate::short($due),
                    'days' => (int) today()->diffInDays($due, false),
                    'who' => (self::ROLE_NAMES[$row->role] ?? 'ผู้ใช้').($row->faculty ? ' · '.$row->faculty : ''),
                    'renewed' => (int) $row->renew_count,
                ];
            });

        /* ---------- ความสมบูรณ์ของข้อมูล ---------- */

        $files = app(EbookFileService::class);
        $allBooks = Ebook::get(['id', 'cover', 'preview_file', 'description', 'keywords', 'isbn']);

        $dataHealth = [
            'noCover' => $allBooks->filter(fn ($b) => ! $files->hasUploadedCover($b))->count(),
            'noCoverAtAll' => $allBooks->filter(fn ($b) => ! $files->hasUploadedCover($b) && ! $files->hasAutoCover($b))->count(),
            'noPreview' => $allBooks->whereNull('preview_file')->count(),
            'noDescription' => $allBooks->filter(fn ($b) => blank($b->description))->count(),
            'noKeywords' => $allBooks->filter(fn ($b) => blank($b->keywords))->count(),
        ];

        return array_merge($inputs, compact(
            'period',
            'startDate',
            'endDate',
            'reportTitle',
            'rangeLabel',
            'prevStart',
            'prevEnd',
            'kpis',
            'overview',
            'loanStatus',
            'trend',
            'byRole',
            'byFaculty',
            'byDepartment',
            'byCategory',
            'heatmap',
            'topBooks',
            'topViewed',
            'collectionByCategory',
            'usersByRole',
            'attention',
            'dataHealth'
        ));
    }

    /**
     * อ่านค่าตัวกรอง แล้วคืนช่วงวันที่ของรายงาน
     */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->query('period', 'month');
        $allowed = ['day', 'month', 'year', 'fiscal', 'range', 'last7', 'last30'];

        if ($period === 'today') {
            $period = 'day'; // รองรับลิงก์เดิม
        }
        if ($period === 'custom') {
            $period = 'range';
        }
        if (! in_array($period, $allowed, true)) {
            $period = 'month';
        }

        $today = today();
        $minDate = Carbon::create(2000, 1, 1);

        $safeDate = function ($value, Carbon $fallback) use ($minDate, $today) {
            try {
                $d = Carbon::createFromFormat('!Y-m-d', (string) $value);
            } catch (\Throwable $e) {
                return $fallback->copy();
            }

            if (! $d || $d->lt($minDate) || $d->gt($today->copy()->addYear())) {
                return $fallback->copy();
            }

            return $d;
        };

        $selectedDay = $safeDate($request->query('day'), $today)->toDateString();

        $selectedMonth = $request->query('month', $today->format('Y-m'));
        if (! is_string($selectedMonth) || ! preg_match('/^(20\d{2})-(0[1-9]|1[0-2])$/', $selectedMonth)) {
            $selectedMonth = $today->format('Y-m');
        }

        // ปีรับได้ทั้ง ค.ศ. และ พ.ศ.
        $selectedYear = (int) $request->query('year', $today->year);
        if ($selectedYear > 2400) {
            $selectedYear -= 543;
        }
        if ($selectedYear < 2000 || $selectedYear > $today->year + 1) {
            $selectedYear = $today->year;
        }

        // ปีงบประมาณ (พ.ศ.) เริ่ม 1 ต.ค. ของปีก่อนหน้า
        $currentFiscal = ($today->month >= 10 ? $today->year + 1 : $today->year) + 543;
        $selectedFiscal = (int) $request->query('fiscal', $currentFiscal);
        if ($selectedFiscal < 2543 || $selectedFiscal > $currentFiscal + 1) {
            $selectedFiscal = $currentFiscal;
        }

        $rangeStart = $safeDate($request->query('start_date'), $today->copy()->subDays(29));
        $rangeEnd = $safeDate($request->query('end_date'), $today);

        switch ($period) {
            case 'day':
                $start = Carbon::parse($selectedDay);
                $end = $start->copy();
                $title = 'รายงานประจำวัน';
                break;

            case 'last7':
                $start = $today->copy()->subDays(6);
                $end = $today->copy();
                $title = 'รายงาน 7 วันล่าสุด';
                break;

            case 'last30':
                $start = $today->copy()->subDays(29);
                $end = $today->copy();
                $title = 'รายงาน 30 วันล่าสุด';
                break;

            case 'year':
                $start = Carbon::create($selectedYear, 1, 1);
                $end = $start->copy()->endOfYear()->startOfDay();
                $title = 'รายงานประจำปี พ.ศ. '.($selectedYear + 543);
                break;

            case 'fiscal':
                $start = Carbon::create($selectedFiscal - 543 - 1, 10, 1);
                $end = Carbon::create($selectedFiscal - 543, 9, 30);
                $title = 'รายงานปีงบประมาณ พ.ศ. '.$selectedFiscal;
                break;

            case 'range':
                if ($rangeEnd->lt($rangeStart)) {
                    [$rangeStart, $rangeEnd] = [$rangeEnd, $rangeStart];
                }
                if ($rangeStart->diffInDays($rangeEnd) > 3660) {
                    $rangeStart = $rangeEnd->copy()->subDays(3660);
                }
                $start = $rangeStart->copy();
                $end = $rangeEnd->copy();
                $title = 'รายงานตามช่วงวันที่';
                break;

            case 'month':
            default:
                $start = Carbon::createFromFormat('!Y-m', $selectedMonth)->startOfMonth();
                $end = $start->copy()->endOfMonth()->startOfDay();
                $title = 'รายงานประจำเดือน '.ThaiDate::monthYear($start);
                break;
        }

        $inputs = [
            'selectedDay' => $selectedDay,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedFiscal' => $selectedFiscal,
            'currentFiscal' => $currentFiscal,
            'startDateInput' => $start->toDateString(),
            'endDateInput' => $end->toDateString(),
            'yearOptions' => range($today->year, max(2020, $today->year - 10)),
        ];

        return [$period, $start->startOfDay(), $end->startOfDay(), $title, $inputs];
    }

    /** จำนวนผู้ใช้ที่ออนไลน์ใน 15 นาทีล่าสุด (เมื่อใช้ SESSION_DRIVER=database) */
    private function onlineUsers(): ?int
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        try {
            return (int) DB::table(config('session.table', 'sessions'))
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
                ->distinct()
                ->count('user_id');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** รายการยืมในช่วง พร้อมข้อมูลกลุ่มผู้ใช้ (ไม่มีชื่อ/อีเมล) */
    private function borrowRows(Carbon $start, Carbon $end): Collection
    {
        return DB::table('borrows')
            ->join('users', 'borrows.user_id', '=', 'users.id')
            ->join('ebooks', 'borrows.ebook_id', '=', 'ebooks.id')
            ->whereBetween('borrows.borrow_date', [$start->toDateString(), $end->toDateString()])
            ->get([
                'borrows.user_id',
                'borrows.ebook_id',
                'borrows.borrow_date',
                'borrows.renew_count',
                'borrows.created_at',
                'users.role',
                'users.faculty',
                'users.department',
                'ebooks.category_id',
            ]);
    }

    private function countBetween($query, string $column, Carbon $start, Carbon $end, bool $timestamp = false): int
    {
        if ($timestamp) {
            return $query->whereBetween($column, [
                $start->copy()->startOfDay()->toDateTimeString(),
                $end->copy()->endOfDay()->toDateTimeString(),
            ])->count();
        }

        return $query->whereNotNull($column)
            ->whereBetween($column, [$start->toDateString(), $end->toDateString()])
            ->count();
    }

    private function kpi(string $label, $value, $previous): array
    {
        $change = null;

        if ($previous !== null) {
            $change = $previous == 0
                ? ($value > 0 ? 100 : 0)
                : round(($value - $previous) / $previous * 100, 1);
        }

        return compact('label', 'value', 'previous', 'change');
    }

    private function groupCount(Collection $rows, string $field, int $limit): Collection
    {
        return $rows->filter(fn ($r) => filled($r->{$field}))
            ->groupBy($field)
            ->map(fn ($g, $label) => ['label' => $label, 'total' => $g->count()])
            ->sortByDesc('total')
            ->values()
            ->take($limit);
    }

    /**
     * จัดกลุ่มข้อมูลกราฟ: 1 วัน → รายชั่วโมง, ≤ 62 วัน → รายวัน, ≤ 3 ปี → รายเดือน, มากกว่านั้น → รายปี
     */
    private function buildTrend(Carbon $start, Carbon $end, Collection $borrows, string $period): array
    {
        $days = $start->diffInDays($end) + 1;

        $returnDates = Borrow::whereNotNull('return_date')
            ->whereBetween('return_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('return_date')
            ->map(fn ($d) => Carbon::parse($d));

        $borrowDates = $borrows->map(fn ($r) => Carbon::parse($r->borrow_date));

        if ($days === 1) {
            $labels = [];
            $b = array_fill(0, 24, 0);
            $ret = array_fill(0, 24, 0);

            foreach ($borrows as $row) {
                if ($row->created_at) {
                    $b[Carbon::parse($row->created_at)->hour]++;
                }
            }

            $returnTimes = Borrow::whereNotNull('return_date')
                ->whereDate('return_date', $start->toDateString())
                ->pluck('updated_at');

            foreach ($returnTimes as $t) {
                if ($t) {
                    $ret[Carbon::parse($t)->hour]++;
                }
            }

            for ($h = 0; $h < 24; $h++) {
                $labels[] = sprintf('%02d:00', $h);
            }

            return ['labels' => $labels, 'borrows' => $b, 'returns' => $ret, 'unit' => 'ชั่วโมง'];
        }

        if ($days <= 62) {
            $unit = 'วัน';
            $key = fn (Carbon $d) => $d->format('Y-m-d');
            $cursor = $start->copy();
            $buckets = [];
            while ($cursor->lte($end)) {
                $buckets[$cursor->format('Y-m-d')] = $cursor->day.' '.ThaiDate::MONTHS_SHORT[$cursor->month];
                $cursor->addDay();
            }
        } elseif ($days <= 1100) {
            $unit = 'เดือน';
            $key = fn (Carbon $d) => $d->format('Y-m');
            $cursor = $start->copy()->startOfMonth();
            $buckets = [];
            while ($cursor->lte($end)) {
                $buckets[$cursor->format('Y-m')] = ThaiDate::MONTHS_SHORT[$cursor->month].' '.substr((string) ($cursor->year + 543), 2);
                $cursor->addMonth();
            }
        } else {
            $unit = 'ปี';
            $key = fn (Carbon $d) => $d->format('Y');
            $buckets = [];
            for ($y = $start->year; $y <= $end->year; $y++) {
                $buckets[(string) $y] = 'พ.ศ. '.($y + 543);
            }
        }

        $bCount = array_fill_keys(array_keys($buckets), 0);
        $rCount = array_fill_keys(array_keys($buckets), 0);

        foreach ($borrowDates as $d) {
            $k = $key($d);
            if (isset($bCount[$k])) {
                $bCount[$k]++;
            }
        }

        foreach ($returnDates as $d) {
            $k = $key($d);
            if (isset($rCount[$k])) {
                $rCount[$k]++;
            }
        }

        return [
            'labels' => array_values($buckets),
            'borrows' => array_values($bCount),
            'returns' => array_values($rCount),
            'unit' => $unit,
        ];
    }
}
