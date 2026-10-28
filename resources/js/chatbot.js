/**
 * ผู้ช่วยห้องสมุด (แชทบอท)
 * - ข้อความผู้ใช้แสดงแบบ text เสมอ (กัน XSS)
 * - คำตอบของบอทผ่านการกรอง HTML ให้เหลือเฉพาะแท็ก/ลิงก์ที่อนุญาต
 */

const ALLOWED_TAGS = new Set(['A', 'B', 'STRONG', 'I', 'EM', 'P', 'BR', 'UL', 'OL', 'LI', 'DIV', 'SPAN', 'IMG', 'SMALL']);
const DEFAULT_SUGGESTIONS = ['หนังสือมาใหม่', 'หนังสือยอดนิยม', 'หนังสือที่ฉันยืมอยู่', 'ยืมได้กี่เล่ม', 'มีหมวดอะไรบ้าง'];

function sanitize(html) {
    const doc = new DOMParser().parseFromString(`<div>${html}</div>`, 'text/html');
    const root = doc.body.firstElementChild;

    const walk = (node) => {
        [...node.children].forEach((child) => {
            if (!ALLOWED_TAGS.has(child.tagName)) {
                child.replaceWith(doc.createTextNode(child.textContent || ''));
                return;
            }

            [...child.attributes].forEach((attr) => {
                const name = attr.name.toLowerCase();
                const value = attr.value.trim();
                const keep =
                    name === 'class' ||
                    name === 'alt' ||
                    name === 'loading' ||
                    (name === 'href' && /^(\/|https?:\/\/)/i.test(value) && !/^javascript:/i.test(value)) ||
                    (name === 'src' && /^(\/|https?:\/\/)/i.test(value));

                if (!keep) child.removeAttribute(attr.name);
            });

            walk(child);
        });
    };

    walk(root);

    return root.innerHTML;
}

export function initChatbot() {
    const root = document.getElementById('chatbot-root');
    const button = document.getElementById('chatbot-button');
    const box = document.getElementById('chatbot-box');
    const body = document.getElementById('chat-body');
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-input');
    const chips = document.getElementById('chat-suggestions');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let loaded = false;
    let busy = false;

    const scroll = () => {
        body.scrollTop = body.scrollHeight;
    };

    const addUser = (text) => {
        const row = document.createElement('div');
        row.className = 'flex justify-end';
        const bubble = document.createElement('div');
        bubble.className = 'max-w-[85%] rounded-2xl rounded-br-md bg-ink-900 px-3.5 py-2 text-sm text-white';
        bubble.textContent = text;
        row.appendChild(bubble);
        body.appendChild(row);
        scroll();
    };

    const addBot = (html) => {
        const row = document.createElement('div');
        row.className = 'flex gap-2';
        row.innerHTML =
            '<span class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gold-400 text-xs text-ink-950"><i class="bi bi-stars"></i></span>';
        const bubble = document.createElement('div');
        bubble.className = 'chat-bot max-w-[88%] rounded-2xl rounded-tl-md border border-ink-100 bg-white px-3.5 py-2.5 text-sm text-ink-800 shadow-sm';
        bubble.innerHTML = sanitize(html);
        row.appendChild(bubble);
        body.appendChild(row);
        scroll();
        return row;
    };

    const typing = () => {
        const row = addBot('<span class="chat-typing"><span></span><span></span><span></span></span>');
        return () => row.remove();
    };

    const setSuggestions = (list) => {
        chips.innerHTML = '';
        (list && list.length ? list : DEFAULT_SUGGESTIONS).forEach((text) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className =
                'shrink-0 rounded-full border border-ink-200 bg-white px-3 py-1 text-xs font-medium text-ink-700 hover:border-gold-400 hover:bg-gold-50';
            chip.textContent = text;
            chip.addEventListener('click', () => send(text));
            chips.appendChild(chip);
        });
    };

    const welcome = () => {
        const name = root.dataset.user || '';
        addBot(
            `<p>สวัสดีครับคุณ${name.replace(/[<>&"]/g, '')} 👋</p><p>ผมช่วยค้นหา Ebook ดูสถานะการยืม และตอบเรื่องสิทธิ์การยืมได้ ลองพิมพ์ชื่อหนังสือหรือหัวข้อที่สนใจ หรือกดคำแนะนำด้านล่างได้เลยครับ</p>`
        );
        setSuggestions();
    };

    const loadHistory = async () => {
        if (loaded) return;
        loaded = true;

        try {
            const messages = await fetch(root.dataset.historyUrl, { headers: { Accept: 'application/json' } }).then((r) => r.json());
            body.innerHTML = '';

            if (!messages.length) {
                welcome();
                return;
            }

            messages.forEach((m) => (m.sender === 'user' ? addUser(m.message) : addBot(m.message)));
            setSuggestions();
        } catch (e) {
            body.innerHTML = '';
            welcome();
        }
    };

    const send = async (text) => {
        const message = (text || '').trim();
        if (!message || busy) return;

        busy = true;
        addUser(message);
        input.value = '';
        const stop = typing();

        try {
            const response = await fetch(root.dataset.chatUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ message }),
            });

            stop();

            if (response.status === 429) {
                addBot('<p>ส่งข้อความถี่เกินไป กรุณารอสักครู่แล้วลองใหม่ครับ</p>');
            } else if (!response.ok) {
                addBot('<p>ขออภัย ระบบขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้ง</p>');
            } else {
                const data = await response.json();
                addBot(data.reply);
                setSuggestions(data.suggestions);
            }
        } catch (e) {
            stop();
            addBot('<p>เชื่อมต่อไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่</p>');
        } finally {
            busy = false;
            input.focus();
        }
    };

    const open = () => {
        box.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        loadHistory();
        setTimeout(() => input.focus(), 50);
    };

    const close = () => {
        box.hidden = true;
        button.setAttribute('aria-expanded', 'false');
    };

    button.addEventListener('click', () => (box.hidden ? open() : close()));
    document.getElementById('close-chat').addEventListener('click', close);
    document.addEventListener('keydown', (e) => e.key === 'Escape' && !box.hidden && close());

    document.getElementById('chat-clear').addEventListener('click', async () => {
        await fetch(root.dataset.clearUrl, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
        body.innerHTML = '';
        welcome();
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        send(input.value);
    });

    // เปิดแชทจากปุ่มอื่นในหน้า: <button data-open-chat="ข้อความ">
    document.querySelectorAll('[data-open-chat]').forEach((el) =>
        el.addEventListener('click', () => {
            open();
            if (el.dataset.openChat) setTimeout(() => send(el.dataset.openChat), 300);
        })
    );
}
