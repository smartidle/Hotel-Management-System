/**
 * Dashboard Charts
 */
document.addEventListener('DOMContentLoaded', async function() {
    await initDashboard();
});
document.addEventListener('ajaxPageLoaded', async function() {
    await initDashboard();
});

async function initDashboard() {
    try {
        const baseUrl = document.querySelector('meta[name="baseUrl"]')?.content || '';
        const apiUrl = baseUrl ? baseUrl + '/api/dashboard_stats.php' : '/api/dashboard_stats.php';
        const response = await fetch(apiUrl);
        const result = await response.json();

        if (!result.success) return;
        const data = result.data;

        // Update stat cards with animation
        const totalRooms = document.getElementById('statTotalRooms');
        const availRooms = document.getElementById('statAvailableRooms');
        const revenue = document.getElementById('statRevenue');
        const occupancy = document.getElementById('statOccupancy');
        const totalGuests = document.getElementById('statTotalGuests');
        const todayCheckins = document.getElementById('statTodayCheckins');

        if (totalRooms) animateCounter(totalRooms, data.total_rooms);
        if (availRooms) animateCounter(availRooms, data.available_rooms);
        if (revenue) revenue.textContent = '₱' + Number(data.today_revenue).toLocaleString('en-US', {minimumFractionDigits: 2});
        if (occupancy) {
            animateCounter(occupancy, data.occupancy_rate);
            setTimeout(() => { occupancy.textContent = data.occupancy_rate + '%'; }, 1100);
        }
        if (totalGuests) animateCounter(totalGuests, data.total_guests);
        if (todayCheckins) animateCounter(todayCheckins, data.today_checkins);

        // Weekly Check-ins Bar Chart
        const weeklyCtx = document.getElementById('weeklyChart');
        if (weeklyCtx && data.weekly_checkins) {
            trackChart(new Chart(weeklyCtx, {
                type: 'bar',
                data: {
                    labels: data.weekly_checkins.map(d => d.day),
                    datasets: [{
                        label: 'Check-ins',
                        data: data.weekly_checkins.map(d => d.count),
                        backgroundColor: 'rgba(15, 52, 96, 0.8)',
                        borderColor: 'rgba(15, 52, 96, 1)',
                        borderWidth: 1,
                        borderRadius: 6,
                        barThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { grid: { display: false } }
                    }
                }
            }));
        }

        // Monthly Revenue Line Chart
        const revenueCtx = document.getElementById('revenueChart');
        if (revenueCtx && data.monthly_revenue) {
            trackChart(new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: data.monthly_revenue.map(d => d.month),
                    datasets: [{
                        label: 'Revenue',
                        data: data.monthly_revenue.map(d => d.revenue),
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
                                label: function(context) {
                                    return '₱' + Number(context.parsed.y).toLocaleString('en-US', {minimumFractionDigits: 2});
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        },
                        x: { grid: { display: false } }
                    }
                }
            }));
        }

        // Room Type Doughnut Chart
        const roomTypeCtx = document.getElementById('roomTypeChart');
        if (roomTypeCtx && data.room_type_distribution) {
            const colors = ['#0f3460', '#1a5276', '#2ecc71', '#f39c12'];
            trackChart(new Chart(roomTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: data.room_type_distribution.map(d => d.name),
                    datasets: [{
                        data: data.room_type_distribution.map(d => d.count),
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true } }
                    }
                }
            }));
        }

    } catch (error) {
        console.error('Dashboard load error:', error);
    }
}
