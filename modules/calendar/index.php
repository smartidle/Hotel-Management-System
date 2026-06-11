<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = 'Calendar';
$active_page = 'calendar';

// Get current month/year from query params
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Clamp month to valid range
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$baseUrl = getBaseUrl();

// Query reservations for this month
$stmt = $pdo->prepare("
    SELECT r.*, g.first_name, g.last_name, rm.room_number
    FROM reservations r
    LEFT JOIN guests g ON r.guest_id = g.id
    LEFT JOIN rooms rm ON r.room_id = rm.id
    WHERE (strftime('%m', r.check_in_date) = ? AND strftime('%Y', r.check_in_date) = ?)
       OR (strftime('%m', r.check_out_date) = ? AND strftime('%Y', r.check_out_date) = ?)
       OR (r.check_in_date <= ? AND r.check_out_date >= ?)
    ORDER BY r.check_in_date
");
$monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
$monthStart = $year . '-' . $monthStr . '-01';
$monthEnd = $year . '-' . $monthStr . '-' . date('t', strtotime($monthStart));
$stmt->execute([$monthStr, $year, $monthStr, $year, $monthStart, $monthEnd]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build date-indexed arrays for check-ins and check-outs
$checkins = [];
$checkouts = [];
$allResByDate = [];
foreach ($reservations as $r) {
    $ciDate = substr($r['check_in_date'], 0, 10);
    $coDate = substr($r['check_out_date'], 0, 10);
    $ciDay = date('j', strtotime($ciDate));
    $coDay = date('j', strtotime($coDate));
    $ciMonth = date('n', strtotime($ciDate));
    $coMonth = date('n', strtotime($coDate));
    $ciYear = date('Y', strtotime($ciDate));
    $coYear = date('Y', strtotime($coDate));

    if ($ciMonth == $month && $ciYear == $year) {
        if (!isset($checkins[$ciDay])) $checkins[$ciDay] = [];
        $checkins[$ciDay][] = $r;
    }
    if ($coMonth == $month && $coYear == $year) {
        if (!isset($checkouts[$coDay])) $checkouts[$coDay] = [];
        $checkouts[$coDay][] = $r;
    }

    // Build allResByDate for every day this reservation spans in the month
    $start = max(strtotime($monthStart), strtotime($ciDate));
    $end = min(strtotime($monthEnd), strtotime($coDate));
    for ($d = $start; $d <= $end; $d += 86400) {
        $dayNum = date('j', $d);
        if (!isset($allResByDate[$dayNum])) $allResByDate[$dayNum] = [];
        $allResByDate[$dayNum][] = $r;
    }
}

// Calendar calculations
$daysInMonth = date('t', strtotime("$year-$monthStr-01"));
$firstDayOfWeek = date('N', strtotime("$year-$monthStr-01")); // 1=Mon, 7=Sun
$today = date('j');
$currentMonth = date('n');
$currentYear = date('Y');

// Stats
$totalReservations = count($reservations);
$totalCheckins = array_sum(array_map('count', $checkins));
$totalCheckouts = array_sum(array_map('count', $checkouts));

// Month name for display
$monthNames = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
$monthName = $monthNames[$month];

// Prev/Next month
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

include __DIR__ . '/../../includes/header.php';
?>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #dee2e6;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}
.calendar-header-cell {
    background: #f8f9fa;
    padding: 10px;
    text-align: center;
    font-weight: 600;
    font-size: 0.85rem;
    color: #555;
}
.calendar-day {
    background: #fff;
    min-height: 90px;
    padding: 8px;
    position: relative;
    transition: background 0.2s;
}
.calendar-day:hover {
    background: #f0f7ff;
}
.calendar-day.today {
    background: #e8f4fd;
}
.calendar-day.today .day-num {
    background: var(--primary);
    color: #fff;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.calendar-day.empty {
    background: #f8f9fa;
}
.calendar-day .day-num {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 4px;
}
.calendar-indicator {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 0.72rem;
    margin-top: 2px;
    padding: 1px 5px;
    border-radius: 10px;
}
.calendar-indicator.checkin {
    background: #d4edda;
    color: #155724;
}
.calendar-indicator.checkout {
    background: #f8d7da;
    color: #721c24;
}
.calendar-indicator .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    display: inline-block;
}
.calendar-indicator.checkin .dot { background: #2ecc71; }
.calendar-indicator.checkout .dot { background: #e74c3c; }

@media (max-width: 767.98px) {
    .calendar-day { min-height: 60px; padding: 4px; }
    .calendar-day .day-num { font-size: 0.8rem; }
    .calendar-indicator { font-size: 0.65rem; }
}
</style>

<!-- Page Header -->
<div class="page-header">
    <h4><i class="bi bi-calendar3"></i> <?php echo t('nav_calendar') ?: 'Calendar'; ?></h4>
    <div class="d-flex align-items-center gap-2">
        <a href="<?php echo $baseUrl; ?>/modules/calendar/index.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-chevron-left"></i>
        </a>
        <span class="fw-bold fs-5" style="min-width: 160px; text-align: center;"><?php echo $monthName . ' ' . $year; ?></span>
        <a href="<?php echo $baseUrl; ?>/modules/calendar/index.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-chevron-right"></i>
        </a>
        <?php if ($month != $currentMonth || $year != $currentYear): ?>
        <a href="<?php echo $baseUrl; ?>/modules/calendar/index.php" class="btn btn-sm btn-primary ms-2">
            <i class="bi bi-calendar-event"></i> Today
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">This Month Reservations</h6>
                    <h3 class="mb-0"><?php echo $totalReservations; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success">
                    <i class="bi bi-box-arrow-in-right"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Expected Check-ins</h6>
                    <h3 class="mb-0"><?php echo $totalCheckins; ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Expected Check-outs</h6>
                    <h3 class="mb-0"><?php echo $totalCheckouts; ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Grid -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="calendar-grid">
            <!-- Header Row -->
            <div class="calendar-header-cell">Mon</div>
            <div class="calendar-header-cell">Tue</div>
            <div class="calendar-header-cell">Wed</div>
            <div class="calendar-header-cell">Thu</div>
            <div class="calendar-header-cell">Fri</div>
            <div class="calendar-header-cell">Sat</div>
            <div class="calendar-header-cell">Sun</div>

            <?php
            // Empty cells before first day
            for ($i = 1; $i < $firstDayOfWeek; $i++) {
                echo '<div class="calendar-day empty"></div>';
            }

            // Day cells
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $isToday = ($day == $today && $month == $currentMonth && $year == $currentYear);
                $hasReservations = isset($allResByDate[$day]);
                $classes = 'calendar-day';
                if ($isToday) $classes .= ' today';
                if ($hasReservations) $classes .= ' has-reservations';

                echo '<div class="' . $classes . '">';
                echo '<div class="day-num">' . $day . '</div>';

                // Check-in indicator
                if (isset($checkins[$day])) {
                    $count = count($checkins[$day]);
                    echo '<div class="calendar-indicator checkin"><span class="dot"></span>' . $count . ' in</div>';
                }

                // Check-out indicator
                if (isset($checkouts[$day])) {
                    $count = count($checkouts[$day]);
                    echo '<div class="calendar-indicator checkout"><span class="dot"></span>' . $count . ' out</div>';
                }

                echo '</div>';
            }

            // Fill remaining cells to complete last row
            $totalCells = $firstDayOfWeek - 1 + $daysInMonth;
            $remaining = $totalCells % 7;
            if ($remaining > 0) {
                for ($i = $remaining; $i < 7; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- Monthly Reservation List -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-list-ul"></i> <?php echo $monthName . ' ' . $year; ?> Reservations</h6>
        <span class="badge bg-primary"><?php echo $totalReservations; ?> total</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No reservations this month</td></tr>
                    <?php else: ?>
                    <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r['reservation_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['room_number']); ?></td>
                        <td><?php echo date('M j', strtotime($r['check_in_date'])); ?></td>
                        <td><?php echo date('M j', strtotime($r['check_out_date'])); ?></td>
                        <td><span class="badge badge-status badge-<?php echo $r['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $r['status'])); ?></span></td>
                        <td><?php echo formatCurrency($r['total_amount']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
