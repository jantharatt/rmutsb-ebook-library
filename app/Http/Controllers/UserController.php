<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    /**
     * ตรวจสอบสิทธิ์ Admin
     */
    private function checkAdmin()
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าใช้งานหน้านี้');
        }
    }


    /**
     * แสดงรายการผู้ใช้
     */
    public function index(Request $request)
    {
        $this->checkAdmin();

        $search = trim((string) $request->search);
        $role = $request->query('role');
        $login = $request->query('login'); // yes = เคยเข้าสู่ระบบ, no = ยังไม่เคย

        $users = User::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('faculty', 'like', "%{$search}%");
                });
            })
            ->when(in_array($role, ['student', 'teacher', 'staff', 'alumni', 'admin'], true), fn ($q) => $q->where('role', $role))
            ->when($login === 'yes', fn ($q) => $q->whereNotNull('email_verified_at'))
            ->when($login === 'no', fn ($q) => $q->whereNull('email_verified_at'))
            ->orderByRaw('email_verified_at is null')
            ->orderByDesc('email_verified_at')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        // สรุปจำนวนผู้ใช้ (นับคนที่เคยเข้าสู่ระบบเพียงครั้งเดียว)
        $stats = [
            'total' => User::count(),
            'loggedIn' => User::whereNotNull('email_verified_at')->count(),
            'today' => User::whereDate('email_verified_at', today())->count(),
            'byRole' => User::selectRaw('role, COUNT(*) as total, SUM(CASE WHEN email_verified_at IS NULL THEN 0 ELSE 1 END) as logged_in')
                ->groupBy('role')
                ->get()
                ->keyBy('role'),
        ];

        return view('users.index', compact('users', 'search', 'role', 'login', 'stats'));
    }

    public function create()
{
    $this->checkAdmin();

    return view('users.create');
}

/**
 * บันทึกผู้ใช้ใหม่
 */
public function store(Request $request)
{
    $this->checkAdmin();


    $validated = $request->validate([

        'name' => [
            'required',
            'string',
            'max:255'
        ],

        'email' => [
            'required',
            'email',
            'unique:users,email',
            function ($attribute, $value, $fail) {
                if (! \App\Support\UniversityAccount::isUniversityEmail($value)) {
                    $fail('ต้องเป็นอีเมล @'.\App\Support\UniversityAccount::domain());
                }
            },
        ],

        'password' => [
            'required',
            'min:8'
        ],


        'role' => [
            'required',
            'in:student,teacher,staff,alumni,admin'
        ],


        'faculty' => [
            'nullable',
            'string',
            'max:255'
        ],


        'department' => [
            'nullable',
            'string',
            'max:255'
        ],


    ]);



    User::create([

        'name' => $validated['name'],

        'email' => strtolower($validated['email']),

        'password' => Hash::make(
            $validated['password']
        ),

        'role' => $validated['role'],


        'faculty' => $validated['faculty'] ?? null,


        'department' => $validated['department'] ?? null,


        // ตามฐานข้อมูลเดิมของคุณ status เป็น 0/1
        'status' => 1,

    ]);



    return redirect()
        ->route('users.index')
        ->with(
            'success',
            'เพิ่มผู้ใช้เรียบร้อยแล้ว'
        );
}


    /**
     * หน้าแก้ไขผู้ใช้
     */
    public function edit(User $user)
    {
        $this->checkAdmin();

        return view('users.edit', compact('user'));
    }


    /**
     * บันทึกข้อมูลผู้ใช้
     */
    public function update(Request $request, User $user)
    {
        $this->checkAdmin();


$validated = $request->validate([
    'role' => [
        'required',
        'in:student,teacher,staff,alumni,admin'
    ],

    'status' => [
        'required',
        'in:0,1'
    ],
]);


        // ป้องกัน Admin ลดสิทธิ์ตัวเอง
// ป้องกัน Admin ลดสิทธิ์ตัวเอง
if (
    auth()->id() === $user->id &&
    $validated['role'] !== 'admin'
) {
    return back()->with(
        'error',
        'ไม่สามารถเปลี่ยนสิทธิ์ของบัญชีตนเองได้'
    );
}

// ป้องกัน Admin ปิดบัญชีตัวเอง
if (
    auth()->id() === $user->id &&
    (int) $validated['status'] !== 1
) {
    return back()->with(
        'error',
        'ไม่สามารถปิดการใช้งานบัญชีตนเองได้'
    );
}

        // ต้องมีแอดมินที่ใช้งานได้อย่างน้อย 1 คน
        if (
            $user->role === 'admin'
            && ($validated['role'] !== 'admin' || (int) $validated['status'] !== 1)
            && User::where('role', 'admin')->where('status', 1)->count() <= 1
        ) {
            return back()->with('error', 'ต้องมีผู้ดูแลระบบที่ใช้งานได้อย่างน้อย 1 คน');
        }

        $user->update($validated);


        return redirect()
            ->to(url()->previous() === route('users.edit', $user) ? route('users.index') : url()->previous())
            ->with(
                'success',
                'อัปเดตสิทธิ์ของ '.$user->name.' เป็น “'.$user->role_name.'” เรียบร้อยแล้ว'
            );
    }

}