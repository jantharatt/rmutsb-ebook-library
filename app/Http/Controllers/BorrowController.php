<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use App\Models\Ebook;
use App\Models\BorrowPolicy;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BorrowController extends Controller
{

public function index(Request $request)
{

    $user = Auth::user();


    if(
        $user->isAdmin()
        &&
        session('view_mode') != 'user'
    ){


        $search = $request->search;


        $borrows = Borrow::with([
            'user',
            'ebook'
        ])

        ->when($search, function($query) use ($search){


            $query->where(function ($query) use ($search) {
            $query->whereHas('user', function($q) use ($search){

                $q->where('name','like',"%{$search}%")
                  ->orWhere('email','like',"%{$search}%");

            })

            ->orWhereHas('ebook', function($q) use ($search){

                $q->where('title','like',"%{$search}%");

            });
            });


        })

        ->when(in_array($request->status, ['borrowed', 'returned', 'expired'], true), function ($q) use ($request) {
            $q->where('status', $request->status);
        })
        ->latest()
        ->paginate(20)
        ->withQueryString();


    }
    else{


        $borrows = Borrow::with([
            'ebook'
        ])

        ->where(
            'user_id',
            Auth::id()
        )

        ->latest()
        ->paginate(20)
        ->withQueryString();


    }



    foreach ($borrows as $borrow) {


        $borrow->days_left = (int) today()->diffInDays(
            $borrow->due_date,
            false
        );


    }



    return view(
        'borrows.index',
        compact('borrows')
    );

}

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Borrow $borrow)
    {
        //
    }

    public function edit(Borrow $borrow)
    {
        //
    }

    public function update(Request $request, Borrow $borrow)
    {
        //
    }

    public function destroy(Borrow $borrow)
    {
        //
    }

    public function borrow(Ebook $ebook)
    {
        $user = Auth::user();

        if ((int) $ebook->status !== 1) {
            return back()->with('error', 'Ebook เล่มนี้ปิดให้บริการชั่วคราว');
        }

        $policy = BorrowPolicy::where('role', $user->role)
            ->where('active', 1)
            ->first();

        if (! $policy) {
            return back()->with(
                'error',
                $user->isAdmin()
                    ? 'ผู้ดูแลระบบอ่านได้ทุกเล่มโดยไม่ต้องยืม'
                    : 'ไม่พบนโยบายการยืมสำหรับประเภทผู้ใช้ของคุณ กรุณาติดต่อห้องสมุด'
            );
        }

        // ป้องกันกดซ้ำพร้อมกัน (double submit) ด้วย lock ราย user
        return DB::transaction(function () use ($user, $ebook, $policy) {
            User::whereKey($user->id)->lockForUpdate()->first();

            // รายการที่เลยกำหนดแต่ยังเป็น borrowed → ปรับเป็น expired ก่อนนับ
            Borrow::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->whereDate('due_date', '<', today())
                ->update(['status' => 'expired']);

            $alreadyBorrowed = Borrow::where('user_id', $user->id)
                ->where('ebook_id', $ebook->id)
                ->where('status', 'borrowed')
                ->exists();

            if ($alreadyBorrowed) {
                return back()->with('error', 'คุณยืม Ebook เล่มนี้อยู่แล้ว');
            }

            $currentBorrows = Borrow::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->count();

            if ($currentBorrows >= $policy->max_books) {
                return back()->with(
                    'error',
                    "คุณยืมครบ {$policy->max_books} เล่มตามสิทธิ์แล้ว กรุณาคืนเล่มที่อ่านจบก่อน"
                );
            }

            $dueDate = now()->addDays($policy->borrow_days);

            Borrow::create([
                'user_id' => $user->id,
                'ebook_id' => $ebook->id,
                'borrow_date' => today(),
                'due_date' => $dueDate->toDateString(),
                'status' => 'borrowed',
            ]);

            $ebook->increment('borrow_count');

            return back()->with(
                'success',
                'ยืม Ebook สำเร็จ อ่านได้ถึงวันที่ '.$dueDate->locale('th')->translatedFormat('j M').' '.($dueDate->year + 543)
            );
        });
    }

    public function returnBook(Borrow $borrow)
    {
        $user = Auth::user();

        // คืนได้เฉพาะรายการของตนเอง (ผู้ดูแลระบบคืนแทนได้)
        if ($borrow->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        if ($borrow->status !== 'borrowed') {
            return back()->with('error', 'รายการนี้ไม่ได้อยู่ในสถานะกำลังยืม');
        }

        $borrow->update([
            'status' => 'returned',
            'return_date' => today(),
        ]);

        return back()->with('success', 'คืน Ebook สำเร็จ');
    }

    public function renew(Borrow $borrow)
    {
        if (! in_array($borrow->status, ['borrowed', 'expired'], true) || $borrow->return_date) {
            return back()->with('error', 'ต่ออายุได้เฉพาะรายการที่กำลังยืมหรือหมดอายุ');
        }

        $base = $borrow->due_date && $borrow->due_date->isFuture()
            ? $borrow->due_date->copy()
            : today();

        $borrow->update([
            'due_date' => $base->addDays(7)->toDateString(),
            'renew_count' => (int) $borrow->renew_count + 1,
            'status' => 'borrowed',
        ]);

        return back()->with('success', 'ต่ออายุ Ebook สำเร็จ (+7 วัน)');
    }
}
