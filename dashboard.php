<?php
/**
 * Dashboard
 */
require_once __DIR__ . '/includes/auth_check.php';

$page_title = t('dashboard');
$active_page = 'dashboard';
$module_js = 'dashboard';

// Get recent reservations
$stmt = $pdo->query("
    SELECT r.*, g.first_name, g.last_name, rm.room_number, rt.name as room_type_name
    FROM reservations r
    JOIN guests g ON r.guest_id = g.id
    JOIN rooms rm ON r.room_id = rm.id
    JOIN room_types rt ON rm.room_type_id = rt.id
    ORDER BY r.created_at DESC
    LIMIT 5
");
$recent_reservations = $stmt->fetchAll();

// Additional stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM guests");
$total_guests = (int)$stmt->fetch()['total'];
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM check_ins WHERE date(actual_check_in) = ?");
$stmt->execute([date('Y-m-d')]);
$today_checkins = (int)$stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms");
$total_rooms = (int)$stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
$available_rooms = (int)$stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied'");
$occupied_rooms = (int)$stmt->fetch()['total'];
$occupancy_rate = $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100, 1) : 0;
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE date(payment_date) = ?");
$stmt->execute([date('Y-m-d')]);
$today_revenue = (float)$stmt->fetch()['total'];

include __DIR__ . '/includes/header.php';
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <!-- Row 1 -->
    <div class="col-xl-4 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted"><?php echo t('total_rooms'); ?></h6>
                        <h3 id="statTotalRooms"><?php echo $total_rooms; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary"><i class="bi bi-door-open"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted"><?php echo t('available_rooms'); ?></h6>
                        <h3 id="statAvailableRooms"><?php echo $available_rooms; ?></h3>
                    </div>
                    <div class="stat-icon bg-success"><i class="bi bi-check-circle"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted"><?php echo t('occupancy_rate'); ?></h6>
                        <h3 id="statOccupancy"><?php echo $occupancy_rate; ?>%</h3>
                    </div>
                    <div class="stat-icon bg-info"><i class="bi bi-pie-chart"></i></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Row 2 -->
    <div class="col-xl-4 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted"><?php echo t('total_guests'); ?></h6>
                        <h3 id="statTotalGuests"><?php echo $total_guests; ?></h3>
                    </div>
                    <div class="stat-icon bg-purple"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted"><?php echo t('todays_checkins'); ?></h6>
                        <h3 id="statTodayCheckins"><?php echo $today_checkins; ?></h3>
                    </div>
                    <div class="stat-icon bg-teal"><i class="bi bi-box-arrow-in-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted"><?php echo t('todays_revenue'); ?></h6>
                        <h3 id="statRevenue"><?php echo formatCurrency($today_revenue); ?></h3>
                    </div>
                    <div class="stat-icon bg-warning"><i class="bi bi-currency-dollar"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions mb-4">
    <a href="<?php echo getBaseUrl(); ?>/modules/reservations/create.php" class="btn btn-primary me-2">
        <i class="bi bi-plus-circle"></i> <?php echo t('new_reservation'); ?>
    </a>
    <a href="<?php echo getBaseUrl(); ?>/modules/checkinout/checkin.php" class="btn btn-outline-primary">
        <i class="bi bi-box-arrow-in-right"></i> <?php echo t('quick_checkin'); ?>
    </a>
</div>

<!-- Charts Row 1: Weekly Check-ins + Monthly Revenue -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><?php echo t('weekly_checkins'); ?></div>
            <div class="card-body">
                <canvas id="weeklyChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><?php echo t('monthly_revenue'); ?></div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2: Room Type Distribution + Recent Reservations -->
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><?php echo t('room_type_distribution'); ?></div>
            <div class="card-body">
                <canvas id="roomTypeChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <!-- Recent Reservations -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><?php echo t('recent_reservations'); ?></span>
                <a href="<?php echo getBaseUrl(); ?>/modules/reservations/index.php" class="btn btn-sm btn-outline-primary">
                    <?php echo t('view'); ?> <?php echo t('all'); ?>
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?php echo t('reservation_code'); ?></th>
                                <th><?php echo t('guest_name'); ?></th>
                                <th><?php echo t('room_number'); ?></th>
                                <th><?php echo t('check_in_date'); ?></th>
                                <th><?php echo t('check_out_date'); ?></th>
                                <th><?php echo t('status'); ?></th>
                                <th><?php echo t('total_amount'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_reservations)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted"><?php echo t('no_data'); ?></td></tr>
                            <?php else: ?>
                            <?php foreach ($recent_reservations as $res): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($res['reservation_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($res['first_name'] . ' ' . $res['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($res['room_number']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($res['check_in_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($res['check_out_date'])); ?></td>
                                <td><span class="badge badge-status badge-<?php echo $res['status']; ?>"><?php echo t('status_' . $res['status']); ?></span></td>
                                <td><?php echo formatCurrency($res['total_amount']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
