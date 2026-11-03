{{-- ตัวอ่านแบบเปิดหนังสือ: @include('ebooks.partials.book-reader', ['src' => URL ของ PDF]) --}}
<div class="book-reader" data-book-reader data-src="{{ $src }}">

    <div class="book-stage" data-book-stage>
        <p class="book-status" data-book-status><span class="book-spinner"></span> กำลังเปิดหนังสือ...</p>

        <div class="book" data-book>
            <div class="book-page left" data-page-left></div>
            <div class="book-page right" data-page-right></div>

            <div class="book-leaf" data-leaf hidden>
                <div class="book-face front" data-leaf-front></div>
                <div class="book-face back" data-leaf-back></div>
            </div>
        </div>
    </div>

    <div class="book-toolbar">
        <button type="button" class="btn btn-outline btn-sm" data-book-first title="หน้าแรก"><i class="bi bi-chevron-bar-left"></i></button>
        <button type="button" class="btn btn-outline btn-sm" data-book-prev title="หน้าก่อนหน้า (←)"><i class="bi bi-chevron-left"></i> ก่อนหน้า</button>

        <span class="text-sm text-ink-600">
            หน้า
            <input type="number" min="1" value="1" data-page-input
                class="mx-1 h-8 w-16 rounded-md border-gray-300 text-center text-sm">
            / <span data-page-total>-</span>
        </span>

        <button type="button" class="btn btn-outline btn-sm" data-book-next title="หน้าถัดไป (→)">ถัดไป <i class="bi bi-chevron-right"></i></button>
        <button type="button" class="btn btn-outline btn-sm" data-book-last title="หน้าสุดท้าย"><i class="bi bi-chevron-bar-right"></i></button>
        <span class="mx-1 h-6 w-px bg-ink-200"></span>

        <button type="button" class="btn btn-outline btn-sm" data-zoom-out title="ซูมออก (-)"><i class="bi bi-zoom-out"></i></button>
        <button type="button" class="btn btn-outline btn-sm min-w-[4rem]" data-zoom-reset title="ขนาดพอดีจอ (0)"><span data-zoom-label>100%</span></button>
        <button type="button" class="btn btn-outline btn-sm" data-zoom-in title="ซูมเข้า (+) หรือ Ctrl + ล้อเมาส์"><i class="bi bi-zoom-in"></i></button>

        <button type="button" class="btn btn-outline btn-sm" data-book-fullscreen title="เต็มจอ"><i class="bi bi-arrows-fullscreen"></i></button>
    </div>
</div>

@once
    @push('styles')
        <style>
            .book-reader { display: flex; flex-direction: column; gap: .75rem; background: radial-gradient(ellipse at center, #f3efe6 0%, #e4ddcd 100%); border-radius: 1rem; padding: 1rem; }
            .book-reader:fullscreen { border-radius: 0; padding: 1.5rem; }
            .book-stage { position: relative; height: 76vh; display: flex; padding: 12px; overflow: auto; perspective: 2500px; }
            .book-stage .book { margin: auto; flex-shrink: 0; } /* margin:auto จัดกึ่งกลาง และเลื่อนดูได้ครบเมื่อซูม */
            .book-reader.is-zoomed .book { cursor: grab; }
            .book-stage.is-dragging, .book-stage.is-dragging .book { cursor: grabbing; }
            .book-reader.is-zoomed .book.is-cover, .book-reader.is-zoomed .book.is-back { transform: none; }
            .book-reader:fullscreen .book-stage { height: calc(100vh - 6rem); }
            .book-status { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; gap: .5rem; color: #6b6458; font-size: .9rem; z-index: 5; }
            .book-status[hidden] { display: none; }
            .book-spinner { width: 1.1rem; height: 1.1rem; border: 2px solid #c9bfa9; border-top-color: #6b6458; border-radius: 50%; animation: book-spin .8s linear infinite; }
            @keyframes book-spin { to { transform: rotate(360deg); } }

            .book { position: relative; display: flex; transform-style: preserve-3d; cursor: pointer; opacity: 0; transition: opacity .3s, transform .4s; user-select: none; }
            .book-reader.is-ready .book { opacity: 1; }
            .book-page, .book-face { position: relative; background: #fff; overflow: hidden; }
            .book-page[hidden] { display: none; }
            .book-page canvas, .book-face canvas { display: block; width: 100%; height: 100%; }
            .book-page.is-blank, .book-face.is-blank { background: #faf8f3; }

            /* เงาหนังสือ + สันหนังสือตรงกลาง */
            .book-page { box-shadow: 0 10px 30px rgba(60, 45, 20, .25); }
            .book.is-spread .book-page.left { border-radius: 4px 0 0 4px; }
            .book.is-spread .book-page.right { border-radius: 0 4px 4px 0; }
            .book.is-spread .book-page.left::after, .book.is-spread .book-page.right::after { content: ''; position: absolute; top: 0; bottom: 0; width: 12%; pointer-events: none; }
            .book.is-spread .book-page.left::after { right: 0; background: linear-gradient(to left, rgba(0,0,0,.18), rgba(0,0,0,0)); }
            .book.is-spread .book-page.right::after { left: 0; background: linear-gradient(to right, rgba(0,0,0,.18), rgba(0,0,0,0)); }
            /* ปกหน้า/ปกหลัง: ซ่อนหน้าว่างฝั่งตรงข้าม ให้ดูเหมือนหนังสือปิด */
            .book.is-cover { transform: translateX(-25%); }
            .book.is-cover .book-page.left { visibility: hidden; }
            .book.is-back { transform: translateX(25%); }
            .book.is-back .book-page.right { visibility: hidden; }

            /* แผ่นที่กำลังพลิก */
            .book-leaf { position: absolute; top: 0; transform-style: preserve-3d; z-index: 3; pointer-events: none; }
            .book-leaf[hidden] { display: none; }
            .book-leaf.from-right { left: 50%; transform-origin: left center; }
            .book-leaf.from-left { left: 0; transform-origin: right center; }
            .book-leaf.single { left: 0; transform-origin: left center; }
            .book-face { position: absolute; inset: 0; backface-visibility: hidden; box-shadow: 0 10px 30px rgba(60, 45, 20, .3); }
            .book-face.back { transform: rotateY(180deg); }
            .book-face::after { content: ''; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(to right, rgba(0,0,0,.12), rgba(0,0,0,0) 30%); }
            .book-leaf.from-left .book-face.front::after, .book-leaf.from-right .book-face.back::after { background: linear-gradient(to left, rgba(0,0,0,.12), rgba(0,0,0,0) 30%); }

            .book-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .5rem; }
            .book-toolbar button:disabled { opacity: .4; cursor: not-allowed; }

            @media (max-width: 640px) { .book-stage { height: 68vh; } }
        </style>
    @endpush

    @push('scripts')
        @vite('resources/js/book-reader.js')
    @endpush
@endonce
