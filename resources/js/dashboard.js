/**
 * กราฟแดชบอร์ดผู้ดูแลระบบ (Chart.js)
 * สี: ยืม #4061b8 / คืน #c27c00 — ผ่านการตรวจความต่างสำหรับผู้มีภาวะตาบอดสี
 */
import {
    Chart,
    BarController,
    LineController,
    BarElement,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

Chart.register(BarController, LineController, BarElement, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

const BLUE = '#4061b8';
const GOLD = '#c27c00';
const INK = '#3a5598';
const GRID = '#e3e9f5';

Chart.defaults.font.family = 'Sarabun, system-ui, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = INK;

const tooltip = {
    backgroundColor: '#0f1a35',
    padding: 10,
    cornerRadius: 8,
    titleFont: { weight: '600' },
    callbacks: {
        label: (ctx) => ` ${ctx.dataset.label}: ${Number(ctx.parsed.y ?? ctx.parsed.x).toLocaleString('th-TH')} ครั้ง`,
    },
};

function horizontalBar(id, labels, values, label) {
    const el = document.getElementById(id);
    if (!el) return;

    new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: [{ label, data: values, backgroundColor: BLUE, borderRadius: 4, borderSkipped: 'start', barThickness: 16 }],
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...tooltip,
                    callbacks: { label: (ctx) => ` ${Number(ctx.parsed.x).toLocaleString('th-TH')} ${label}` },
                },
            },
            scales: {
                x: { beginAtZero: true, grid: { color: GRID }, border: { display: false }, ticks: { precision: 0 } },
                y: { grid: { display: false }, border: { display: false }, ticks: { callback(v) { const t = this.getLabelForValue(v); return t.length > 26 ? t.slice(0, 25) + '…' : t; } } },
            },
        },
    });
}

export function initDashboard() {
    const raw = document.getElementById('dashboard-data');
    if (!raw) return;

    const data = JSON.parse(raw.textContent);
    const trend = data.trend;
    const manyPoints = trend.labels.length > 40;

    const trendEl = document.getElementById('trendChart');
    if (trendEl) {
        new Chart(trendEl, {
            type: 'bar',
            data: {
                labels: trend.labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'ยืม',
                        data: trend.borrows,
                        backgroundColor: BLUE,
                        borderRadius: 4,
                        borderSkipped: 'start',
                        maxBarThickness: 22,
                        order: 2,
                    },
                    {
                        type: 'line',
                        label: 'คืน',
                        data: trend.returns,
                        borderColor: GOLD,
                        backgroundColor: GOLD,
                        borderWidth: 2,
                        pointRadius: manyPoints ? 0 : 3,
                        pointHoverRadius: 5,
                        tension: 0.3,
                        order: 1,
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8, boxHeight: 8 } },
                    tooltip,
                },
                scales: {
                    x: { grid: { display: false }, border: { color: GRID }, ticks: { maxRotation: 0, autoSkipPadding: 12 } },
                    y: { beginAtZero: true, grid: { color: GRID }, border: { display: false }, ticks: { precision: 0 } },
                },
            },
        });
    }

    horizontalBar('categoryChart', data.category.labels, data.category.values, 'ครั้ง');
    horizontalBar('collectionChart', data.collection.labels, data.collection.values, 'เล่ม');
}
