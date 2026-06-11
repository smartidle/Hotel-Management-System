/**
 * Reports & Analytics - Chart Rendering
 */
document.addEventListener('DOMContentLoaded', async function () {
    await initReports();
});
document.addEventListener('ajaxPageLoaded', async function() {
    await initReports();
});

async function initReports() {
    const baseUrl = document.querySelector('meta[name="baseUrl"]')?.content || '';
    const apiBase = baseUrl ? baseUrl + '/modules/reports/api.php' : '/modules/reports/api.php';
    // Detect currency symbol from stat card text
    const statRevEl = document.getElementById('statRevenue');
    const currencySymbol = statRevEl ? statRevEl.textContent.replace(/[\d.,\s]/g, '').charAt(0) || '$' : '$';

    let currentPeriod = 'month';

    // ---- Period buttons ----
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary');
            document.querySelectorAll('.period-btn:not(.active)').forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-secondary');
            });
            currentPeriod = this.dataset.period;
            loadOverview();
        });
        // Style the initially active one
        if (btn.classList.contains('active')) {
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-primary');
        }
    });

    // ---- Fetch helper ----
    async function fetchApi(action, params = {}) {
        const url = new URL(apiBase, window.location.origin);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
        const resp = await fetch(url.toString());
        return resp.json();
    }

    // ---- Counter animation ----
    function animateValue(el, start, end, duration, suffix = '', prefix = '') {
        const range = end - start;
        const startTime = performance.now();
        function step(ts) {
            const progress = Math.min((ts - startTime) / duration, 1);
            const val = start + range * progress;
            if (Number.isInteger(end)) {
                el.textContent = prefix + Math.round(val).toLocaleString() + suffix;
            } else {
                el.textContent = prefix + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + suffix;
            }
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    // ---- Overview stats ----
    async function loadOverview() {
        try {
            const result = await fetchApi('overview', { period: currentPeriod });
            if (!result.success) return;
            const d = result.data;

            const revEl = document.getElementById('statRevenue');
            const occEl = document.getElementById('statOccupancy');
            const avgEl = document.getElementById('statAvgRate');
            const resEl = document.getElementById('statReservations');

            if (revEl) animateValue(revEl, 0, d.revenue, 800, '', currencySymbol);
            if (occEl) animateValue(occEl, 0, d.occupancy, 800, '%');
            if (avgEl) animateValue(avgEl, 0, d.avg_rate, 800, '', currencySymbol);
            if (resEl) animateValue(resEl, 0, d.total_reservations, 800);
        } catch (e) {
            console.error('Overview error:', e);
        }
    }

    // ---- Revenue Trend (Line) ----
    async function loadRevenueTrend() {
        try {
            const result = await fetchApi('revenue_trend');
            if (!result.success) return;
            const data = result.data;

            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;

            trackChart(new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.month),
                    datasets: [{
                        label: 'Revenue',
                        data: data.map(d => d.revenue),
                        borderColor: '#0f3460',
                        backgroundColor: 'rgba(15, 52, 96, 0.1)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#0f3460',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return currencySymbol + Number(context.parsed.y).toLocaleString('en-US', { minimumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return currencySymbol + value.toLocaleString();
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            }));
        } catch (e) {
            console.error('Revenue trend error:', e);
        }
    }

    // ---- Reservation Status (Pie) ----
    async function loadStatusChart() {
        try {
            const result = await fetchApi('reservation_status');
            if (!result.success) return;
            const data = result.data;

            const ctx = document.getElementById('statusChart');
            if (!ctx) return;

            const colorMap = {
                pending: '#f39c12',
                confirmed: '#3498db',
                checked_in: '#2ecc71',
                checked_out: '#95a5a6',
                cancelled: '#e74c3c',
                no_show: '#34495e'
            };
            const colors = data.map(d => colorMap[d.status] || '#999');

            trackChart(new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())),
                    datasets: [{
                        data: data.map(d => d.count),
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 12, usePointStyle: true, font: { size: 11 } }
                        }
                    }
                }
            }));
        } catch (e) {
            console.error('Status chart error:', e);
        }
    }

    // ---- Room Type Revenue (Bar) ----
    async function loadRoomRevenueChart() {
        try {
            const result = await fetchApi('room_type_revenue');
            if (!result.success) return;
            const data = result.data;

            const ctx = document.getElementById('roomRevenueChart');
            if (!ctx) return;

            const colors = ['#0f3460', '#1a5276', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6'];

            trackChart(new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.room_type),
                    datasets: [{
                        label: 'Revenue',
                        data: data.map(d => d.revenue),
                        backgroundColor: colors.slice(0, data.length),
                        borderWidth: 0,
                        borderRadius: 6,
                        barThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return currencySymbol + Number(context.parsed.y).toLocaleString('en-US', { minimumFractionDigits: 2 });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return currencySymbol + value.toLocaleString();
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            }));
        } catch (e) {
            console.error('Room revenue chart error:', e);
        }
    }

    // ---- Monthly Occupancy (Bar) ----
    async function loadOccupancyChart() {
        try {
            const result = await fetchApi('occupancy_trend');
            if (!result.success) return;
            const data = result.data;

            const ctx = document.getElementById('occupancyChart');
            if (!ctx) return;

            trackChart(new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.month),
                    datasets: [{
                        label: 'Occupancy %',
                        data: data.map(d => d.rate),
                        backgroundColor: 'rgba(46, 204, 113, 0.8)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                        barThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.parsed.y + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function (value) {
                                    return value + '%';
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            }));
        } catch (e) {
            console.error('Occupancy chart error:', e);
        }
    }

    // ---- Top Rooms Table ----
    async function loadTopRooms() {
        try {
            const result = await fetchApi('top_rooms');
            if (!result.success) return;
            const data = result.data;

            const tbody = document.getElementById('topRoomsBody');
            if (!tbody) return;

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No data available</td></tr>';
                return;
            }

            tbody.innerHTML = data.map((d, i) => `
                <tr>
                    <td><strong>#${i + 1}</strong></td>
                    <td><strong>${d.room_number}</strong></td>
                    <td>${d.room_type}</td>
                    <td>${d.total_reservations}</td>
                    <td><strong>${currencySymbol}${Number(d.total_revenue).toLocaleString('en-US', { minimumFractionDigits: 2 })}</strong></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: ${Math.min(d.occupancy_pct, 100)}%"></div>
                            </div>
                            <small>${d.occupancy_pct}%</small>
                        </div>
                    </td>
                </tr>
            `).join('');
        } catch (e) {
            console.error('Top rooms error:', e);
        }
    }

    // ---- Load everything ----
    await Promise.all([
        loadOverview(),
        loadRevenueTrend(),
        loadStatusChart(),
        loadRoomRevenueChart(),
        loadOccupancyChart(),
        loadTopRooms()
    ]);
}
