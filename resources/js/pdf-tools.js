/**
 * เครื่องมือ PDF ฝั่งเบราว์เซอร์ (ใช้กับผู้ดูแลระบบเท่านั้น)
 *
 * - สร้างรูปปกจากหน้าแรกของ PDF (pdf.js)
 * - สร้างไฟล์ตัวอย่าง 5 หน้าแรก (pdf-lib)
 *
 * ใช้เมื่อเซิร์ฟเวอร์ไม่มีเครื่องมือแปลง PDF (เช่น XAMPP บน Windows)
 * ผลลัพธ์ถูกส่งกลับไปเก็บที่เซิร์ฟเวอร์ ผู้ใช้ทั่วไปจึงไม่ต้องดาวน์โหลด PDF ฉบับเต็มเพื่อดูปก
 */
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

const PREVIEW_PAGES = 5;
const COVER_WIDTH = 900;

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/** เรนเดอร์หน้าแรกเป็นไฟล์ JPEG */
export async function renderCover(pdfData) {
    const loadingTask = pdfjsLib.getDocument({ data: pdfData.slice(0) });
    const pdf = await loadingTask.promise;

    try {
        const page = await pdf.getPage(1);
        const base = page.getViewport({ scale: 1 });
        const scale = Math.min(3, COVER_WIDTH / base.width);
        const viewport = page.getViewport({ scale });

        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);

        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        await page.render({ canvas, canvasContext: ctx, viewport }).promise;
        page.cleanup();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));

        return { blob, numPages: pdf.numPages, dataUrl: canvas.toDataURL('image/jpeg', 0.6) };
    } finally {
        try {
            await loadingTask.destroy();
        } catch (e) {
            /* ignore */
        }
    }
}

/** ตัด 5 หน้าแรกเป็นไฟล์ PDF ใหม่ */
export async function buildPreview(pdfData) {
    const { PDFDocument } = await import('pdf-lib');

    const source = await PDFDocument.load(pdfData, { ignoreEncryption: true, updateMetadata: false });
    const out = await PDFDocument.create();
    const count = Math.min(PREVIEW_PAGES, source.getPageCount());
    const pages = await out.copyPages(source, [...Array(count).keys()]);

    pages.forEach((p) => out.addPage(p));

    const bytes = await out.save();

    return new Blob([bytes], { type: 'application/pdf' });
}

/* --------------------------------------------------------------------------
 | ฟอร์มเพิ่ม/แก้ไข Ebook
 * -------------------------------------------------------------------------- */
export function bindPdfUploadForm() {
    document.querySelectorAll('[data-pdf-input]').forEach((input) => {
        const form = input.closest('form');
        if (!form) return;

        const coverInput = form.querySelector('input[name="cover"]');
        const status = form.querySelector('[data-auto-cover-status]');
        const previewImg = form.querySelector('[data-auto-cover-preview]');

        const ensureHidden = (name, accept) => {
            let el = form.querySelector(`input[type=file][name="${name}"]`);
            if (!el) {
                el = document.createElement('input');
                el.type = 'file';
                el.name = name;
                el.accept = accept;
                el.hidden = true;
                form.appendChild(el);
            }
            return el;
        };

        const setFile = (el, file) => {
            const dt = new DataTransfer();
            if (file) dt.items.add(file);
            el.files = dt.files;
        };

        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            const autoCover = ensureHidden('auto_cover', 'image/jpeg');
            const autoPreview = ensureHidden('auto_preview', 'application/pdf');

            setFile(autoCover, null);
            setFile(autoPreview, null);

            if (!file) return;

            if (status) status.textContent = 'กำลังอ่านหน้าแรกของ PDF เพื่อสร้างปก...';

            try {
                const data = new Uint8Array(await file.arrayBuffer());
                const cover = await renderCover(data);

                setFile(autoCover, new File([cover.blob], 'auto-cover.jpg', { type: 'image/jpeg' }));

                if (previewImg) {
                    previewImg.src = cover.dataUrl;
                    previewImg.closest('[data-auto-cover-wrap]')?.classList.remove('hidden');
                }

                const pagesInput = form.querySelector('input[name="total_pages"]');
                if (pagesInput && (!pagesInput.value || pagesInput.value === '0')) {
                    pagesInput.value = cover.numPages;
                }

                try {
                    const preview = await buildPreview(data);
                    setFile(autoPreview, new File([preview], 'preview.pdf', { type: 'application/pdf' }));
                } catch (e) {
                    console.warn('สร้างไฟล์ตัวอย่างในเบราว์เซอร์ไม่สำเร็จ', e);
                }

                if (status) {
                    status.textContent = coverInput?.files?.length
                        ? 'มีรูปปกที่อัปโหลดแล้ว ระบบจะใช้รูปปกที่อัปโหลด'
                        : `พร้อมใช้หน้าแรกเป็นปก (PDF ${cover.numPages} หน้า)`;
                }
            } catch (e) {
                console.warn(e);
                if (status) status.textContent = 'อ่าน PDF ในเบราว์เซอร์ไม่ได้ ระบบจะลองสร้างปกที่เซิร์ฟเวอร์แทน';
            }
        });
    });
}

/* --------------------------------------------------------------------------
 | เติมปก/ตัวอย่างให้หนังสือเดิมที่ยังไม่มี (ทำทีละเล่มเบื้องหลัง)
 * -------------------------------------------------------------------------- */
export async function backfillDerivedFiles() {
    const items = [...document.querySelectorAll('[data-derive-url]')];
    const seen = new Set();

    // แทนปกจำลองด้วยปกจริงทุกตำแหน่งในหน้า
    const applyResult = (url, saved, done) => {
        document.querySelectorAll(`[data-derive-url="${url}"]`).forEach((node) => {
            if (done) node.removeAttribute('data-derive-url');
            if (!saved) return;

            if (node.classList.contains('book-cover')) {
                if (saved.cover_url && !node.querySelector('img')) {
                    const img = document.createElement('img');
                    img.src = saved.cover_url;
                    img.alt = node.getAttribute('aria-label') || '';
                    node.querySelector('.book-cover-placeholder')?.remove();
                    node.prepend(img);
                }
            } else if (done) {
                node.innerHTML = '<i class="bi bi-check2-circle"></i> สร้างปกและไฟล์ตัวอย่างจาก PDF เรียบร้อย';
            }
        });
    };

    for (const el of items) {
        const url = el.dataset.deriveUrl;
        if (!url || seen.has(url)) continue;
        seen.add(url);

        const needCover = items.some((n) => n.dataset.deriveUrl === url && n.dataset.needCover === '1');
        const needPreview = items.some((n) => n.dataset.deriveUrl === url && n.dataset.needPreview === '1');
        let saved = null;

        try {
            const response = await fetch(el.dataset.streamUrl, { credentials: 'same-origin' });
            if (!response.ok) {
                console.warn('เปิดไฟล์ PDF ไม่ได้', response.status, el.dataset.streamUrl);
                continue;
            }

            const data = new Uint8Array(await response.arrayBuffer());
            const post = (form) =>
                fetch(url, {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                }).then((r) => (r.ok ? r.json() : null));

            // 1) ส่งปกก่อน (เร็ว) เพื่อให้ปกแสดงทันที
            if (needCover) {
                const cover = await renderCover(data);
                const form = new FormData();
                form.append('auto_cover', new File([cover.blob], 'auto-cover.jpg', { type: 'image/jpeg' }));
                form.append('total_pages', cover.numPages);
                saved = await post(form);
                applyResult(url, saved, false);
            }

            // 2) ไฟล์ตัวอย่าง 5 หน้า (ไฟล์ใหญ่อาจใช้เวลานาน)
            if (needPreview) {
                try {
                    const preview = await buildPreview(data);
                    const form = new FormData();
                    form.append('auto_preview', new File([preview], 'preview.pdf', { type: 'application/pdf' }));
                    saved = (await post(form)) || saved;
                } catch (e) {
                    console.warn('สร้างไฟล์ตัวอย่างไม่สำเร็จ', e);
                }
            }
        } catch (e) {
            console.warn('สร้างปกอัตโนมัติไม่สำเร็จ', e);
        }

        applyResult(url, saved, true);
    }
}
