import './bootstrap';

import '@fontsource/sarabun/300.css';
import '@fontsource/sarabun/400.css';
import '@fontsource/sarabun/500.css';
import '@fontsource/sarabun/600.css';
import '@fontsource/sarabun/700.css';
import '@fontsource/noto-serif-thai/600.css';
import '@fontsource/noto-serif-thai/700.css';
import 'bootstrap-icons/font/bootstrap-icons.css';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

window.Alpine = Alpine;
window.Swal = Swal.mixin({
    confirmButtonColor: '#172649',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'ตกลง',
    cancelButtonText: 'ยกเลิก',
    customClass: { popup: 'rounded-2xl' },
});

Alpine.start();

/* --------------------------------------------------------------------------
 | ข้อความแจ้งผลจากระบบ (session flash) → Toast มุมขวาบน
 * -------------------------------------------------------------------------- */
function showFlash() {
    const el = document.getElementById('flash-data');
    if (!el) return;

    try {
        const flash = JSON.parse(el.textContent || '{}');
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4500,
            timerProgressBar: true,
        });

        if (flash.success) Toast.fire({ icon: 'success', title: flash.success });
        if (flash.error) Toast.fire({ icon: 'error', title: flash.error, timer: 7000 });
    } catch (e) {
        /* ignore */
    }
}

/* --------------------------------------------------------------------------
 | ฟอร์มที่ต้องยืนยันก่อนส่ง: <form data-confirm="ข้อความ" data-confirm-title="..">
 * -------------------------------------------------------------------------- */
function bindConfirmForms() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed) return;

        event.preventDefault();

        window.Swal.fire({
            title: form.dataset.confirmTitle || 'ยืนยันการทำรายการ',
            text: form.dataset.confirm,
            icon: form.dataset.confirmIcon || 'question',
            showCancelButton: true,
            confirmButtonText: form.dataset.confirmButton || 'ยืนยัน',
            confirmButtonColor: form.dataset.confirmDanger ? '#dc2626' : '#172649',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    });
}

/* --------------------------------------------------------------------------
 | โหลดโมดูลเฉพาะหน้าที่ต้องใช้ (ลดขนาดไฟล์หน้าอื่น)
 * -------------------------------------------------------------------------- */
function lazyModules() {
    // ผู้ดูแล: สร้างปกจาก PDF หน้าแรก + ไฟล์ตัวอย่าง สำหรับหนังสือที่ยังไม่มี
    if (document.querySelector('[data-derive-url]')) {
        import('./pdf-tools').then((m) => m.backfillDerivedFiles());
    }

    // ฟอร์มเพิ่ม/แก้ไข Ebook: สร้างปกจากหน้าแรกของ PDF ที่เลือก
    if (document.querySelector('[data-pdf-input]')) {
        import('./pdf-tools').then((m) => m.bindPdfUploadForm());
    }

    if (document.querySelector('[data-dashboard]')) {
        import('./dashboard').then((m) => m.initDashboard());
    }

    if (document.getElementById('chatbot-root')) {
        import('./chatbot').then((m) => m.initChatbot());
    }
}

function boot() {
    showFlash();
    bindConfirmForms();
    lazyModules();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
