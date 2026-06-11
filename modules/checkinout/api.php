<?php
/**
 * Check-in/Check-out API
 * Handles AJAX requests for check-in and check-out operations
 */
require_once __DIR__ . '/../../includes/auth_check.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'search_reservations':
        searchReservations();
        break;

    case 'search_checkins':
        searchCheckins();
        break;

    case 'checkin':
        processCheckin();
        break;

    case 'checkout':
        processCheckout();
        break;

    case 'calculate_charges':
        calculateCharges();
        break;

    default:
        echo json_encode(['success' => false, 'message' => t('operation_failed')]);
        break;
}

/**
 * Search confirmed reservations for check-in
 * GET: action=search_reservations&search=keyword
 */
function searchReservations() {
    global $pdo;
    $search = trim($_GET['search'] ?? '');
    if ($search === '') {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }

    $like = '%' . $search . '%';
    $stmt = $pdo->prepare("
        SELECT r.id, r.reservation_code, r.check_in_date, r.check_out_date, r.total_amount,
            r.num_guests, r.special_requests,
            g.first_name AS guest_first, g.last_name AS guest_last,
            g.phone AS guest_phone, g.email AS guest_email,
            g.id_type, g.id_number, g.nationality,
            rm.room_number, rt.name AS room_type_name, rt.base_price
        FROM reservations r
        LEFT JOIN guests g ON r.guest_id = g.id
        LEFT JOIN rooms rm ON r.room_id = rm.id
        LEFT JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE r.status = ?
            AND (r.reservation_code LIKE ? OR g.first_name LIKE ? OR g.last_name LIKE ?)
        ORDER BY r.check_in_date ASC
        LIMIT 20
    ");
    $stmt->execute([RES_CONFIRMED, $like, $like, $like]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $results]);
}

/**
 * Search active check-ins for check-out
 * GET: action=search_checkins&search=keyword
 */
function searchCheckins() {
    global $pdo;
    $search = trim($_GET['search'] ?? '');
    if ($search === '') {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }

    $like = '%' . $search . '%';
    $stmt = $pdo->prepare("
        SELECT ci.id AS checkin_id, ci.actual_check_in, ci.notes AS checkin_notes,
            r.reservation_code, r.check_in_date, r.check_out_date, r.total_amount AS reservation_total,
            g.first_name AS guest_first, g.last_name AS guest_last,
            rm.room_number, rt.name AS room_type_name, rt.base_price
        FROM check_ins ci
        LEFT JOIN reservations r ON ci.reservation_id = r.id
        LEFT JOIN guests g ON ci.guest_id = g.id
        LEFT JOIN rooms rm ON ci.room_id = rm.id
        LEFT JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE ci.status = ?
            AND (rm.room_number LIKE ? OR g.first_name LIKE ? OR g.last_name LIKE ?)
        ORDER BY ci.actual_check_in ASC
        LIMIT 20
    ");
    $stmt->execute([CHECKIN_ACTIVE, $like, $like, $like]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $results]);
}

/**
 * Process check-in
 * POST: action=checkin, reservation_id, checkin_time, notes, id_verified
 */
function processCheckin() {
    global $pdo;

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => t('session_expired')]);
        return;
    }

    $reservationId = intval($_POST['reservation_id'] ?? 0);
    $checkinTime = $_POST['checkin_time'] ?? date('Y-m-d H:i:s');
    $notes = sanitize($_POST['notes'] ?? '');
    $idVerified = intval($_POST['id_verified'] ?? 0);

    // Verify reservation exists and is confirmed
    $stmt = $pdo->prepare("SELECT r.*, rm.id AS room_id FROM reservations r
        LEFT JOIN rooms rm ON r.room_id = rm.id
        WHERE r.id = ? AND r.status = ?");
    $stmt->execute([$reservationId, RES_CONFIRMED]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reservation) {
        echo json_encode(['success' => false, 'message' => t('operation_failed')]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // INSERT check_ins
        $stmt = $pdo->prepare("INSERT INTO check_ins (reservation_id, room_id, guest_id, actual_check_in, status, processed_by, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $reservation['id'],
            $reservation['room_id'],
            $reservation['guest_id'],
            $checkinTime,
            CHECKIN_ACTIVE,
            $_SESSION['user_id'],
            $notes
        ]);

        // UPDATE reservations.status = checked_in
        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([RES_CHECKED_IN, $reservationId]);

        // UPDATE rooms.status = occupied
        $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        $stmt->execute([ROOM_OCCUPIED, $reservation['room_id']]);

        logActivity($pdo, $_SESSION['user_id'], 'checkin', 'checkinout',
            'Checked in reservation ID: ' . $reservationId);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => t('checkin_success')]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => t('operation_failed')]);
    }
}

/**
 * Process check-out
 * POST: action=checkout, checkin_id, checkout_time, room_charges, extra_charges_total, tax_amount, total_amount, notes, charge arrays
 */
function processCheckout() {
    global $pdo;

    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => t('session_expired')]);
        return;
    }

    $checkinId = intval($_POST['checkin_id'] ?? 0);
    $checkoutTime = $_POST['checkout_time'] ?? date('Y-m-d H:i:s');
    $notes = sanitize($_POST['notes'] ?? '');
    $roomCharges = floatval($_POST['room_charges'] ?? 0);
    $extraChargesTotal = floatval($_POST['extra_charges_total'] ?? 0);
    $taxAmount = floatval($_POST['tax_amount'] ?? 0);
    $totalAmount = floatval($_POST['total_amount'] ?? 0);

    $chargeDescriptions = $_POST['charge_description'] ?? [];
    $chargeAmounts = $_POST['charge_amount'] ?? [];
    $chargeTypes = $_POST['charge_type'] ?? [];

    // Verify check-in exists and is active
    $stmt = $pdo->prepare("
        SELECT ci.*, r.id AS reservation_id, r.guest_id, rm.id AS room_id
        FROM check_ins ci
        LEFT JOIN reservations r ON ci.reservation_id = r.id
        LEFT JOIN rooms rm ON ci.room_id = rm.id
        WHERE ci.id = ? AND ci.status = ?
    ");
    $stmt->execute([$checkinId, CHECKIN_ACTIVE]);
    $checkin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$checkin) {
        echo json_encode(['success' => false, 'message' => t('operation_failed')]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // UPDATE check_ins
        $stmt = $pdo->prepare("UPDATE check_ins SET actual_check_out = ?, status = ?, notes = ? WHERE id = ?");
        $stmt->execute([$checkoutTime, CHECKIN_COMPLETED, $notes, $checkinId]);

        // UPDATE reservations.status = checked_out
        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([RES_CHECKED_OUT, $checkin['reservation_id']]);

        // UPDATE rooms.status = available
        $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        $stmt->execute([ROOM_AVAILABLE, $checkin['room_id']]);

        // Generate bill number
        $billNumber = generateCode('BILL', $pdo, 'bills', 'bill_number');

        // INSERT bills
        $stmt = $pdo->prepare("INSERT INTO bills (bill_number, reservation_id, guest_id, room_charges, extra_charges, tax_amount, total_amount, status, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $billNumber,
            $checkin['reservation_id'],
            $checkin['guest_id'],
            $roomCharges,
            $extraChargesTotal,
            $taxAmount,
            $totalAmount,
            BILL_UNPAID,
            $notes,
            $_SESSION['user_id']
        ]);
        $billId = $pdo->lastInsertId();

        // INSERT extra_charges
        for ($i = 0; $i < count($chargeDescriptions); $i++) {
            if (!empty($chargeDescriptions[$i]) && floatval($chargeAmounts[$i]) > 0) {
                $stmt = $pdo->prepare("INSERT INTO extra_charges (bill_id, description, amount, charge_type) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $billId,
                    sanitize($chargeDescriptions[$i]),
                    floatval($chargeAmounts[$i]),
                    $chargeTypes[$i] ?? CHARGE_OTHER
                ]);
            }
        }

        logActivity($pdo, $_SESSION['user_id'], 'checkout', 'checkinout',
            'Checked out check-in ID: ' . $checkinId . ', Bill: ' . $billNumber);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => t('checkout_success'), 'bill_id' => $billId]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => t('operation_failed')]);
    }
}

/**
 * Calculate room charges based on check-in time and room price
 * GET: action=calculate_charges&checkin_id=ID
 */
function calculateCharges() {
    global $pdo;

    $checkinId = intval($_GET['checkin_id'] ?? 0);
    if ($checkinId <= 0) {
        echo json_encode(['success' => false, 'message' => t('operation_failed')]);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT ci.actual_check_in, rt.base_price
        FROM check_ins ci
        LEFT JOIN rooms rm ON ci.room_id = rm.id
        LEFT JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE ci.id = ? AND ci.status = ?
    ");
    $stmt->execute([$checkinId, CHECKIN_ACTIVE]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => t('operation_failed')]);
        return;
    }

    $checkinTime = new DateTime($data['actual_check_in']);
    $now = new DateTime();
    $nights = max(1, $checkinTime->diff($now)->days);
    $roomCharges = $nights * (float)$data['base_price'];
    $tax = $roomCharges * TAX_RATE;
    $total = $roomCharges + $tax;

    echo json_encode([
        'success' => true,
        'data' => [
            'nights' => $nights,
            'price_per_night' => (float)$data['base_price'],
            'room_charges' => round($roomCharges, 2),
            'tax_rate' => TAX_RATE,
            'tax_amount' => round($tax, 2),
            'total' => round($total, 2)
        ]
    ]);
}
