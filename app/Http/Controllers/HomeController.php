<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use App\Models\BorrowPolicy;
use App\Models\Category;
use App\Models\Ebook;
use Illuminate\Support\Facades\Auth;

/**
 * หน้าแรกของผู้ใช้ (นักศึกษา อาจารย์ บุคลากร ศิษย์เก่า) และโหมด "ดูแบบผู้ใช้" ของผู้ดูแลระบบ
 * (ย้ายมาจาก closure ใน routes/web.php เดิม ข้อมูลที่ส่งให้ view ยังใช้ชื่อเดิม)
 */
class HomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $latestEbooks = Ebook::available()
            ->with('category')
            ->latest()
            ->take(12)
            ->get();

        $popularBooks = Ebook::available()
            ->withCount('borrows')
            ->whereHas('borrows')
            ->orderByDesc('borrows_count')
            ->orderByDesc('view_count')
            ->take(6)
            ->get();

        $mainCategories = Category::whereNull('parent_id')
            ->where('status', 1)
            ->orderBy('code')
            ->get();

        // จำนวนหนังสือในแต่ละหมวดหลัก (รวมหมวดย่อย)
        $categoryCounts = Ebook::available()
            ->join('categories', 'ebooks.category_id', '=', 'categories.id')
            ->selectRaw('COALESCE(categories.parent_id, categories.id) as main_id, COUNT(*) as total')
            ->groupBy('main_id')
            ->pluck('total', 'main_id');

        // ข้อมูลส่วนตัวของผู้ใช้
        $myBorrows = Borrow::with('ebook')
            ->where('user_id', $user->id)
            ->where('status', 'borrowed')
            ->whereDate('due_date', '>=', today())
            ->orderBy('due_date')
            ->get();

        $policy = BorrowPolicy::where('role', $user->role)->where('active', 1)->first();

        $totalBorrowed = Borrow::where('user_id', $user->id)->count();

        $totalEbooks = Ebook::available()->count();

        return view('home', compact(
            'latestEbooks',
            'popularBooks',
            'mainCategories',
            'categoryCounts',
            'myBorrows',
            'policy',
            'totalBorrowed',
            'totalEbooks'
        ));
    }
}
