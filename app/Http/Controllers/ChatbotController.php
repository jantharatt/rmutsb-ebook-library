<?php

namespace App\Http\Controllers;

use App\Models\Borrow;
use App\Models\BorrowPolicy;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Ebook;
use App\Support\ThaiDate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * ผู้ช่วยห้องสมุด (แชทบอท) — ทำงานบนข้อมูลในระบบทั้งหมด ไม่ต้องใช้บริการภายนอก
 *
 * เข้าใจคำถาม:
 *  - ค้นหาหนังสือ (ตัดคำภาษาไทย + ให้คะแนนความเกี่ยวข้องจากหลายฟิลด์)
 *  - หนังสือตามหมวด / ผู้แต่ง / ISBN / เลขเรียก / ปีพิมพ์ (พ.ศ. หรือ ค.ศ.)
 *  - หนังสือใหม่ / ยอดนิยม
 *  - หนังสือที่ฉันยืมอยู่และวันครบกำหนด / สิทธิ์การยืมตามประเภทผู้ใช้
 *  - วิธียืม คืน ต่ออายุ และช่องทางติดต่อ
 *
 * ข้อความที่บันทึกลง chat_messages ผ่านการ escape แล้วทุกครั้ง (ป้องกัน XSS)
 */
class ChatbotController extends Controller
{
    private const MAX_RESULTS = 5;

    private const STOPWORDS = [
        'หา', 'ค้นหา', 'ค้น', 'ขอ', 'อยาก', 'อยากได้', 'ได้', 'ไหม', 'มั้ย', 'มี', 'มีไหม', 'หนังสือ', 'ebook', 'e-book',
        'อีบุ๊ก', 'อีบุ๊ค', 'อีบุค', 'เกี่ยวกับ', 'เกี่ยว', 'กับ', 'เรื่อง', 'ครับ', 'ค่ะ', 'คะ', 'นะ', 'หน่อย', 'ช่วย',
        'ที่', 'และ', 'หรือ', 'เล่ม', 'บ้าง', 'อะไร', 'ด้วย', 'จ้า', 'ผม', 'ฉัน', 'หนู', 'ใน', 'เป็น', 'คือ', 'ว่า',
        'the', 'of', 'and', 'a', 'an', 'to', 'in', 'for', 'on', 'book', 'books', 'please', 'find', 'search',
        'ให้', 'ที', 'ดู', 'ทาง', 'ด้าน', 'วิชา', 'แนว', 'ไร', 'ยัง', 'ไง', 'อ่าน', 'เอา', 'แบบ',
    ];

    /* ======================================================================
     | Endpoints
     * ====================================================================== */

    public function history()
    {
        $messages = ChatMessage::where('user_id', Auth::id())
            ->orderByDesc('id')
            ->limit(60)
            ->get(['id', 'message', 'sender', 'created_at'])
            ->reverse()
            ->values();

        return response()->json($messages);
    }

    public function clear()
    {
        ChatMessage::where('user_id', Auth::id())->delete();

        return response()->json(['ok' => true]);
    }

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:300',
        ]);

        $message = trim(preg_replace('/\s+/u', ' ', strip_tags($validated['message'])));

        if ($message === '') {
            return response()->json(['reply' => 'พิมพ์ชื่อหนังสือ ผู้แต่ง หรือหัวข้อที่สนใจได้เลยครับ', 'suggestions' => []]);
        }

        ChatMessage::create([
            'user_id' => Auth::id(),
            'message' => $message, // เก็บเป็นข้อความล้วน (หน้าเว็บแสดงแบบ text)
            'sender' => 'user',
        ]);

        [$reply, $suggestions] = $this->answer($message);

        ChatMessage::create([
            'user_id' => Auth::id(),
            'message' => $reply,
            'sender' => 'bot',
        ]);

        return response()->json([
            'reply' => $reply,
            'suggestions' => $suggestions,
        ]);
    }

    /* ======================================================================
     | Intent routing
     * ====================================================================== */

    private function answer(string $message): array
    {
        $m = mb_strtolower($message);

        // ทักทาย / ขอความช่วยเหลือ
        if (preg_match('/^(สวัสดี|หวัดดี|ดีครับ|ดีค่ะ|hello|hi|hey|help|ช่วยเหลือ|ทำอะไรได้|ใช้งานยังไง|เมนู)/u', $m)) {
            return $this->helpReply();
        }

        // หนังสือที่ฉันยืมอยู่
        if (preg_match('/(ยืมอยู่|ที่ยืม|ของฉัน|ของผม|ของหนู|ครบกำหนด|กำหนดคืน|หมดอายุ|เหลือกี่วัน|my ?books?|my ?loans?)/u', $m)) {
            return $this->myBorrowsReply();
        }

        // สิทธิ์การยืม
        if (preg_match('/(ยืมได้กี่|กี่เล่ม|กี่วัน|สิทธิ์|สิทธิ|นโยบาย|ระเบียบ|เงื่อนไข|policy)/u', $m)) {
            return $this->policyReply();
        }

        // วิธียืม / คืน / ต่ออายุ
        if (preg_match('/(วิธี|ยังไง|อย่างไร|ทำไง|how).*(ยืม|คืน|ต่ออายุ|อ่าน)|(ยืม|คืน|ต่ออายุ|อ่าน).*(ยังไง|อย่างไร|ทำไง)|^ต่ออายุ|^วิธี/u', $m)) {
            return $this->howToReply($m);
        }

        // ติดต่อห้องสมุด
        if (preg_match('/(ติดต่อ|เบอร์|โทร|อีเมล|email|เวลาทำการ|เปิดกี่โมง|บรรณารักษ์|contact)/u', $m)) {
            return $this->contactReply();
        }

        // จำนวนหนังสือในระบบ
        if (preg_match('/(ทั้งหมดกี่|มีกี่เล่ม|มีหนังสือกี่|จำนวนหนังสือ|สถิติ)/u', $m)) {
            return $this->statsReply();
        }

        // รายการหมวดหมู่
        if (preg_match('/^(หมวด(หมู่)?(ทั้งหมด)?|มีหมวด(หมู่)?อะไร(บ้าง)?|categories?)$/u', $m) || preg_match('/มีหมวด(หมู่)?อะไร/u', $m)) {
            return $this->categoriesReply();
        }

        // หมวด ...
        if (preg_match('/^(?:หมวด(?:หมู่)?|category)\s*(.+)$/u', $m, $mm)) {
            return $this->categoryReply(trim($mm[1]));
        }

        // หนังสือใหม่
        if (preg_match('/(มาใหม่|ใหม่ล่าสุด|ล่าสุด|เพิ่งเพิ่ม|new|latest)/u', $m)) {
            return $this->listReply(
                'Ebook มาใหม่ล่าสุด',
                $this->visibleQuery()->latest()->take(self::MAX_RESULTS)->get()
            );
        }

        // ยอดนิยม / แนะนำ
        if (preg_match('/(ยอดนิยม|นิยม|ฮิต|แนะนำ|popular|recommend)/u', $m)) {
            $books = $this->visibleQuery()
                ->orderByDesc('borrow_count')
                ->orderByDesc('view_count')
                ->take(self::MAX_RESULTS)
                ->get();

            return $this->listReply('Ebook ยอดนิยม (ยืมและเปิดอ่านมากที่สุด)', $books);
        }

        // ISBN
        $digits = preg_replace('/[^0-9Xx]/', '', $m);
        if (preg_match('/^(isbn)?\s*[0-9][0-9\- ]{8,16}[0-9xX]$/u', $m) && strlen($digits) >= 10 && strlen($digits) <= 13) {
            $books = $this->visibleQuery()
                ->whereRaw("REPLACE(isbn, '-', '') like ?", ['%'.$digits.'%'])
                ->take(self::MAX_RESULTS)
                ->get();

            return $this->listReply('ผลการค้นหา ISBN '.$digits, $books);
        }

        // ผู้แต่ง ...
        if (preg_match('/^(?:ผู้แต่ง|ผู้เขียน|นักเขียน|author|by)\s*[:：]?\s*(.+)$/u', $m, $mm)) {
            $name = trim($mm[1]);
            $books = $this->visibleQuery()
                ->where(fn ($q) => $q->where('author', 'like', "%{$name}%")
                    ->orWhere('co_author', 'like', "%{$name}%")
                    ->orWhere('added_personal_name', 'like', "%{$name}%"))
                ->take(self::MAX_RESULTS)
                ->get();

            return $this->listReply('ผลงานของผู้แต่ง “'.$name.'”', $books);
        }

        // ปีพิมพ์
        if (preg_match('/(?:ปี|พิมพ์ปี|ปีพิมพ์|year)\s*(\d{4})/u', $m, $mm)) {
            $year = (int) $mm[1];
            $years = $year > 2400 ? [$year, $year - 543] : [$year, $year + 543];
            $books = $this->visibleQuery()->whereIn('publish_year', $years)->take(self::MAX_RESULTS)->get();

            return $this->listReply('Ebook ที่พิมพ์ปี '.($year > 2400 ? 'พ.ศ. '.$year : 'ค.ศ. '.$year), $books);
        }

        // เลขเรียก
        if (preg_match('/^(?:เลขเรียก|call\s*(?:no|number)?)\s*(.+)$/u', $m, $mm)) {
            $call = trim($mm[1]);
            $books = $this->visibleQuery()
                ->where(fn ($q) => $q->where('call_number', 'like', "%{$call}%")->orWhere('call_number_copy', 'like', "%{$call}%"))
                ->take(self::MAX_RESULTS)
                ->get();

            return $this->listReply('ผลการค้นหาเลขเรียก '.$call, $books);
        }

        return $this->searchReply($message);
    }

    /* ======================================================================
     | Replies
     * ====================================================================== */

    private function helpReply(): array
    {
        $name = e(Str::before(Auth::user()->name, ' '));

        $html = "<p>สวัสดีครับคุณ{$name} 👋 ผมคือผู้ช่วยห้องสมุด Ebook</p>"
            .'<p class="chat-muted">ลองถามผมได้แบบนี้ครับ</p>'
            .'<ul class="chat-list">'
            .'<li><b>ค้นหา</b> — “หนังสือเกี่ยวกับสาหร่าย”, “ระบบควบคุม”</li>'
            .'<li><b>หมวดหมู่</b> — “หมวด 600” หรือ “มีหมวดอะไรบ้าง”</li>'
            .'<li><b>ผู้แต่ง / ISBN / ปี</b> — “ผู้แต่ง สมชาย”, “9786160000000”, “ปี 2567”</li>'
            .'<li><b>ของฉัน</b> — “หนังสือที่ฉันยืมอยู่”, “ยืมได้กี่เล่ม”</li>'
            .'<li><b>แนะนำ</b> — “หนังสือมาใหม่”, “หนังสือยอดนิยม”</li>'
            .'</ul>';

        return [$html, ['หนังสือมาใหม่', 'หนังสือยอดนิยม', 'หนังสือที่ฉันยืมอยู่', 'มีหมวดอะไรบ้าง']];
    }

    private function myBorrowsReply(): array
    {
        $borrows = Borrow::with('ebook')
            ->where('user_id', Auth::id())
            ->where('status', 'borrowed')
            ->orderBy('due_date')
            ->get();

        if ($borrows->isEmpty()) {
            return [
                '<p>ตอนนี้คุณยังไม่มี Ebook ที่ยืมอยู่ครับ</p><p class="chat-muted">ลองค้นหาหนังสือที่สนใจ แล้วกด “ยืม Ebook” ในหน้ารายละเอียดได้เลย</p>',
                ['หนังสือมาใหม่', 'หนังสือยอดนิยม', 'ยืมได้กี่เล่ม'],
            ];
        }

        $html = '<p class="chat-title">Ebook ที่คุณยืมอยู่ ('.$borrows->count().' เล่ม)</p>';

        foreach ($borrows as $borrow) {
            if (! $borrow->ebook) {
                continue;
            }

            $days = (int) today()->diffInDays($borrow->due_date, false);
            $badge = $days < 0
                ? '<span class="chat-badge chat-badge-danger">เลยกำหนด '.abs($days).' วัน</span>'
                : ($days <= 2
                    ? '<span class="chat-badge chat-badge-warn">เหลือ '.$days.' วัน</span>'
                    : '<span class="chat-badge chat-badge-ok">เหลือ '.$days.' วัน</span>');

            $html .= $this->bookCard($borrow->ebook, 'ครบกำหนด '.ThaiDate::short($borrow->due_date).' '.$badge, true);
        }

        return [$html, ['ยืมได้กี่เล่ม', 'วิธีต่ออายุ', 'หนังสือมาใหม่']];
    }

    private function policyReply(): array
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $rows = BorrowPolicy::where('active', 1)->get();
            $html = '<p class="chat-title">นโยบายการยืมของแต่ละประเภทผู้ใช้</p><ul class="chat-list">';
            foreach ($rows as $p) {
                $html .= '<li>'.e($this->roleName($p->role)).' — ยืมได้ '.(int) $p->max_books.' เล่ม นาน '.(int) $p->borrow_days.' วัน</li>';
            }
            $html .= '</ul><p class="chat-muted">ผู้ดูแลระบบเปิดอ่านได้ทุกเล่มโดยไม่ต้องยืม</p>';

            return [$html, []];
        }

        $policy = BorrowPolicy::where('role', $user->role)->where('active', 1)->first();

        if (! $policy) {
            return ['<p>ยังไม่พบนโยบายการยืมสำหรับประเภทผู้ใช้ของคุณ กรุณาติดต่อห้องสมุดครับ</p>', ['ติดต่อห้องสมุด']];
        }

        $current = Borrow::where('user_id', $user->id)->where('status', 'borrowed')->count();
        $left = max(0, $policy->max_books - $current);

        $html = '<p class="chat-title">สิทธิ์การยืมของคุณ ('.e($user->role_name).')</p>'
            .'<ul class="chat-list">'
            .'<li>ยืมพร้อมกันได้สูงสุด <b>'.(int) $policy->max_books.' เล่ม</b></li>'
            .'<li>ระยะเวลายืม <b>'.(int) $policy->borrow_days.' วัน</b> ต่อเล่ม</li>'
            .'<li>ตอนนี้ยืมอยู่ <b>'.$current.' เล่ม</b> ยืมเพิ่มได้อีก <b>'.$left.' เล่ม</b></li>'
            .'</ul><p class="chat-muted">ครบกำหนดแล้วระบบจะปิดสิทธิ์อ่านอัตโนมัติ ไม่มีค่าปรับ</p>';

        return [$html, ['หนังสือที่ฉันยืมอยู่', 'วิธีต่ออายุ']];
    }

    private function howToReply(string $m): array
    {
        if (str_contains($m, 'ต่ออายุ')) {
            $html = '<p class="chat-title">การต่ออายุการยืม</p>'
                .'<p>การต่ออายุทำโดยบรรณารักษ์ ครั้งละ 7 วัน กรุณาติดต่อห้องสมุดก่อนวันครบกำหนด พร้อมแจ้งชื่อหนังสือ</p>';

            return [$html, ['ติดต่อห้องสมุด', 'หนังสือที่ฉันยืมอยู่']];
        }

        if (str_contains($m, 'คืน')) {
            $html = '<p class="chat-title">วิธีคืน Ebook</p><ol class="chat-list">'
                .'<li>ไปที่เมนู <b>ชั้นหนังสือของฉัน</b></li>'
                .'<li>กดปุ่ม <b>คืนหนังสือ</b> ที่รายการที่ต้องการ</li>'
                .'</ol><p class="chat-muted">ถ้าไม่ได้กดคืน ระบบจะคืนให้อัตโนมัติเมื่อครบกำหนด</p>';

            return [$html, ['หนังสือที่ฉันยืมอยู่']];
        }

        $html = '<p class="chat-title">วิธียืมและอ่าน Ebook</p><ol class="chat-list">'
            .'<li>ค้นหาหนังสือ แล้วเปิดหน้ารายละเอียด</li>'
            .'<li>กด <b>ดูตัวอย่าง</b> เพื่ออ่าน 5 หน้าแรกได้ทันที</li>'
            .'<li>กด <b>ยืม Ebook</b> แล้วกด <b>อ่านเต็มเล่ม</b> ได้จนถึงวันครบกำหนด</li>'
            .'</ol>';

        return [$html, ['ยืมได้กี่เล่ม', 'หนังสือมาใหม่']];
    }

    private function contactReply(): array
    {
        $html = '<p class="chat-title">ติดต่อสำนักวิทยบริการและเทคโนโลยีสารสนเทศ</p><ul class="chat-list">'
            .'<li>☎ 035-709081</li>'
            .'<li>✉ library@rmutsb.ac.th</li>'
            .'<li>📍 ศูนย์พระนครศรีอยุธยา หันตรา</li>'
            .'</ul>';

        return [$html, []];
    }

    private function statsReply(): array
    {
        $total = $this->visibleQuery()->count();
        $mainCount = Category::whereNull('parent_id')->where('status', 1)->count();

        $html = '<p>ขณะนี้ห้องสมุดมี Ebook ให้บริการ <b>'.number_format($total).' เล่ม</b> ใน <b>'.$mainCount.' หมวดหลัก</b></p>';

        return [$html, ['มีหมวดอะไรบ้าง', 'หนังสือมาใหม่']];
    }

    private function categoriesReply(): array
    {
        $counts = Ebook::query()
            ->when(! Auth::user()->isAdmin(), fn ($q) => $q->where('ebooks.status', 1))
            ->join('categories', 'ebooks.category_id', '=', 'categories.id')
            ->selectRaw('COALESCE(categories.parent_id, categories.id) as main_id, COUNT(*) as total')
            ->groupBy('main_id')
            ->pluck('total', 'main_id');

        $mains = Category::whereNull('parent_id')->where('status', 1)->orderBy('code')->get();

        $html = '<p class="chat-title">หมวดหมู่หลัก (ระบบทศนิยมดิวอี้)</p><ul class="chat-list">';
        foreach ($mains as $c) {
            $url = e(route('ebooks.index', ['category' => $c->id]));
            $html .= '<li><a href="'.$url.'">'.e($c->code.' '.$c->name).'</a> <span class="chat-muted">('.(int) ($counts[$c->id] ?? 0).' เล่ม)</span></li>';
        }
        $html .= '</ul>';

        return [$html, $mains->take(4)->map(fn ($c) => 'หมวด '.$c->code)->all()];
    }

    private function categoryReply(string $term): array
    {
        $category = Category::where('code', $term)->first()
            ?? Category::where('name', 'like', "%{$term}%")->orderByRaw('parent_id is not null')->first();

        if (! $category) {
            return $this->searchReply($term);
        }

        $ids = Category::where('parent_id', $category->id)->pluck('id')->push($category->id);

        $books = $this->visibleQuery()
            ->whereIn('category_id', $ids)
            ->orderByDesc('borrow_count')
            ->take(self::MAX_RESULTS)
            ->get();

        $total = $this->visibleQuery()->whereIn('category_id', $ids)->count();
        $more = $total > $books->count()
            ? '<a class="chat-more" href="'.e(route('ebooks.index', ['category' => $category->parent_id ?? $category->id])).'">ดูทั้งหมด '.$total.' เล่ม →</a>'
            : '';

        [$html, $s] = $this->listReply('หมวด '.$category->code.' '.$category->name, $books);

        return [$html.$more, $s];
    }

    private function listReply(string $title, Collection $books): array
    {
        if ($books->isEmpty()) {
            return [
                '<p>'.e($title).'</p><p class="chat-muted">ยังไม่พบรายการครับ</p>',
                ['หนังสือมาใหม่', 'มีหมวดอะไรบ้าง'],
            ];
        }

        $html = '<p class="chat-title">'.e($title).'</p>';

        foreach ($books as $book) {
            $html .= $this->bookCard($book);
        }

        return [$html, []];
    }

    /**
     * ค้นหาแบบให้คะแนน (ตัดคำไทย, ตัดคำฟุ่มเฟือย, ให้น้ำหนักตามฟิลด์)
     */
    private function searchReply(string $message): array
    {
        $tokens = $this->tokenize($message);
        $phrase = $this->cleanPhrase($message);

        if (empty($tokens) && $phrase === '') {
            return $this->helpReply();
        }

        $terms = collect($tokens)->push($phrase)->filter(fn ($t) => mb_strlen($t) >= 2)->unique()->values();

        $fields = ['title', 'title_en', 'author', 'co_author', 'added_personal_name', 'keywords', 'subject_heading',
            'description', 'publisher', 'series_name', 'isbn', 'call_number'];

        $candidates = $this->visibleQuery()
            ->with('category:id,code,name,parent_id')
            ->where(function ($q) use ($terms, $fields) {
                foreach ($terms as $term) {
                    foreach ($fields as $field) {
                        $q->orWhere($field, 'like', '%'.$term.'%');
                    }
                    $q->orWhereHas('category', fn ($c) => $c->where('name', 'like', '%'.$term.'%'));
                }
            })
            ->limit(200)
            ->get();

        $weights = ['title' => 6, 'title_en' => 5, 'author' => 5, 'co_author' => 3, 'added_personal_name' => 3,
            'keywords' => 4, 'subject_heading' => 4, 'series_name' => 2, 'publisher' => 1, 'description' => 1,
            'isbn' => 6, 'call_number' => 3];

        $scored = $candidates->map(function (Ebook $book) use ($terms, $weights, $phrase, $tokens) {
            $score = 0;
            $matchedTokens = 0;

            foreach ($terms as $term) {
                $hit = false;
                foreach ($weights as $field => $w) {
                    if ($book->{$field} && mb_stripos((string) $book->{$field}, $term) !== false) {
                        $score += $w * max(1, mb_strlen($term) / 3);
                        $hit = true;
                    }
                }
                if ($book->category && mb_stripos($book->category->name, $term) !== false) {
                    $score += 3;
                    $hit = true;
                }
                if ($hit && in_array($term, $tokens, true)) {
                    $matchedTokens++;
                }
            }

            // ตรงทั้งวลีในชื่อเรื่อง ให้คะแนนพิเศษ
            if ($phrase !== '' && mb_stripos((string) $book->title, $phrase) !== false) {
                $score += 25;
            }

            // ครอบคลุมทุกคำค้น ให้คะแนนพิเศษ
            if (count($tokens) > 1 && $matchedTokens === count($tokens)) {
                $score += 15;
            }

            $score += min(5, $book->borrow_count / 5);

            $book->setAttribute('chat_score', $score);

            return $book;
        })->sortByDesc('chat_score')->values();

        if ($scored->isEmpty()) {
            $popular = $this->visibleQuery()->orderByDesc('borrow_count')->take(3)->get();

            $html = '<p>ไม่พบ Ebook ที่ตรงกับ “'.e(Str::limit($message, 60)).'” ครับ</p>'
                .'<p class="chat-muted">ลองใช้คำค้นที่สั้นลง คำพ้องความหมาย หรือค้นตามหมวดหมู่ เช่น “หมวด 600”</p>';

            if ($popular->isNotEmpty()) {
                $html .= '<p class="chat-title">หรือลองดูหนังสือยอดนิยมเหล่านี้</p>';
                foreach ($popular as $book) {
                    $html .= $this->bookCard($book);
                }
            }

            return [$html, ['มีหมวดอะไรบ้าง', 'หนังสือมาใหม่']];
        }

        $top = $scored->take(self::MAX_RESULTS);

        $html = '<p class="chat-title">พบ '.$scored->count().' รายการที่เกี่ยวข้องกับ “'.e(Str::limit($phrase ?: $message, 40)).'”</p>';

        foreach ($top as $book) {
            $html .= $this->bookCard($book);
        }

        if ($scored->count() > self::MAX_RESULTS) {
            $html .= '<a class="chat-more" href="'.e(route('ebooks.index', ['search' => $phrase ?: $message])).'">ดูผลการค้นหาทั้งหมด →</a>';
        }

        // คำแนะนำต่อ: หมวดของผลลัพธ์อันดับแรก
        $suggestions = [];
        if ($first = $top->first()) {
            if ($first->category) {
                $suggestions[] = 'หมวด '.$first->category->code;
            }
            if ($first->author) {
                $suggestions[] = 'ผู้แต่ง '.Str::limit($first->author, 30, '');
            }
        }

        return [$html, $suggestions];
    }

    /* ======================================================================
     | Helpers
     * ====================================================================== */

    private function visibleQuery()
    {
        $query = Ebook::query();

        if (! Auth::user()->isAdmin()) {
            $query->where('ebooks.status', 1);
        }

        return $query->select('ebooks.*');
    }

    private function bookCard(Ebook $book, ?string $extraHtml = null, bool $mine = false): string
    {
        $url = e(route('ebooks.show', $book));
        $cover = $book->cover_url;

        $thumb = $cover
            ? '<img src="'.e($cover).'" alt="" loading="lazy">'
            : '<span>'.e(mb_substr($book->title, 0, 1)).'</span>';

        $meta = collect([
            $book->author,
            $book->publish_year ? 'ปี '.$book->publish_year : null,
        ])->filter()->map(fn ($v) => e($v))->implode(' · ');

        if (! $mine && ! $extraHtml) {
            $borrowed = Borrow::where('user_id', Auth::id())
                ->where('ebook_id', $book->id)
                ->where('status', 'borrowed')
                ->exists();

            $extraHtml = $borrowed
                ? '<span class="chat-badge chat-badge-ok">คุณยืมอยู่ · อ่านได้เลย</span>'
                : '<span class="chat-badge">ยืมได้</span>';
        }

        return '<a class="chat-book" href="'.$url.'">'
            .'<div class="chat-book-cover">'.$thumb.'</div>'
            .'<div class="chat-book-body">'
            .'<div class="chat-book-title">'.e(Str::limit($book->title, 90)).'</div>'
            .($meta ? '<div class="chat-book-meta">'.$meta.'</div>' : '')
            .($extraHtml ? '<div class="chat-book-extra">'.$extraHtml.'</div>' : '')
            .'</div></a>';
    }

    /** ตัดคำ (รองรับภาษาไทยด้วย ICU ถ้ามี) และตัดคำฟุ่มเฟือย */
    private function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        $words = [];

        if (class_exists(\IntlBreakIterator::class)) {
            $it = \IntlBreakIterator::createWordInstance('th');
            $it->setText($text);

            foreach ($it->getPartsIterator() as $part) {
                $part = trim($part);
                if ($part !== '' && preg_match('/[\p{L}\p{N}]/u', $part)) {
                    $words[] = $part;
                }
            }
        } else {
            $words = preg_split('/[\s,.;:!?()\[\]"\'\/]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        }

        // รวมคำย่อยไทยที่สั้นมากเข้ากับคำถัดไป เพื่อไม่ให้ค้นกว้างเกินไป
        $tokens = [];
        $buffer = '';
        foreach ($words as $w) {
            if (in_array($w, self::STOPWORDS, true)) {
                if ($buffer !== '') {
                    $tokens[] = $buffer;
                    $buffer = '';
                }

                continue;
            }

            if (preg_match('/^\p{Thai}+$/u', $w) && mb_strlen($w) <= 2) {
                $buffer .= $w;

                continue;
            }

            $tokens[] = $buffer.$w;
            $buffer = '';
        }

        if ($buffer !== '') {
            $tokens[] = $buffer;
        }

        return array_values(array_unique(array_filter($tokens, fn ($t) => mb_strlen($t) >= 2)));
    }

    /** ลบคำฟุ่มเฟือยออกจากประโยค เหลือวลีหลักสำหรับค้นแบบตรงตัว */
    private function cleanPhrase(string $text): string
    {
        $phrase = mb_strtolower($text);

        $fillers = ['ช่วยหา', 'ค้นหา', 'อยากได้', 'อยากอ่าน', 'มีหนังสือ', 'หนังสือ', 'เกี่ยวกับ', 'เรื่อง', 'อีบุ๊ก', 'อีบุ๊ค',
            'ebook', 'ครับ', 'ค่ะ', 'คะ', 'หน่อย', 'บ้างไหม', 'บ้าง', 'ไหม', 'มั้ย', 'ขอ', 'หา'];

        foreach ($fillers as $f) {
            $phrase = str_replace($f, ' ', $phrase);
        }

        return trim(preg_replace('/\s+/u', ' ', $phrase));
    }

    private function roleName(string $role): string
    {
        return match ($role) {
            'student' => 'นักศึกษา',
            'teacher' => 'อาจารย์',
            'staff' => 'บุคลากร',
            'alumni' => 'ศิษย์เก่า',
            default => $role,
        };
    }
}
