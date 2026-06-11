<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = 'Reports';
$active_page = 'reports';
$module_js = 'reports';
$baseUrl = getBaseUrl();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h4><i class="bi bi-bar-chart-line"></i> Reports & Analytics</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary period-btn active" data-period="month">This Month</button>
        <button class="btn btn-sm btn-outline-secondary period-btn" data-period="quarter">This Quarter</button>
        <button class="btn btn-sm btn-outline-secondary period-btn" data-period="year">This Year</button>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Monthly Revenue</h6>
                    <h3 class="mb-0" id="statRevenue"><?php echo CURRENCY_SYMBOL; ?>0.00</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success">
                    <i class="bi bi-pie-chart"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Occupancy Rate</h6>
                    <h3 class="mb-0" id="statOccupancy">0%</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-purple">
                    <i class="bi bi-tag"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Average Room Rate</h6>
                    <h3 class="mb-0" id="statAvgRate"><?php echo CURRENCY_SYMBOL; ?>0.00</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: #e67e22;">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Total Reservations</h6>
                    <h3 class="mb-0" id="statReservations">0</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Revenue Trend (12 Months)</h6>
            </div>
            <div class="card-body">
                <div style="height: 320px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-pie-chart-fill"></i> Reservation Status</h6>
            </div>
            <div class="card-body d-flex justify-content-center">
                <div style="height: 320px; width: 100%;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-building"></i> Room Type Revenue</h6>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="roomRevenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-bar-chart"></i> Monthly Occupancy</h6>
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="occupancyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Rooms Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-trophy"></i> Top 10 Rooms by Revenue</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Room</th>
                        <th>Room Type</th>
                        <th>Total Reservations</th>
                        <th>Total Revenue</th>
                        <th>Occupancy</th>
                    </tr>
                </thead>
                <tbody id="topRoomsBody">
                    <tr><td colspan="6" class="text-center py-4 text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
