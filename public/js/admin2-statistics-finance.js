(function () {
    const page = document.querySelector('.stats-page--finance');
    if (!page || typeof Chart === 'undefined') {
        return;
    }

    let payload = {};
    try {
        payload = JSON.parse(page.getAttribute('data-finance') || '{}');
    } catch {
        return;
    }

    const green = '#059669';
    const red = '#dc2626';
    const muted = '#94a3b8';
    // High-contrast hues so neighboring bars are easy to tell apart.
    const cashPalette = [
        '#e11d48', // rose
        '#2563eb', // blue
        '#ca8a04', // gold
        '#7c3aed', // violet
        '#ea580c', // orange
        '#0d9488', // teal
        '#db2777', // pink
        '#84cc16', // lime
        '#1d4ed8', // indigo
        '#b45309', // amber
        '#0891b2', // cyan
        '#be123c', // crimson
    ];

    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = muted;
    Chart.defaults.plugins.legend.labels.boxWidth = 12;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;

    const money = (value, code) => {
        const amount = Number(value || 0).toLocaleString('uk-UA');
        return code ? `${amount} ${code}` : amount;
    };

    const hexToRgba = (hex, alpha) => {
        const raw = hex.replace('#', '');
        const value = raw.length === 3
            ? raw.split('').map((ch) => ch + ch).join('')
            : raw;
        const int = parseInt(value, 16);
        const r = (int >> 16) & 255;
        const g = (int >> 8) & 255;
        const b = int & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    };

    const cashColors = (count) => Array.from({ length: count }, (_, index) => (
        hexToRgba(cashPalette[index % cashPalette.length], 0.88)
    ));

    const signColors = (values) => (values || []).map((value) => (
        Number(value) >= 0 ? 'rgba(5, 150, 105, 0.78)' : 'rgba(220, 38, 38, 0.78)'
    ));

    const renderHorizontalBars = (canvasId, chart, colorMode) => {
        const el = document.getElementById(canvasId);
        if (!el || !chart || !(chart.labels || []).length) {
            return;
        }

        const codes = chart.codes || [];
        const values = chart.values || [];
        const colors = colorMode === 'sign'
            ? signColors(values)
            : cashColors(values.length);

        new Chart(el, {
            type: 'bar',
            data: {
                labels: chart.labels || [],
                datasets: [{
                    label: 'Баланс',
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 8,
                    maxBarThickness: 28,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const code = codes[ctx.dataIndex] || '';
                                return money(ctx.parsed.x, code);
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.18)' },
                        ticks: {
                            callback(value) {
                                return Number(value).toLocaleString('uk-UA');
                            },
                        },
                    },
                    y: {
                        grid: { display: false },
                        ticks: { autoSkip: false },
                    },
                },
            },
        });
    };

    const renderCashShareChart = (canvasId, chart) => {
        const el = document.getElementById(canvasId);
        if (!el) {
            return;
        }

        const labels = chart?.labels || [];
        const codes = chart?.codes || [];
        const values = (chart?.values || []).map((value) => Math.max(0, Number(value || 0)));
        const colors = cashColors(values.length);

        if (!values.length || values.every((value) => value === 0)) {
            new Chart(el, {
                type: 'doughnut',
                data: {
                    labels: ['Немає даних'],
                    datasets: [{
                        data: [1],
                        backgroundColor: ['#e2e8f0'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '58%',
                    plugins: { legend: { display: false } },
                },
            });
            return;
        }

        new Chart(el, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 8,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '52%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10 },
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                const code = codes[ctx.dataIndex] || '';
                                const total = values.reduce((sum, value) => sum + value, 0);
                                const amount = Number(ctx.parsed || 0);
                                const share = total > 0 ? Math.round((amount / total) * 100) : 0;
                                return `${money(amount, code)} · ${share}%`;
                            },
                        },
                    },
                },
            },
        });
    };

    const renderDebtStructureChart = (canvasId, owed, owe) => {
        const el = document.getElementById(canvasId);
        if (!el) {
            return;
        }

        const codeSet = new Set();
        (owed || []).forEach((item) => {
            if (item?.code) {
                codeSet.add(String(item.code));
            }
        });
        (owe || []).forEach((item) => {
            if (item?.code) {
                codeSet.add(String(item.code));
            }
        });
        const codes = Array.from(codeSet).sort((a, b) => a.localeCompare(b, 'uk'));

        const owedMap = Object.fromEntries((owed || []).map((item) => [String(item.code), Number(item.total || 0)]));
        const oweMap = Object.fromEntries((owe || []).map((item) => [String(item.code), Number(item.total || 0)]));

        if (!codes.length) {
            new Chart(el, {
                type: 'bar',
                data: {
                    labels: ['Немає даних'],
                    datasets: [{
                        label: 'Борги',
                        data: [0],
                        backgroundColor: '#e2e8f0',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.18)' } },
                    },
                },
            });
            return;
        }

        new Chart(el, {
            type: 'bar',
            data: {
                labels: codes,
                datasets: [
                    {
                        label: 'Вам винні',
                        data: codes.map((code) => owedMap[code] || 0),
                        backgroundColor: 'rgba(5, 150, 105, 0.85)',
                        borderRadius: 8,
                        maxBarThickness: 36,
                    },
                    {
                        label: 'Ви винні',
                        data: codes.map((code) => oweMap[code] || 0),
                        backgroundColor: 'rgba(220, 38, 38, 0.85)',
                        borderRadius: 8,
                        maxBarThickness: 36,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 10 },
                    },
                    tooltip: {
                        callbacks: {
                            label(ctx) {
                                return `${ctx.dataset.label}: ${money(ctx.parsed.y, ctx.label)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.18)' },
                        ticks: {
                            callback(value) {
                                return Number(value).toLocaleString('uk-UA');
                            },
                        },
                    },
                },
            },
        });
    };

    const circulations = payload.circulations || {};
    renderHorizontalBars('statsCirculationBalancesChart', circulations.chart, 'cash');
    renderCashShareChart('statsCirculationShareChart', circulations.chart);

    const debtors = payload.debtors;
    if (debtors) {
        renderHorizontalBars('statsDebtorBalancesChart', debtors.chart, 'sign');
        renderDebtStructureChart(
            'statsDebtorStructureChart',
            debtors.owedToYouByCurrency,
            debtors.youOweByCurrency,
        );
    }
})();
