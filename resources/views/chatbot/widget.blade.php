{{-- ผู้ช่วยห้องสมุด (แชทบอท) — สคริปต์อยู่ที่ resources/js/chatbot.js --}}
<div id="chatbot-root"
    data-chat-url="{{ route('chatbot.chat') }}"
    data-history-url="{{ route('chatbot.history') }}"
    data-clear-url="{{ route('chatbot.clear') }}"
    data-user="{{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}">

    <button type="button" id="chatbot-button" aria-label="เปิดผู้ช่วยห้องสมุด" aria-controls="chatbot-box" aria-expanded="false"
        class="fixed bottom-5 right-5 z-50 flex h-14 items-center gap-2 rounded-full bg-ink-900 pl-4 pr-5 text-white shadow-lift ring-4 ring-white transition hover:-translate-y-0.5 hover:bg-ink-800">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold-400 text-ink-950"><i class="bi bi-stars"></i></span>
        <span class="hidden text-sm font-semibold sm:inline">ถามผู้ช่วยห้องสมุด</span>
    </button>

    <section id="chatbot-box" role="dialog" aria-label="ผู้ช่วยห้องสมุด" hidden
        class="fixed bottom-24 right-3 z-50 flex h-[min(620px,calc(100vh-120px))] w-[calc(100vw-24px)] max-w-[400px] flex-col overflow-hidden rounded-2xl border border-ink-100 bg-white shadow-lift sm:right-5">

        <header class="flex items-center gap-3 bg-ink-900 px-4 py-3 text-white">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gold-400 text-ink-950"><i class="bi bi-stars"></i></span>
            <div class="min-w-0 flex-1 leading-tight">
                <div class="font-semibold">ผู้ช่วยห้องสมุด</div>
                <div class="text-[11px] text-ink-200">ค้นหาหนังสือ · สถานะการยืม · สิทธิ์การยืม</div>
            </div>
            <button type="button" id="chat-clear" class="rounded-lg p-2 text-ink-200 hover:bg-white/10 hover:text-white" title="ล้างประวัติสนทนา" aria-label="ล้างประวัติสนทนา">
                <i class="bi bi-trash3"></i>
            </button>
            <button type="button" id="close-chat" class="rounded-lg p-2 text-ink-200 hover:bg-white/10 hover:text-white" aria-label="ปิด">
                <i class="bi bi-x-lg"></i>
            </button>
        </header>

        <div id="chat-body" class="flex-1 space-y-3 overflow-y-auto bg-paper px-3 py-4" aria-live="polite"></div>

        <div id="chat-suggestions" class="flex gap-2 overflow-x-auto border-t border-ink-100 bg-white px-3 py-2"></div>

        <form id="chat-form" class="flex items-center gap-2 border-t border-ink-100 bg-white p-3" autocomplete="off">
            <input type="text" id="chat-input" maxlength="300" required
                placeholder="พิมพ์ชื่อหนังสือ ผู้แต่ง หัวข้อ หรือคำถาม..."
                class="input flex-1 rounded-full">
            <button type="submit" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-ink-900 text-white hover:bg-ink-800" aria-label="ส่ง">
                <i class="bi bi-send-fill text-sm"></i>
            </button>
        </form>
    </section>
</div>
