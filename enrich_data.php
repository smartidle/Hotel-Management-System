<?php
/**
 * Add enriched test data to make dashboard more impressive
 */
require_once __DIR__ . '/config/database.php';

$homeDir = getenv('USERPROFILE') ?: getenv('HOME') ?: 'C:\Users\Administrator';
$dbPath = $homeDir . '\hotel_demo_data\hotel_management.sqlite';
$pdo = new PDO("sqlite:" . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "<h2>Enriching test data...</h2><pre>";

$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

// ===================== 1. Add more check-ins this week =====================
echo "Adding more check-ins this week...\n";

// Get some available rooms to check in
$stmt = $pdo->query("SELECT id, room_number, room_type_id FROM rooms WHERE status = 'available' LIMIT 5");
$availRooms = $stmt->fetchAll();

// Get confirmed reservations that can be checked in
$stmt = $pdo->query("SELECT id, guest_id, room_id FROM reservations WHERE status = 'confirmed' LIMIT 5");
$confirmedRes = $stmt->fetchAll();

// Check in some confirmed reservations
foreach ($confirmedRes as $idx => $res) {
    $checkinDate = date('Y-m-d H:i:s', strtotime("-" . ($idx * 2) . " days"));
    $stmt = $pdo->prepare("INSERT INTO check_ins (reservation_id, room_id, guest_id, actual_check_in, status, created_at) VALUES (?, ?, ?, ?, 'active', ?)");
    $stmt->execute([$res['id'], $res['room_id'], $res['guest_id'], $checkinDate, $now]);
    
    // Update reservation status
    $pdo->prepare("UPDATE reservations SET status = 'checked_in' WHERE id = ?")->execute([$res['id']]);
    
    // Update room status
    $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$res['room_id']]);
    
    echo "  Checked in reservation #{$res['id']} on {$checkinDate}\n";
}

// ===================== 2. Add payments spread over 6 months =====================
echo "\nAdding payments spread over 6 months...\n";

$paymentCount = 0;
for ($m = 5; $m >= 0; $m--) {
    $monthDate = date('Y-m', strtotime("-$m months"));
    $daysInMonth = date('t', strtotime("-$m months"));
    
    // Add 3-6 payments per month
    $numPayments = rand(4, 8);
    for ($p = 0; $p < $numPayments; $p++) {
        $day = rand(1, $daysInMonth);
        $payDate = $monthDate . '-' . str_pad($day, 2, '0', STR_PAD_LEFT) . ' ' . str_pad(rand(8, 20), 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
        $amount = rand(800, 8000) + (rand(0, 99) / 100);
        
        // Pick a random bill
        $billId = rand(1, 14);
        $method = ['cash', 'credit_card', 'debit_card', 'gcash', 'bank_transfer'][rand(0, 4)];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (bill_id, amount, payment_method, payment_date, reference_number, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$billId, $amount, $method, $payDate, 'REF' . strtoupper(substr(md5($payDate . $p), 0, 8)), 'Payment for services', $now]);
            $paymentCount++;
        } catch (Exception $e) {
            // Skip if bill doesn't exist
        }
    }
}
echo "  Added {$paymentCount} historical payments.\n";

// ===================== 3. Add more reservations =====================
echo "\nAdding more diverse reservations...\n";

// Get all guest IDs
$guestIds = $pdo->query("SELECT id FROM guests")->fetchAll(PDO::FETCH_COLUMN);
$roomIds = $pdo->query("SELECT id FROM rooms")->fetchAll(PDO::FETCH_COLUMN);

$resCount = 0;
// Add some pending reservations for future dates
for ($i = 0; $i < 5; $i++) {
    $guestId = $guestIds[array_rand($guestIds)];
    $roomId = $roomIds[array_rand($roomIds)];
    $checkIn = date('Y-m-d', strtotime("+" . ($i + 3) . " days"));
    $checkOut = date('Y-m-d', strtotime("+" . ($i + 5) . " days"));
    $numGuests = rand(1, 4);
    $totalAmount = rand(2000, 15000) + (rand(0, 99) / 100);
    $resCode = 'RES-' . strtoupper(substr(md5($now . $i . rand()), 0, 8));
    
    $stmt = $pdo->prepare("INSERT INTO reservations (reservation_code, guest_id, room_id, check_in_date, check_out_date, num_guests, total_amount, status, special_requests, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)");
    $requests = ['Late check-in', 'Extra pillows', 'Airport pickup', 'Baby crib needed', 'High floor preferred'][$i % 5];
    $stmt->execute([$resCode, $guestId, $roomId, $checkIn, $checkOut, $numGuests, $totalAmount, $requests, $now, $now]);
    $resCount++;
}

// Add confirmed reservations
for ($i = 0; $i < 5; $i++) {
    $guestId = $guestIds[array_rand($guestIds)];
    $roomId = $roomIds[array_rand($roomIds)];
    $checkIn = date('Y-m-d', strtotime("+" . ($i + 1) . " days"));
    $checkOut = date('Y-m-d', strtotime("+" . ($i + 3) . " days"));
    $numGuests = rand(1, 3);
    $totalAmount = rand(3000, 12000) + (rand(0, 99) / 100);
    $resCode = 'RES-' . strtoupper(substr(md5($now . 'c' . $i . rand()), 0, 8));
    
    $stmt = $pdo->prepare("INSERT INTO reservations (reservation_code, guest_id, room_id, check_in_date, check_out_date, num_guests, total_amount, status, special_requests, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, ?)");
    $requests = ['Early check-in requested', 'Connecting rooms', 'Non-smoking room', 'Sea view', 'Ground floor'][$i % 5];
    $stmt->execute([$resCode, $guestId, $roomId, $checkIn, $checkOut, $numGuests, $totalAmount, $requests, $now, $now]);
    $resCount++;
}

echo "  Added {$resCount} new reservations.\n";

// ===================== 4. Add today's check-ins =====================
echo "\nAdding today's check-ins...\n";

$stmt = $pdo->query("SELECT id, guest_id, room_id FROM reservations WHERE status = 'confirmed' ORDER BY id DESC LIMIT 3");
$todayRes = $stmt->fetchAll();

foreach ($todayRes as $res) {
    $checkinTime = $today . ' ' . str_pad(rand(6, 14), 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00';
    $stmt = $pdo->prepare("INSERT INTO check_ins (reservation_id, room_id, guest_id, actual_check_in, status, created_at) VALUES (?, ?, ?, ?, 'active', ?)");
    $stmt->execute([$res['id'], $res['room_id'], $res['guest_id'], $checkinTime, $now]);
    
    $pdo->prepare("UPDATE reservations SET status = 'checked_in' WHERE id = ?")->execute([$res['id']]);
    $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE id = ?")->execute([$res['room_id']]);
    
    echo "  Today check-in: reservation #{$res['id']}\n";
}

// ===================== 5. Add today's payments =====================
echo "\nAdding today's payments...\n";

$todayPayments = 0;
for ($i = 0; $i < 5; $i++) {
    $billId = rand(1, 14);
    $amount = rand(500, 5000) + (rand(0, 99) / 100);
    $method = ['cash', 'credit_card', 'gcash', 'bank_transfer', 'debit_card'][rand(0, 4)];
    $payTime = $today . ' ' . str_pad(rand(7, 18), 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO payments (bill_id, amount, payment_method, payment_date, reference_number, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$billId, $amount, $method, $payTime, 'REF' . strtoupper(substr(md5($payTime . $i), 0, 8)), 'Walk-in payment', $now]);
        $todayPayments++;
    } catch (Exception $e) {}
}
echo "  Added {$todayPayments} today's payments.\n";

// ===================== 6. Add extra charges =====================
echo "\nAdding extra charges...\n";

$chargeTypes = [
    ['Room Service', 'Food and beverage delivery', 250, 1500],
    ['Laundry', 'Dry cleaning and laundry service', 100, 800],
    ['Mini Bar', 'Mini bar consumption', 150, 600],
    ['Spa', 'Spa and wellness treatment', 500, 3000],
    ['Airport Transfer', 'Airport pick-up/drop-off', 300, 800],
    ['Wi-Fi Upgrade', 'Premium internet access', 100, 300],
    ['Late Checkout', 'Extended checkout fee', 200, 500],
    ['Extra Bed', 'Additional bed setup', 350, 700],
    ['Pool Access', 'Private pool cabana rental', 400, 1200],
    ['Breakfast Buffet', 'International breakfast buffet', 250, 650],
];

$chargeCount = 0;
for ($i = 0; $i < 20; $i++) {
    $billId = rand(1, 14);
    $charge = $chargeTypes[array_rand($chargeTypes)];
    $amount = rand($charge[2], $charge[3]) + (rand(0, 99) / 100);
    $quantity = rand(1, 3);
    $chargeDate = date('Y-m-d', strtotime("-" . rand(0, 14) . " days"));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO extra_charges (bill_id, charge_type, description, amount, quantity, total, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$billId, $charge[0], $charge[1], $amount, $quantity, $amount * $quantity, $chargeDate . ' ' . str_pad(rand(8, 20), 2, '0', STR_PAD_LEFT) . ':00:00']);
        $chargeCount++;
    } catch (Exception $e) {}
}
echo "  Added {$chargeCount} extra charges.\n";

// ===================== 7. Add activity logs =====================
echo "\nAdding activity logs...\n";

$logActions = [
    ['reservation_created', 'New reservation created for room #%s'],
    ['checkin_completed', 'Guest checked into room #%s'],
    ['checkout_completed', 'Guest checked out of room #%s'],
    ['payment_received', 'Payment of ₱%s received'],
    ['room_cleaned', 'Room #%s cleaned and marked available'],
    ['guest_registered', 'New guest registered: %s'],
    ['bill_generated', 'Bill generated for room #%s'],
    ['room_maintenance', 'Room #%s scheduled for maintenance'],
    ['reservation_cancelled', 'Reservation cancelled for room #%s'],
    ['staff_login', 'Staff member logged in'],
];

$logCount = 0;
for ($i = 0; $i < 30; $i++) {
    $action = $logActions[array_rand($logActions)];
    $detail = sprintf($action[1], rand(101, 120));
    $staffId = rand(1, 3);
    $logDate = date('Y-m-d H:i:s', strtotime("-" . rand(0, 30) . " days " . rand(0, 23) . " hours"));
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$staffId, $action[0], $detail, $logDate]);
    $logCount++;
}
echo "  Added {$logCount} activity logs.\n";

// ===================== Summary =====================
echo "\n========================================\n";
echo "Data enrichment completed!\n";
echo "========================================\n\n";

$stmt = $pdo->query("SELECT COUNT(*) as c FROM guests"); echo "  Guests: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query("SELECT COUNT(*) as c FROM rooms"); echo "  Rooms: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query("SELECT COUNT(*) as c FROM reservations"); echo "  Reservations: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query("SELECT COUNT(*) as c FROM check_ins"); echo "  Check-ins: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query("SELECT COUNT(*) as c FROM bills"); echo "  Bills: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query("SELECT COUNT(*) as c FROM payments"); echo "  Payments: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query("SELECT COUNT(*) as c FROM extra_charges"); echo "  Extra Charges: " . $stmt->fetch()['c'] . "\n";
$stmt = $pdo->query("SELECT COUNT(*) as c FROM activity_logs"); echo "  Activity Logs: " . $stmt->fetch()['c'] . "\n";

$stmt = $pdo->query("SELECT SUM(amount) as total FROM payments");
$total = $stmt->fetch()['total'];
echo "\n  Total Revenue: ₱" . number_format($total, 2) . "\n";

echo "\nRefresh the dashboard to see richer data!\n";
echo "</pre>";
