<?php
require_once __DIR__ . '/../../includes/auth_check.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'overview':
            $period = $_GET['period'] ?? 'month';

            // Determine date range based on period
            $now = new DateTime();
            switch ($period) {
                case 'quarter':
                    $qMonth = (int)(($now->format('n') - 1) / 3) * 3 + 1;
                    $startDate = $now->format('Y') . '-' . str_pad($qMonth, 2, '0', STR_PAD_LEFT) . '-01';
                    $endDate = $now->format('Y-m-d');
                    break;
                case 'year':
                    $startDate = $now->format('Y') . '-01-01';
                    $endDate = $now->format('Y-m-d');
                    break;
                default: // month
                    $startDate = $now->format('Y-m-01');
                    $endDate = $now->format('Y-m-d');
                    break;
            }

            // Revenue: sum of paid bills in period
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(b.total_amount), 0) as revenue
                FROM bills b
                WHERE b.created_at >= ? AND b.created_at <= ? || ' 23:59:59'
            ");
            $stmt->execute([$startDate, $endDate]);
            $revenue = (float)$stmt->fetch()['revenue'];

            // Occupancy rate
            $totalRooms = $pdo->query("SELECT COUNT(*) as cnt FROM rooms")->fetch()['cnt'];
            $occupiedRooms = $pdo->query("SELECT COUNT(*) as cnt FROM rooms WHERE status = 'occupied'")->fetch()['cnt'];
            $occupancy = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

            // Average room rate
            $stmt = $pdo->prepare("
                SELECT COALESCE(AVG(r.total_amount * 1.0 / (julianday(r.check_out_date) - julianday(r.check_in_date))), 0) as avg_rate
                FROM reservations r
                WHERE r.status NOT IN ('cancelled', 'no_show')
                AND r.check_in_date >= ? AND r.check_in_date <= ?
            ");
            $stmt->execute([$startDate, $endDate]);
            $avgRate = round((float)$stmt->fetch()['avg_rate'], 2);

            // Total reservations
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as cnt
                FROM reservations
                WHERE created_at >= ? AND created_at <= ? || ' 23:59:59'
            ");
            $stmt->execute([$startDate, $endDate]);
            $totalRes = (int)$stmt->fetch()['cnt'];

            echo json_encode([
                'success' => true,
                'data' => [
                    'revenue' => $revenue,
                    'occupancy' => $occupancy,
                    'avg_rate' => $avgRate,
                    'total_reservations' => $totalRes
                ]
            ]);
            break;

        case 'revenue_trend':
            // Last 12 months revenue
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = new DateTime();
                $date->modify("-$i months");
                $m = $date->format('m');
                $y = $date->format('Y');
                $label = $date->format('M Y');

                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(total_amount), 0) as revenue
                    FROM bills
                    WHERE strftime('%m', created_at) = ? AND strftime('%Y', created_at) = ?
                ");
                $stmt->execute([$m, $y]);
                $rev = (float)$stmt->fetch()['revenue'];

                $data[] = ['month' => $label, 'revenue' => $rev];
            }
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'reservation_status':
            $stmt = $pdo->query("
                SELECT status, COUNT(*) as count
                FROM reservations
                GROUP BY status
                ORDER BY count DESC
            ");
            $data = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'room_type_revenue':
            $stmt = $pdo->query("
                SELECT rt.name as room_type,
                       COALESCE(SUM(r.total_amount), 0) as revenue,
                       COUNT(r.id) as reservation_count
                FROM room_types rt
                LEFT JOIN rooms rm ON rm.room_type_id = rt.id
                LEFT JOIN reservations r ON r.room_id = rm.id AND r.status NOT IN ('cancelled', 'no_show')
                GROUP BY rt.id
                ORDER BY revenue DESC
            ");
            $data = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'occupancy_trend':
            $data = [];
            $totalRooms = $pdo->query("SELECT COUNT(*) as cnt FROM rooms")->fetch()['cnt'];
            for ($i = 5; $i >= 0; $i--) {
                $date = new DateTime();
                $date->modify("-$i months");
                $m = $date->format('m');
                $y = $date->format('Y');
                $label = $date->format('M Y');

                // Count days with reservations in this month
                $daysInMonth = $date->format('t');
                $monthStart = $y . '-' . $m . '-01';
                $monthEnd = $y . '-' . $m . '-' . $daysInMonth;

                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT r.room_id) as occupied_rooms
                    FROM reservations r
                    WHERE r.status NOT IN ('cancelled', 'no_show')
                    AND r.check_in_date <= ? AND r.check_out_date >= ?
                ");
                $stmt->execute([$monthEnd, $monthStart]);
                $occupied = (int)$stmt->fetch()['occupied_rooms'];
                $rate = $totalRooms > 0 ? round(($occupied / $totalRooms) * 100, 1) : 0;

                $data[] = ['month' => $label, 'rate' => $rate];
            }
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'top_rooms':
            $stmt = $pdo->query("
                SELECT rm.room_number, rt.name as room_type,
                       COUNT(r.id) as total_reservations,
                       COALESCE(SUM(r.total_amount), 0) as total_revenue,
                       ROUND(AVG(julianday(r.check_out_date) - julianday(r.check_in_date)), 1) as avg_stay
                FROM rooms rm
                JOIN room_types rt ON rm.room_type_id = rt.id
                LEFT JOIN reservations r ON r.room_id = rm.id AND r.status NOT IN ('cancelled', 'no_show')
                GROUP BY rm.id
                ORDER BY total_revenue DESC
                LIMIT 10
            ");
            $data = $stmt->fetchAll();

            // Calculate occupancy percentage
            $totalRooms = $pdo->query("SELECT COUNT(*) as cnt FROM rooms")->fetch()['cnt'];
            foreach ($data as &$row) {
                $row['occupancy_pct'] = $totalRooms > 0
                    ? round(($row['total_reservations'] / max($row['total_reservations'], 1)) * 100 / 30 * $row['avg_stay'], 1)
                    : 0;
                $row['total_revenue'] = (float)$row['total_revenue'];
                $row['total_reservations'] = (int)$row['total_reservations'];
            }

            echo json_encode(['success' => true, 'data' => $data]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
