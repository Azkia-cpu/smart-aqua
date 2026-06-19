/**
 * SmartAqua Charts Module
 * Chart.js initialization and updates for water level and pH monitoring
 */
const SmartAquaCharts = (function () {
    let waterChart = null;
    let phChart = null;
    let refreshInterval = null;
    const REFRESH_MS = 30000;

    function getThemeColors() {
        const theme = document.documentElement.getAttribute('data-theme') || 'dark';
        return theme === 'dark'
            ? { grid: 'rgba(255,255,255,0.06)', text: '#a0aec0', tooltip: '#1b263b' }
            : { grid: 'rgba(0,0,0,0.06)', text: '#6c757d', tooltip: '#ffffff' };
    }

    function createGradient(ctx, color1, color2) {
        const g = ctx.createLinearGradient(0, 0, 0, 220);
        g.addColorStop(0, color1);
        g.addColorStop(1, color2);
        return g;
    }

    function initCharts() {
        const wCtx = document.getElementById('waterLevelChart');
        const pCtx = document.getElementById('phChart');
        if (!wCtx || !pCtx) return;

        const colors = getThemeColors();

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 600 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: colors.tooltip,
                    titleColor: colors.text === '#a0aec0' ? '#fff' : '#333',
                    bodyColor: colors.text === '#a0aec0' ? '#ddd' : '#555',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    cornerRadius: 8,
                    padding: 10,
                }
            },
            scales: {
                x: {
                    grid: { color: colors.grid, drawBorder: false },
                    ticks: { color: colors.text, maxTicksLimit: 8, font: { size: 11 } },
                },
                y: {
                    grid: { color: colors.grid, drawBorder: false },
                    ticks: { color: colors.text, font: { size: 11 } },
                }
            }
        };

        // Water Level Chart
        waterChart = new Chart(wCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Level Air (cm)',
                    data: [],
                    borderColor: '#00b4d8',
                    backgroundColor: createGradient(wCtx.getContext('2d'), 'rgba(0,180,216,0.25)', 'rgba(0,180,216,0.02)'),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#00b4d8',
                    borderWidth: 2,
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: {
                        ...commonOptions.scales.y,
                        min: 0,
                        max: 15,
                        ticks: {
                            ...commonOptions.scales.y.ticks,
                            callback: v => v + ' cm'
                        }
                    }
                }
            }
        });

        // pH Chart
        phChart = new Chart(pCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'pH Air',
                    data: [],
                    borderColor: '#06d6a0',
                    backgroundColor: createGradient(pCtx.getContext('2d'), 'rgba(6,214,160,0.25)', 'rgba(6,214,160,0.02)'),
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#06d6a0',
                    borderWidth: 2,
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: {
                        ...commonOptions.scales.y,
                        min: 4,
                        max: 10,
                    }
                }
            }
        });

        // Initial data fetch
        fetchChartData();
        refreshInterval = setInterval(fetchChartData, REFRESH_MS);
    }

    function fetchChartData() {
        const pond = window.SmartAqua?.currentPond;
        if (!pond) return;

        const url = window.SmartAqua.urls.chartData.replace(':pondCode', pond);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(res => {
                if (res.success) updateCharts(res.data);
            })
            .catch(err => console.warn('Chart fetch error:', err));
    }

    function updateCharts(data) {
        if (!data) return;

        const labels = (data.labels || []).map(ts => {
            const d = new Date(ts);
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        });

        if (waterChart) {
            waterChart.data.labels = labels;
            waterChart.data.datasets[0].data = data.water_levels || [];
            waterChart.update('none');
        }

        if (phChart) {
            phChart.data.labels = labels;
            phChart.data.datasets[0].data = data.ph_values || [];
            phChart.update('none');
        }
    }

    function updateTheme() {
        const colors = getThemeColors();
        [waterChart, phChart].forEach(chart => {
            if (!chart) return;
            chart.options.scales.x.grid.color = colors.grid;
            chart.options.scales.x.ticks.color = colors.text;
            chart.options.scales.y.grid.color = colors.grid;
            chart.options.scales.y.ticks.color = colors.text;
            chart.options.plugins.tooltip.backgroundColor = colors.tooltip;
            chart.update('none');
        });
    }

    document.addEventListener('DOMContentLoaded', initCharts);

    return { fetchChartData, updateCharts, updateTheme };
})();
