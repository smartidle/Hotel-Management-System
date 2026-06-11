<?php
/**
 * Add more test data to make the dashboard richer
 */
$dbPath = ($homeDir = getenv('USERPROFILE') ?: 'C:\Users\Administrator') . '\hotel_demo_data\hotel_management.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("PRAGMA foreign_keys = ON;");

    echo "<h2>Adding more test data...</h2><pre>";

    // ========== More Guests (20 more) ==========
    echo "Adding guests...\n";
    $newGuests = [
        ['Luis','Gonzalez','luis.g@email.com','+34-91-555-0001','Passport','ES11223344','Spanish','Calle Mayor 3','Barcelona','Spain','08002',0],
        ['Yuki','Yamamoto','yuki.y@email.com','+81-3-5555-0001','Passport','JP99887766','Japanese','5-1 Shibakoen','Tokyo','Japan','105-0011',1],
        ['Michael','Chen','michael.c@email.com','+86-21-5555-0001','National ID','CN55443322','Chinese','Nanjing Road 100','Shanghai','China','200000',0],
        ['Priya','Sharma','priya.s@email.com','+91-22-5555-0001','Passport','IN33221100','Indian','Brigade Road 20','Bangalore','India','560001',0],
        ['Ahmed','Hassan','ahmed.h@email.com','+20-2-5555-0001','Passport','EG77889900','Egyptian','Tahrir Square 1','Cairo','Egypt','11511',0],
        ['Olga','Petrova','olga.p@email.com','+7-495-555-0001','Passport','RU12345678','Russian','Tverskaya 10','Moscow','Russia','125009',1],
        ['Marco','Rossi','marco.r@email.com','+39-06-5555-0001','Passport','IT98765432','Italian','Via Roma 15','Rome','Italy','00100',0],
        ['Lisa','Anderson','lisa.a@email.com','+1-212-555-0001','Driver License','USDL44332211','American','5th Avenue 200','New York','United States','10010',0],
        ['Jun','Park','jun.p@email.com','+82-2-5555-0002','Passport','KR11223344','South Korean','Hongdae 30','Seoul','South Korea','04050',0],
        ['Fatima','Al-Rashid','fatima.ar@email.com','+971-4-555-0001','Passport','AE55667788','Emirati','Sheikh Zayed Road','Dubai','UAE','00000',1],
        ['Thomas','Muller','thomas.m@email.com','+49-89-5555-0001','National ID','DE99887766','German','Marienplatz 1','Munich','Germany','80331',0],
        ['Sophie','Dubois','sophie.d@email.com','+33-1-5555-0001','National ID','FR33445566','French','Champs Elysees 50','Paris','France','75008',0],
        ['Roberto','Silva','roberto.s@email.com','+55-11-5555-0001','Passport','BR77889911','Brazilian','Paulista Avenue 100','Sao Paulo','Brazil','01310-100',0],
        ['Anna','Johansson','anna.j@email.com','+46-8-5555-0001','Passport','SE55667744','Swedish','Kungsgatan 20','Stockholm','Sweden','111 43',0],
        ['Nguyen','Tran','nguyen.t@email.com','+84-28-5555-0001','Passport','VN99887766','Vietnamese','Nguyen Hue 50','Ho Chi Minh','Vietnam','700000',0],
        ['Maria','Fernandez','maria.f@email.com','+34-93-5555-0001','National ID','ES88776655','Spanish','La Rambla 30','Barcelona','Spain','08002',0],
        ['James','Taylor','james.t@email.com','+1-310-555-0001','Driver License','USDL77889900','American','Sunset Blvd 500','Los Angeles','United States','90028',0],
        ['Aisha','Khan','aisha.k@email.com','+92-21-5555-0001','Passport','PK11223344','Pakistani','Clifton Block 5','Karachi','Pakistan','75600',0],
        ['Dmitry','Sokolov','dmitry.s@email.com','+7-812-555-0001','Passport','RU55667788','Russian','Nevsky Prospect 25','St Petersburg','Russia','191186',0],
        ['Elena','Popescu','elena.p@email.com','+40-21-5555-0001','Passport','RO33445566','Romanian','Calea Victoriei 40','Bucharest','Romania','010063',0],
    ];
    $stmt = $pdo->prepare("INSERT INTO guests (first_name, last_name, email, phone, id_type, id_number, nationality, address, city, country, zip_code, vip_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($newGuests as $g) $stmt->execute($g);
    echo "  Added " . count($newGuests) . " guests.\n";

    // ========== More Reservations (30 more, spread across past 30 days and future) ==========
    echo "Adding reservations...\n";
    $roomIds = range(1, 20);
    $allGuestIds = [];
    for ($i = 1; $i <= 30; $i++) $allGuestIds[] = $i;

    $statuses = ['pending','confirmed','checked_in','checked_out','cancelled'];
    $resCount = 0;

    // Past reservations (completed)
    for ($i = 0; $i < 12; $i++) {
        $daysAgo = rand(2, 30);
        $nights = rand(1, 7);
        $checkIn = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $checkOut = date('Y-m-d', strtotime("-" . ($daysAgo - $nights) . " days"));
        $roomId = $roomIds[array_rand($roomIds)];
        $guestId = $allGuestIds[array_rand($allGuestIds)];
        $stmt = $pdo->prepare("SELECT rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
        $stmt->execute([$roomId]);
        $price = (float)$stmt->fetchColumn();
        $total = $nights * $price;

        $code = 'RES-' . str_pad(6 + $resCount, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO reservations (reservation_code, guest_id, room_id, check_in_date, check_out_date, num_guests, status, special_requests, total_amount, created_by) VALUES (?,?,?,?,?,?,?,null,?,?)")
            ->execute([$code, $guestId, $roomId, $checkIn, $checkOut, rand(1,3), 'checked_out', $total, rand(1,2)]);
        $resCount++;
    }

    // Active check-ins
    for ($i = 0; $i < 5; $i++) {
        $daysAgo = rand(1, 5);
        $nights = rand(3, 10);
        $checkIn = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $checkOut = date('Y-m-d', strtotime("+{$nights} days"));
        $roomId = $roomIds[array_rand($roomIds)];
        $guestId = $allGuestIds[array_rand($allGuestIds)];
        $stmt = $pdo->prepare("SELECT rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
        $stmt->execute([$roomId]);
        $price = (float)$stmt->fetchColumn();
        $total = $nights * $price;

        $code = 'RES-' . str_pad(6 + $resCount, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO reservations (reservation_code, guest_id, room_id, check_in_date, check_out_date, num_guests, status, special_requests, total_amount, created_by) VALUES (?,?,?,?,?,?,?,null,?,?)")
            ->execute([$code, $guestId, $roomId, $checkIn, $checkOut, rand(1,3), 'checked_in', $total, rand(1,2)]);

        // Also create check-in record
        $resId = $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO check_ins (reservation_id, room_id, guest_id, actual_check_in, actual_check_out, status, processed_by) VALUES (?,?,?,?,null,'active',?)")
            ->execute([$resId, $roomId, $guestId, date('Y-m-d H:i:s', strtotime("-{$daysAgo} days")), rand(1,2)]);
        $resCount++;
    }

    // Confirmed future
    for ($i = 0; $i < 6; $i++) {
        $daysAhead = rand(1, 14);
        $nights = rand(1, 5);
        $checkIn = date('Y-m-d', strtotime("+{$daysAhead} days"));
        $checkOut = date('Y-m-d', strtotime("+" . ($daysAhead + $nights) . " days"));
        $roomId = $roomIds[array_rand($roomIds)];
        $guestId = $allGuestIds[array_rand($allGuestIds)];
        $stmt = $pdo->prepare("SELECT rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
        $stmt->execute([$roomId]);
        $price = (float)$stmt->fetchColumn();
        $total = $nights * $price;

        $code = 'RES-' . str_pad(6 + $resCount, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO reservations (reservation_code, guest_id, room_id, check_in_date, check_out_date, num_guests, status, special_requests, total_amount, created_by) VALUES (?,?,?,?,?,?,?,null,?,?)")
            ->execute([$code, $guestId, $roomId, $checkIn, $checkOut, rand(1,3), 'confirmed', $total, rand(1,2)]);
        $resCount++;
    }

    // Pending future
    for ($i = 0; $i < 4; $i++) {
        $daysAhead = rand(3, 21);
        $nights = rand(1, 5);
        $checkIn = date('Y-m-d', strtotime("+{$daysAhead} days"));
        $checkOut = date('Y-m-d', strtotime("+" . ($daysAhead + $nights) . " days"));
        $roomId = $roomIds[array_rand($roomIds)];
        $guestId = $allGuestIds[array_rand($allGuestIds)];
        $stmt = $pdo->prepare("SELECT rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
        $stmt->execute([$roomId]);
        $price = (float)$stmt->fetchColumn();
        $total = $nights * $price;

        $code = 'RES-' . str_pad(6 + $resCount, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO reservations (reservation_code, guest_id, room_id, check_in_date, check_out_date, num_guests, status, special_requests, total_amount, created_by) VALUES (?,?,?,?,?,?,?,null,?,?)")
            ->execute([$code, $guestId, $roomId, $checkIn, $checkOut, rand(1,3), 'pending', $total, rand(1,2)]);
        $resCount++;
    }

    // Cancelled
    for ($i = 0; $i < 3; $i++) {
        $daysAgo = rand(5, 20);
        $nights = rand(1, 3);
        $checkIn = date('Y-m-d', strtotime("-{$daysAgo} days"));
        $checkOut = date('Y-m-d', strtotime("-" . ($daysAgo - $nights) . " days"));
        $roomId = $roomIds[array_rand($roomIds)];
        $guestId = $allGuestIds[array_rand($allGuestIds)];
        $stmt = $pdo->prepare("SELECT rt.base_price FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?");
        $stmt->execute([$roomId]);
        $price = (float)$stmt->fetchColumn();
        $total = $nights * $price;

        $code = 'RES-' . str_pad(6 + $resCount, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO reservations (reservation_code, guest_id, room_id, check_in_date, check_out_date, num_guests, status, special_requests, total_amount, created_by) VALUES (?,?,?,?,?,?,?,null,?,?)")
            ->execute([$code, $guestId, $roomId, $checkIn, $checkOut, rand(1,2), 'cancelled', $total, rand(1,2)]);
        $resCount++;
    }
    echo "  Added $resCount reservations.\n";

    // ========== More Bills & Payments for checked_out reservations ==========
    echo "Adding bills and payments...\n";
    $billCount = 0;
    $paymentCount = 0;

    // Get all checked_out reservations that don't have bills yet
    $stmt = $pdo->query("
        SELECT r.id, r.guest_id, r.total_amount, r.check_in_date, r.check_out_date, rt.base_price,
            julianday(r.check_out_date) - julianday(r.check_in_date) as nights
        FROM reservations r
        JOIN rooms rm ON r.room_id = rm.id
        JOIN room_types rt ON rm.room_type_id = rt.id
        WHERE r.status = 'checked_out'
    ");
    $checkedOut = $stmt->fetchAll();

    foreach ($checkedOut as $res) {
        $roomId = $pdo->prepare("SELECT room_id FROM reservations WHERE id = ?");
        $roomId->execute([$res['id']]);
        $rid = (int)$roomId->fetchColumn();

        $nights = max(1, (int)$res['nights']);
        $roomCharges = $nights * (float)$res['base_price'];
        $extra = rand(0, 150);
        $discount = rand(0, 1) ? rand(0, 50) : 0;
        $subtotal = $roomCharges + $extra - $discount;
        $tax = round($subtotal * 0.12, 2);
        $total = round($subtotal + $tax, 2);

        $billNum = 'BILL-' . str_pad(3 + $billCount, 5, '0', STR_PAD_LEFT);
        $status = ['paid','paid','paid','partial','unpaid'][rand(0,4)];

        $pdo->prepare("INSERT INTO bills (bill_number, reservation_id, guest_id, room_charges, extra_charges, tax_amount, discount, total_amount, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?)")
            ->execute([$billNum, $res['id'], $res['guest_id'], $roomCharges, $extra, $tax, $discount, $total, $status, rand(1,2)]);
        $billId = $pdo->lastInsertId();
        $billCount++;

        // Add extra charges
        if ($extra > 0) {
            $types = ['minibar','laundry','room_service','phone','other'];
            $descs = [
                'minibar' => ['Minibar - Beverages','Minibar - Snacks','Minibar - Water'],
                'laundry' => ['Laundry Service','Dry Cleaning','Express Laundry'],
                'room_service' => ['Room Service - Breakfast','Room Service - Dinner','Room Service - Lunch'],
                'phone' => ['International Call','Local Phone Charges'],
                'other' => ['Spa Treatment','Airport Transfer','Parking Fee','Extra Bed'],
            ];
            $numCharges = rand(1, 3);
            $remaining = $extra;
            for ($c = 0; $c < $numCharges && $remaining > 0; $c++) {
                $type = $types[array_rand($types)];
                $descList = $descs[$type];
                $desc = $descList[array_rand($descList)];
                $amt = ($c === $numCharges - 1) ? $remaining : round($remaining / ($numCharges - $c) * (rand(50,150)/100), 2);
                $amt = min($amt, $remaining);
                $remaining -= $amt;
                $pdo->prepare("INSERT INTO extra_charges (bill_id, description, amount, charge_type) VALUES (?,?,?,?)")
                    ->execute([$billId, $desc, $amt, $type]);
            }
        }

        // Add payments
        if ($status === 'paid') {
            $methods = ['cash','credit_card','debit_card','bank_transfer','online'];
            $pdo->prepare("INSERT INTO payments (bill_id, amount, payment_method, payment_date, reference_number, processed_by) VALUES (?,?,?,?,?,?)")
                ->execute([$billId, $total, $methods[array_rand($methods)], date('Y-m-d H:i:s', strtotime("-" . rand(0,5) . " days")), 'REF-' . str_pad($paymentCount, 5, '0', STR_PAD_LEFT), rand(1,2)]);
            $paymentCount++;
        } elseif ($status === 'partial') {
            $methods = ['cash','credit_card','online'];
            $partial = round($total * (rand(30,70)/100), 2);
            $pdo->prepare("INSERT INTO payments (bill_id, amount, payment_method, payment_date, reference_number, processed_by) VALUES (?,?,?,?,?,?)")
                ->execute([$billId, $partial, $methods[array_rand($methods)], date('Y-m-d H:i:s', strtotime("-" . rand(0,3) . " days")), 'REF-' . str_pad($paymentCount, 5, '0', STR_PAD_LEFT), rand(1,2)]);
            $paymentCount++;
        }
    }

    // Also add more payments spread over the past 30 days for revenue chart
    for ($i = 0; $i < 15; $i++) {
        $daysAgo = rand(0, 29);
        $amount = rand(50, 500);
        $methods = ['cash','credit_card','debit_card','bank_transfer','online'];

        // Get a random existing bill
        $billRow = $pdo->query("SELECT id FROM bills ORDER BY RANDOM() LIMIT 1")->fetch();
        if ($billRow) {
            $pdo->prepare("INSERT INTO payments (bill_id, amount, payment_method, payment_date, reference_number, processed_by) VALUES (?,?,?,?,?,?)")
                ->execute([$billRow['id'], $amount, $methods[array_rand($methods)], date('Y-m-d H:i:s', strtotime("-{$daysAgo} days")), 'EXTRA-' . str_pad($i, 4, '0', STR_PAD_LEFT), rand(1,2)]);
            $paymentCount++;
        }
    }

    echo "  Added $billCount bills and $paymentCount payments.\n";

    // ========== Activity Logs ==========
    echo "Adding activity logs...\n";
    $actions = ['login','create','update','delete','checkin','checkout','payment'];
    $modules = ['auth','rooms','reservations','guests','checkinout','billing','staff'];
    for ($i = 0; $i < 30; $i++) {
        $daysAgo = rand(0, 14);
        $pdo->prepare("INSERT INTO activity_logs (user_id, action, module, description, ip_address, created_at) VALUES (?,?,?,?,?,?)")
            ->execute([rand(1,3), $actions[array_rand($actions)], $modules[array_rand($modules)], 'Sample log entry', '127.0.0.1', date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"))]);
    }
    echo "  Added 30 activity logs.\n";

    // ========== Summary ==========
    echo "\n========================================\n";
    echo "Data enrichment completed!\n";
    echo "========================================\n\n";

    $counts = [
        'Guests' => $pdo->query("SELECT COUNT(*) FROM guests")->fetchColumn(),
        'Rooms' => $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn(),
        'Reservations' => $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
        'Check-ins' => $pdo->query("SELECT COUNT(*) FROM check_ins")->fetchColumn(),
        'Bills' => $pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn(),
        'Payments' => $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn(),
        'Extra Charges' => $pdo->query("SELECT COUNT(*) FROM extra_charges")->fetchColumn(),
        'Activity Logs' => $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn(),
    ];

    foreach ($counts as $name => $count) {
        echo "  $name: $count\n";
    }

    echo "\nTotal Revenue: " . $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn() . "\n";
    echo "\nRefresh the dashboard to see richer data!\n";
    echo "</pre>";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
