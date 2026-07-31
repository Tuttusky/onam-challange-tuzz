import Chart from 'chart.js/auto';
import '../css/admin.css';

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initReportCharts();
});

function initSidebar() {
    const body = document.body;
    if (!body.classList.contains('admin-body')) {
        return;
    }

    let backdrop = document.querySelector('.admin-sidebar-backdrop');
    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'admin-sidebar-backdrop';
        body.appendChild(backdrop);
    }

    const closeSidebar = () => body.classList.remove('sidebar-open');
    const toggleSidebar = () => body.classList.toggle('sidebar-open');

    document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar();
        });
    });

    backdrop.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
}

function initReportCharts() {
    const trendCanvas = document.getElementById('visitsTrendChart');
    const eventsCanvas = document.getElementById('eventsBreakdownChart');

    if (trendCanvas) {
        const labels = JSON.parse(trendCanvas.dataset.labels || '[]');
        const values = JSON.parse(trendCanvas.dataset.values || '[]');

        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Visits',
                        data: values,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.12)',
                        fill: true,
                        tension: 0.35,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(255,255,255,0.05)' },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(255,255,255,0.05)' },
                    },
                },
            },
        });
    }

    if (eventsCanvas) {
        const labels = JSON.parse(eventsCanvas.dataset.labels || '[]');
        const values = JSON.parse(eventsCanvas.dataset.values || '[]');

        new Chart(eventsCanvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data: values,
                        backgroundColor: ['#6366f1', '#818cf8', '#64748b', '#38bdf8', '#a78bfa'],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#cbd5e1' },
                    },
                },
            },
        });
    }
}
