(function () {
    const page = document.querySelector('.stats-page');
    if (!page || typeof Chart === 'undefined') {
        return;
    }

    let payload = {};
    try {
        payload = JSON.parse(page.getAttribute('data-stats') || '{}');
    } catch {
        return;
    }

    const teal = '#0d9488';
    const tealSoft = 'rgba(13, 148, 136, 0.18)';
    const slate = '#0f172a';
    const muted = '#94a3b8';

    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = muted;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;

    const money = (value) => `${Number(value || 0).toLocaleString('uk-UA')} ₴`;

    const daily = payload.daily || { labels: [], orders: [], revenue: [] };
    const statusGroups = payload.statusGroups || { labels: [], values: [], colors: [] };
    const sources = payload.sources || { labels: [], values: [] };
    const topProducts = payload.topProducts || { labels: [], values: [] };

    const dailyEl = document.getElementById('statsDailyChart');
    if (dailyEl) {
        new Chart(dailyEl, {
            type: 'line',
            data: {
                labels: daily.labels || [],
                datasets: [
                    {
                        label: 'Замовлення',
                        data: daily.orders || [],
                        borderColor: teal,
                        backgroundColor: tealSoft,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Виручка',
                        data: daily.revenue || [],
                        borderColor: slate,
                        backgroundColor: 'rgba(15, 23, 42, 0.08)',
                        fill: false,
                        tension: 0.35,
                        pointRadius: 2,
                        pointHoverRadius: 4,
                        yAxisID: 'y1',
                        borderDash: [5, 4],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end' },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                if (ctx.dataset.yAxisID === 'y1') {
                                    return `${ctx.dataset.label}: ${money(ctx.parsed.y)}`;
                                }
                                return `${ctx.dataset.label}: ${ctx.parsed.y}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0, autoSkipPadding: 12 },
                    },
                    y: {
                        position: 'left',
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(148, 163, 184, 0.18)' },
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback(value) {
                                return Number(value).toLocaleString('uk-UA');
                            },
                        },
                    },
                },
            },
        });
    }

    const statusEl = document.getElementById('statsStatusChart');
    if (statusEl) {
        new Chart(statusEl, {
            type: 'doughnut',
            data: {
                labels: statusGroups.labels || [],
                datasets: [{
                    data: statusGroups.values || [],
                    backgroundColor: statusGroups.colors || [],
                    borderWidth: 0,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom' },
                },
            },
        });
    }

    const sourcesEl = document.getElementById('statsSourcesChart');
    if (sourcesEl) {
        new Chart(sourcesEl, {
            type: 'bar',
            data: {
                labels: sources.labels || [],
                datasets: [{
                    label: 'Замовлення',
                    data: sources.values || [],
                    backgroundColor: teal,
                    borderRadius: 8,
                    maxBarThickness: 42,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(148, 163, 184, 0.18)' },
                    },
                },
            },
        });
    }

    const productsEl = document.getElementById('statsProductsChart');
    if (productsEl) {
        new Chart(productsEl, {
            type: 'bar',
            data: {
                labels: topProducts.labels || [],
                datasets: [{
                    label: 'Кількість',
                    data: topProducts.values || [],
                    backgroundColor: 'rgba(13, 148, 136, 0.75)',
                    borderRadius: 8,
                    maxBarThickness: 28,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 },
                        grid: { color: 'rgba(148, 163, 184, 0.18)' },
                    },
                    y: {
                        grid: { display: false },
                        ticks: { autoSkip: false },
                    },
                },
            },
        });
    }
})();
