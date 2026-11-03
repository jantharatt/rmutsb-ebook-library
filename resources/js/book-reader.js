/**
 * ตัวอ่าน Ebook แบบเปิดหนังสือ (pdf.js + พลิกหน้าด้วย CSS 3D)
 *
 * - จอกว้าง: แสดงสองหน้าคู่ (ปกอยู่หน้าขวาเดี่ยว เหมือนหนังสือจริง)
 * - จอแคบ: แสดงทีละหน้า
 * - พลิกหน้าได้ด้วยปุ่ม คลิกครึ่งซ้าย/ขวา ปุ่มลูกศร หรือปัดหน้าจอ
 *
 * ใช้: <div data-book-reader data-src="URL ของ PDF"> ... </div>
 */
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

const FLIP_MS = 650;
const SPREAD_MIN_WIDTH = 768;
const RENDER_QUALITY = 2; // ตัวคูณความละเอียดเทียบกับความหนาแน่นพิกเซลของจอ
const ZOOM_MIN = 1;
const ZOOM_MAX = 3;
const ZOOM_STEP = 0.25;
const MAX_CANVAS_PIXELS = 16_000_000;

class BookReader {
    constructor(root) {
        this.root = root;
        this.src = root.dataset.src;
        this.stage = root.querySelector('[data-book-stage]');
        this.book = root.querySelector('[data-book]');
        this.left = root.querySelector('[data-page-left]');
        this.right = root.querySelector('[data-page-right]');
        this.leaf = root.querySelector('[data-leaf]');
        this.leafFront = root.querySelector('[data-leaf-front]');
        this.leafBack = root.querySelector('[data-leaf-back]');
        this.status = root.querySelector('[data-book-status]');
        this.pageInput = root.querySelector('[data-page-input]');
        this.pageTotal = root.querySelector('[data-page-total]');

        this.pdf = null;
        this.cache = new Map(); // pageNum -> Promise<canvas>
        this.page = 1; // หน้าแรกที่แสดงอยู่ (หน้าซ้ายในโหมดคู่ หรือหน้าเดียว)
        this.busy = false;
        this.zoom = 1;
        this.pageSize = { w: 0, h: 0 };
        this.aspect = 0.707;
    }

    async init() {
        try {
            this.pdf = await pdfjsLib.getDocument({ url: this.src, withCredentials: true }).promise;
        } catch (e) {
            this.setStatus('ไม่สามารถเปิดไฟล์ Ebook ได้ กรุณาลองใหม่อีกครั้ง');
            return;
        }

        const first = await this.pdf.getPage(1);
        const vp = first.getViewport({ scale: 1 });
        this.aspect = vp.width / vp.height;
        this.pageTotal.textContent = this.pdf.numPages;
        this.pageInput.max = this.pdf.numPages;

        this.bindEvents();
        this.layout();
        this.setStatus('');
        this.root.classList.add('is-ready');
    }

    setStatus(text) {
        this.status.textContent = text;
        this.status.hidden = !text;
    }

    get spread() {
        return this.stage.offsetWidth >= SPREAD_MIN_WIDTH;
    }

    /** หน้าที่อยู่ซ้าย/ขวาของคู่ที่มีหน้า p (ปก = หน้า 1 อยู่ขวาเดี่ยว) */
    spreadOf(p) {
        if (!this.spread) return { left: null, right: p };
        const left = p % 2 === 0 ? p : p - 1;
        return { left: left >= 1 ? left : null, right: left + 1 <= this.pdf.numPages ? left + 1 : null };
    }

    /** หน้าแรกของคู่ถัดไป/ก่อนหน้า */
    step(dir) {
        if (!this.spread) return this.page + dir;
        const { left } = this.spreadOf(this.page);
        const base = left ?? 0;
        return dir > 0 ? base + 2 : base - 2 < 0 ? 0 : Math.max(1, base - 2);
    }

    layout() {
        const W = this.stage.offsetWidth - 24;
        const H = this.stage.offsetHeight - 24;
        const cols = this.spread ? 2 : 1;
        let h = H;
        let w = h * this.aspect;
        if (w * cols > W) {
            w = W / cols;
            h = w / this.aspect;
        }
        w = Math.floor(w * this.zoom);
        h = Math.floor(h * this.zoom);

        if (w !== this.pageSize.w || h !== this.pageSize.h) {
            this.pageSize = { w, h };
            this.cache.clear();
        }

        this.book.style.width = `${w * cols}px`;
        this.book.style.height = `${h}px`;
        this.book.classList.toggle('is-spread', this.spread);
        for (const el of [this.left, this.right, this.leaf]) {
            el.style.width = `${w}px`;
            el.style.height = `${h}px`;
        }
        this.left.hidden = !this.spread;
        this.root.classList.toggle('is-zoomed', this.zoom > 1);
        const label = this.root.querySelector('[data-zoom-label]');
        if (label) label.textContent = `${Math.round(this.zoom * 100)}%`;

        this.show(this.page);
    }

    /** ซูมโดยคงจุดกึ่งกลางที่กำลังดูไว้ */
    setZoom(z) {
        z = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, Math.round(z * 100) / 100));
        if (z === this.zoom) return;
        const s = this.stage;
        const cx = (s.scrollLeft + s.clientWidth / 2) / (s.scrollWidth || 1);
        const cy = (s.scrollTop + s.clientHeight / 2) / (s.scrollHeight || 1);
        this.zoom = z;
        this.layout();
        s.scrollLeft = cx * s.scrollWidth - s.clientWidth / 2;
        s.scrollTop = cy * s.scrollHeight - s.clientHeight / 2;
    }

    render(num) {
        if (!num || num < 1 || num > this.pdf.numPages) return Promise.resolve(null);
        if (!this.cache.has(num)) {
            this.cache.set(num, (async () => {
                const page = await this.pdf.getPage(num);
                const base = page.getViewport({ scale: 1 });
                // เรนเดอร์ละเอียดกว่าขนาดที่แสดง เพื่อให้ตัวอักษรคมชัด (สูงสุด 4 เท่า)
                let ratio = Math.min(4, Math.max(2, (window.devicePixelRatio || 1) * RENDER_QUALITY));
                // จำกัดขนาด canvas ไม่ให้ใหญ่เกินหน่วยความจำของเบราว์เซอร์ (โดยเฉพาะตอนซูม)
                const px = this.pageSize.w * this.pageSize.h * ratio * ratio;
                if (px > MAX_CANVAS_PIXELS) ratio *= Math.sqrt(MAX_CANVAS_PIXELS / px);
                const viewport = page.getViewport({ scale: (this.pageSize.h * ratio) / base.height });
                const canvas = document.createElement('canvas');
                canvas.width = Math.floor(viewport.width);
                canvas.height = Math.floor(viewport.height);
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                await page.render({ canvasContext: ctx, viewport }).promise;
                return canvas;
            })());
        }
        return this.cache.get(num);
    }

    /** วาดหน้า num ลงในช่อง (ว่างถ้าไม่มีหน้า) */
    async paint(slot, num) {
        let canvas = slot.querySelector('canvas');
        if (!canvas) {
            canvas = document.createElement('canvas');
            slot.prepend(canvas);
        }
        slot.classList.toggle('is-blank', !num);
        slot.dataset.num = num || '';
        const src = await this.render(num);
        if (slot.dataset.num !== String(num || '')) return; // มีการเปลี่ยนหน้าระหว่างโหลด
        if (!src) {
            canvas.width = 1;
            canvas.height = 1;
            return;
        }
        canvas.width = src.width;
        canvas.height = src.height;
        canvas.getContext('2d').drawImage(src, 0, 0);
    }

    async show(p) {
        this.page = Math.min(Math.max(1, p), this.pdf.numPages);
        const s = this.spreadOf(this.page);
        await Promise.all([this.spread ? this.paint(this.left, s.left) : null, this.paint(this.right, s.right)]);
        this.updateControls();
        this.preload();
    }

    preload() {
        const n = this.spread ? 4 : 2;
        for (let i = 1; i <= n; i++) {
            this.render(this.page + i);
            this.render(this.page - i);
        }
    }

    updateControls() {
        const s = this.spreadOf(this.page);
        this.pageInput.value = s.left ?? s.right;
        this.root.querySelector('[data-book-prev]').disabled = (s.left ?? s.right) <= 1;
        this.root.querySelector('[data-book-next]').disabled = (s.right ?? s.left) >= this.pdf.numPages;
        this.book.classList.toggle('is-cover', this.spread && !s.left);
        this.book.classList.toggle('is-back', this.spread && !s.right);
    }

    async flip(dir) {
        if (this.busy) return;
        const target = this.step(dir);
        if (target < 1 || target > this.pdf.numPages) return;

        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) return this.show(target);

        this.busy = true;
        const cur = this.spreadOf(this.page);
        const next = this.spreadOf(target);

        // แผ่นที่พลิก: ด้านหน้า = หน้าที่กำลังจะพลิกออก ด้านหลัง = หน้าที่จะปรากฏ
        const forward = dir > 0;
        const frontNum = this.spread ? (forward ? cur.right : cur.left) : cur.right;
        const backNum = this.spread ? (forward ? next.left : next.right) : null;

        await Promise.all([
            this.paint(this.leafFront, frontNum),
            this.paint(this.leafBack, backNum),
            this.render(next.left),
            this.render(next.right),
        ]);

        // ด้านล่างแผ่นที่พลิก แสดงหน้าปลายทางไว้ล่วงหน้า
        if (this.spread) {
            if (forward) await this.paint(this.right, next.right);
            else await this.paint(this.left, next.left);
        } else {
            await this.paint(this.right, next.right);
        }

        const leaf = this.leaf;
        leaf.className = `book-leaf ${forward ? 'from-right' : 'from-left'} ${this.spread ? '' : 'single'}`;
        leaf.hidden = false;
        leaf.style.transition = 'none';
        leaf.style.transform = 'rotateY(0deg)';
        if (!this.spread && !forward) {
            // หน้าเดียวย้อนกลับ: แผ่นใหม่พลิกกลับเข้ามา
            await this.paint(this.leafFront, next.right);
            await this.paint(this.right, cur.right);
            leaf.style.transform = 'rotateY(-180deg)';
        }
        leaf.getBoundingClientRect();
        leaf.style.transition = `transform ${FLIP_MS}ms cubic-bezier(.45,.05,.35,1)`;
        leaf.style.transform = !this.spread && !forward
            ? 'rotateY(0deg)'
            : `rotateY(${forward ? -180 : 180}deg)`;

        await new Promise((r) => setTimeout(r, FLIP_MS));
        await this.show(target);
        leaf.hidden = true;
        this.busy = false;
    }

    bindEvents() {
        this.root.querySelector('[data-book-prev]').addEventListener('click', () => this.flip(-1));
        this.root.querySelector('[data-book-next]').addEventListener('click', () => this.flip(1));
        this.root.querySelector('[data-book-first]')?.addEventListener('click', () => this.show(1));
        this.root.querySelector('[data-book-last]')?.addEventListener('click', () => this.show(this.pdf.numPages));
        this.root.querySelector('[data-book-fullscreen]')?.addEventListener('click', () => {
            if (document.fullscreenElement) document.exitFullscreen();
            else this.root.requestFullscreen?.();
        });

        this.pageInput.addEventListener('change', () => {
            const n = parseInt(this.pageInput.value, 10);
            if (n) this.show(n);
        });

        // ซูม
        this.root.querySelector('[data-zoom-in]')?.addEventListener('click', () => this.setZoom(this.zoom + ZOOM_STEP));
        this.root.querySelector('[data-zoom-out]')?.addEventListener('click', () => this.setZoom(this.zoom - ZOOM_STEP));
        this.root.querySelector('[data-zoom-reset]')?.addEventListener('click', () => this.setZoom(1));
        this.stage.addEventListener('wheel', (e) => {
            if (!e.ctrlKey) return; // Ctrl + ล้อเมาส์ (และ pinch บน touchpad)
            e.preventDefault();
            this.setZoom(this.zoom + (e.deltaY < 0 ? ZOOM_STEP : -ZOOM_STEP));
        }, { passive: false });

        // ขณะซูม: ลากเพื่อเลื่อนดูหน้า
        let drag = null;
        this.stage.addEventListener('pointerdown', (e) => {
            if (this.zoom <= 1 || e.pointerType !== 'mouse') return;
            drag = { x: e.clientX, y: e.clientY, left: this.stage.scrollLeft, top: this.stage.scrollTop };
            this.stage.classList.add('is-dragging');
        });
        window.addEventListener('pointermove', (e) => {
            if (!drag) return;
            this.stage.scrollLeft = drag.left - (e.clientX - drag.x);
            this.stage.scrollTop = drag.top - (e.clientY - drag.y);
        });
        window.addEventListener('pointerup', () => {
            drag = null;
            this.stage.classList.remove('is-dragging');
        });

        // คลิกครึ่งซ้าย/ขวาของหนังสือเพื่อพลิก (ปิดไว้ขณะซูม เพื่อให้ลากดูได้)
        this.book.addEventListener('click', (e) => {
            if (this.zoom > 1) return;
            if (this.book.classList.contains('is-cover')) return this.flip(1);
            if (this.book.classList.contains('is-back')) return this.flip(-1);
            const rect = this.book.getBoundingClientRect();
            this.flip(e.clientX - rect.left < rect.width / 2 ? -1 : 1);
        });

        document.addEventListener('keydown', (e) => {
            if (e.target.closest('input, textarea, select')) return;
            if (e.key === 'ArrowRight' || e.key === 'PageDown') this.flip(1);
            if (e.key === 'ArrowLeft' || e.key === 'PageUp') this.flip(-1);
            if (e.key === '+' || e.key === '=') this.setZoom(this.zoom + ZOOM_STEP);
            if (e.key === '-') this.setZoom(this.zoom - ZOOM_STEP);
            if (e.key === '0') this.setZoom(1);
        });

        let startX = null;
        this.stage.addEventListener('touchstart', (e) => { startX = e.touches.length === 1 ? e.touches[0].clientX : null; }, { passive: true });
        this.stage.addEventListener('touchend', (e) => {
            if (startX === null || this.zoom > 1) return;
            const dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 40) this.flip(dx < 0 ? 1 : -1);
            startX = null;
        });

        // ป้องกันการคลิกขวาบันทึกภาพหน้า
        this.root.addEventListener('contextmenu', (e) => e.preventDefault());

        let timer;
        new ResizeObserver(() => {
            clearTimeout(timer);
            timer = setTimeout(() => this.layout(), 150);
        }).observe(this.stage);
    }
}

document.querySelectorAll('[data-book-reader]').forEach((el) => new BookReader(el).init());
