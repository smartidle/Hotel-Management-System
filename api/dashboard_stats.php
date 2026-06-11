<?php
/**
 * Dashboard Statistics API (SQLite compatible)
 */
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

try {
    $stats = [];
    $today = date('Y-m-d');

    // Total rooms
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms");
    $stats['total_rooms'] = (int)$stmt->fetch()['total'];

    // Available rooms
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'available'");
    $stats['available_rooms'] = (int)$stmt->fetch()['total'];

    // Occupied rooms
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rooms WHERE status = 'occupied'");
    $stats['occupied_rooms'] = (int)$stmt->fetch()['total'];

    // Total reservations
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations");
    $stats['total_reservations'] = (int)$stmt->fetch()['total'];

    // Total guests
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM guests");
    $stats['total_guests'] = (int)$stmt->fetch()['total'];

    // Today's check-ins
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM check_ins WHERE date(actual_check_in) = ?");
    $stmt->execute([$today]);
    $stats['today_checkins'] = (int)$stmt->fetch()['total'];

    // Today's revenue
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE date(payment_date) = ?");
    $stmt->execute([$today]);
    $stats['today_revenue'] = (float)$stmt->fetch()['total'];

    // Occupancy rate
    $stats['occupancy_rate'] = $stats['total_rooms'] > 0
        ? round(($stats['occupied_rooms'] / $stats['total_rooms']) * 100, 1)
        : 0;

    // Weekly check-ins (last 7 days)
    $weekAgo = date('Y-m-d', strtotime('-6 days'));
    $stmt = $pdo->prepare("
        SELECT date(actual_check_in) as day, COUNT(*) as count
        FROM check_ins
        WHERE date(actual_check_in) >= ?
        GROUP BY date(actual_check_in)
        ORDER BY day ASC
    ");
    $stmt->execute([$weekAgo]);
    $weeklyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $weeklyMap = [];
    foreach ($weeklyData as $row) {
        $weeklyMap[$row['day']] = (int)$row['count'];
    }
    $weekly = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayName = date('D', strtotime("-$i days"));
        $weekly[] = [
            'day' => $dayName,
            'count' => isset($weeklyMap[$date]) ? $weeklyMap[$date] : 0
        ];
    }
    $stats['weekly_checkins'] = $weekly;

    // Room type distribution
    $stmt = $pdo->query("
        SELECT rt.name, COUNT(r.id) as count
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        GROUP BY rt.name
        ORDER BY rt.id
    ");
    $stats['room_type_distribution'] = $stmt->fetchAll();

    // Monthly revenue (last 6 months)
    $sixMonthsAgo = date('Y-m-d', strtotime('-5 months'));
    $monthNames = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec'];
    $stmt = $pdo->prepare("
        SELECT strftime('%m', payment_date) as month_num, SUM(amount) as revenue
        FROM payments
        WHERE date(payment_date) >= ?
        GROUP BY strftime('%Y-%m', payment_date)
        ORDER BY strftime('%Y-%m', payment_date) ASC
    ");
    $stmt->execute([$sixMonthsAgo]);
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($monthlyData as &$row) {
        $m = (int)$row['month_num'];
        $row['month'] = $monthNames[$m] ?? $row['month_num'];
        unset($row['month_num']);
        $row['revenue'] = (float)$row['revenue'];
    }
    unset($row);
    $stats['monthly_revenue'] = $monthlyData;

    echo json_encode(['success' => true, 'data' => $stats]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
