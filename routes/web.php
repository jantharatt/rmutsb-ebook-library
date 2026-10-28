<?php

use App\Http\Controllers\BorrowController;
use App\Http\Controllers\BorrowPolicyController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EbookController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| หน้าต้อนรับ (ยังไม่ล็อกอิน)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
});

/*
|--------------------------------------------------------------------------
| ผู้ดูแลระบบเท่านั้น (role = admin)
|--------------------------------------------------------------------------
| ต้องประกาศก่อน route แสดงรายละเอียด เพื่อไม่ให้ /ebooks/create ชนกับ /ebooks/{ebook}
*/

Route::middleware(['auth', 'verified', 'admin'])->group(function () {

    // รายงาน
    Route::get('/dashboard/export', [DashboardController::class, 'export'])
        ->name('dashboard.export');

    // จัดการ Ebook (เพิ่ม/แก้ไข/ลบ)
    Route::resource('ebooks', EbookController::class)
        ->except(['index', 'show']);

    Route::post('/ebooks/{ebook}/derived', [EbookController::class, 'storeDerived'])
        ->name('ebooks.derived')
        ->middleware('throttle:60,1');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('borrow-policies', BorrowPolicyController::class)->except(['show']);

    Route::post('/borrows/{borrow}/renew', [BorrowController::class, 'renew'])
        ->name('borrows.renew');

    // จัดการผู้ใช้
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');

    // กลับโหมดผู้ดูแลระบบ
    Route::get('/admin-mode', function () {
        session()->forget('view_mode');

        return redirect()->route('dashboard');
    })->name('admin.mode');

    // ผู้ดูแลระบบดูหน้าเว็บแบบผู้ใช้ทั่วไป
    Route::get('/library', function () {
        session(['view_mode' => 'user']);

        return app(HomeController::class)->index();
    })->name('library');
});

/*
|--------------------------------------------------------------------------
| ผู้ใช้ที่ล็อกอินแล้วทุกบทบาท
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // แอดมิน → แดชบอร์ดรายงาน, ผู้ใช้ทั่วไป → หน้าแรกห้องสมุด
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/user', [HomeController::class, 'index'])->name('user.home');

    // Ebook
    Route::get('/ebooks', [EbookController::class, 'index'])->name('ebooks.index');
    Route::get('/ebooks/{ebook}', [EbookController::class, 'show'])->name('ebooks.show');
    Route::get('/ebooks/{ebook}/cover', [EbookController::class, 'cover'])->name('ebooks.cover');
    Route::get('/ebooks/{ebook}/preview', [EbookController::class, 'preview'])->name('ebooks.preview');
    Route::get('/ebooks/{ebook}/sample', [EbookController::class, 'sample'])
        ->name('ebooks.sample')
        ->middleware('throttle:30,1');
    Route::get('/ebooks/{ebook}/reader', [EbookController::class, 'reader'])->name('ebooks.reader');
    Route::get('/ebooks/{ebook}/stream', [EbookController::class, 'stream'])
        ->name('ebooks.stream')
        ->middleware('throttle:60,1');

    // ยืม / คืน
    Route::get('/borrows', [BorrowController::class, 'index'])->name('borrows.index');
    Route::post('/ebooks/{ebook}/borrow', [BorrowController::class, 'borrow'])
        ->name('ebooks.borrow')
        ->middleware('throttle:20,1');
    Route::post('/borrows/{borrow}/return', [BorrowController::class, 'returnBook'])
        ->name('borrows.return');

    // แชทบอท
    Route::post('/chatbot/chat', [ChatbotController::class, 'chat'])
        ->name('chatbot.chat')
        ->middleware('throttle:30,1');
    Route::get('/chatbot/history', [ChatbotController::class, 'history'])->name('chatbot.history');
    Route::delete('/chatbot/history', [ChatbotController::class, 'clear'])->name('chatbot.clear');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
