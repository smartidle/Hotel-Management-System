<?php
/**
 * Hotel Management System Demo - Auto Setup Script
 * Run this file once to create the SQLite database with schema and seed data.
 * Usage: php setup.php  OR  open http://localhost:8000/setup.php
 */

echo "<h1>Hotel Management System Demo - Setup</h1><pre>";

// Use user home directory for writable location
$homeDir = getenv('USERPROFILE') ?: getenv('HOME') ?: 'C:\Users\Administrator';
$dbDir = $homeDir . '\hotel_demo_data';
if (!is_dir($dbDir)) @mkdir($dbDir, 0777, true);
$dbPath = $dbDir . '\hotel_management.sqlite';
echo "Database location: $dbPath\n";

// Remove old database if exists
if (file_exists($dbPath)) {
    unlink($dbPath);
    echo "Removed old database.\n";
}

try {
    $pdo = new PDO("sqlite:" . $dbPath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("PRAGMA foreign_keys = ON;");
    echo "SQLite database created: $dbPath\n\n";

    // ========== CREATE TABLES ==========
    echo "Creating tables...\n";

    $pdo->exec("CREATE TABLE roles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        role_name TEXT NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE staff (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        phone TEXT,
        role_id INTEGER NOT NULL,
        status TEXT DEFAULT 'active' CHECK(status IN ('active','inactive')),
        avatar TEXT,
        last_login TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES roles(id)
    )");

    $pdo->exec("CREATE TABLE room_types (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        base_price REAL NOT NULL,
        max_occupancy INTEGER DEFAULT 2,
        amenities TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE rooms (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        room_number TEXT NOT NULL UNIQUE,
        room_type_id INTEGER NOT NULL,
        floor INTEGER DEFAULT 1,
        status TEXT DEFAULT 'available' CHECK(status IN ('available','occupied','maintenance','reserved')),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_type_id) REFERENCES room_types(id)
    )");

    $pdo->exec("CREATE TABLE guests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        email TEXT,
        phone TEXT,
        id_type TEXT,
        id_number TEXT,
        nationality TEXT,
        address TEXT,
        city TEXT,
        country TEXT,
        zip_code TEXT,
        notes TEXT,
        vip_status INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE reservations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        reservation_code TEXT NOT NULL UNIQUE,
        guest_id INTEGER NOT NULL,
        room_id INTEGER NOT NULL,
        check_in_date DATE NOT NULL,
        check_out_date DATE NOT NULL,
        num_guests INTEGER DEFAULT 1,
        status TEXT DEFAULT 'pending' CHECK(status IN ('pending','confirmed','checked_in','checked_out','cancelled','no_show')),
        special_requests TEXT,
        total_amount REAL DEFAULT 0.00,
        created_by INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (guest_id) REFERENCES guests(id),
        FOREIGN KEY (room_id) REFERENCES rooms(id),
        FOREIGN KEY (created_by) REFERENCES staff(id)
    )");

    $pdo->exec("CREATE TABLE check_ins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        reservation_id INTEGER NOT NULL,
        room_id INTEGER NOT NULL,
        guest_id INTEGER NOT NULL,
        actual_check_in DATETIME NOT NULL,
        actual_check_out DATETIME,
        status TEXT DEFAULT 'active' CHECK(status IN ('active','completed')),
        processed_by INTEGER,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reservation_id) REFERENCES reservations(id),
        FOREIGN KEY (room_id) REFERENCES rooms(id),
        FOREIGN KEY (guest_id) REFERENCES guests(id),
        FOREIGN KEY (processed_by) REFERENCES staff(id)
    )");

    $pdo->exec("CREATE TABLE bills (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bill_number TEXT NOT NULL UNIQUE,
        reservation_id INTEGER NOT NULL,
        guest_id INTEGER NOT NULL,
        room_charges REAL DEFAULT 0.00,
        extra_charges REAL DEFAULT 0.00,
        tax_amount REAL DEFAULT 0.00,
        discount REAL DEFAULT 0.00,
        total_amount REAL NOT NULL,
        status TEXT DEFAULT 'unpaid' CHECK(status IN ('unpaid','partial','paid')),
        notes TEXT,
        created_by INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reservation_id) REFERENCES reservations(id),
        FOREIGN KEY (guest_id) REFERENCES guests(id),
        FOREIGN KEY (created_by) REFERENCES staff(id)
    )");

    $pdo->exec("CREATE TABLE payments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bill_id INTEGER NOT NULL,
        amount REAL NOT NULL,
        payment_method TEXT NOT NULL CHECK(payment_method IN ('cash','credit_card','debit_card','bank_transfer','online')),
        payment_date DATETIME NOT NULL,
        reference_number TEXT,
        notes TEXT,
        processed_by INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bill_id) REFERENCES bills(id),
        FOREIGN KEY (processed_by) REFERENCES staff(id)
    )");

    $pdo->exec("CREATE TABLE extra_charges (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bill_id INTEGER NOT NULL,
        description TEXT NOT NULL,
        amount REAL NOT NULL,
        charge_type TEXT DEFAULT 'other' CHECK(charge_type IN ('minibar','laundry','room_service','phone','other')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bill_id) REFERENCES bills(id)
    )");

    $pdo->exec("CREATE TABLE activity_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        module TEXT,
        description TEXT,
        ip_address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES staff(id)
    )");

    echo "All 11 tables created.\n\n";

    // ========== SEED DATA ==========
    echo "Inserting seed data...\n";

    // Hash passwords
    $adminPw = password_hash('admin123', PASSWORD_BCRYPT);
    $staffPw = password_hash('staff123', PASSWORD_BCRYPT);

    // Roles
    $pdo->exec("INSERT INTO roles (id, role_name, description) VALUES (1, 'admin', 'System Administrator with full access')");
    $pdo->exec("INSERT INTO roles (id, role_name, description) VALUES (2, 'staff', 'Hotel Staff with limited access')");

    // Staff
    $pdo->prepare("INSERT INTO staff (id, username, password, first_name, last_name, email, phone, role_id, status) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([1, 'admin', $adminPw, 'John', 'Administrator', 'admin@hotel.com', '+63-900-123-4567', 1, 'active']);
    $pdo->prepare("INSERT INTO staff (id, username, password, first_name, last_name, email, phone, role_id, status) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([2, 'staff', $staffPw, 'Maria', 'Santos', 'maria@hotel.com', '+63-900-234-5678', 2, 'active']);
    $pdo->prepare("INSERT INTO staff (id, username, password, first_name, last_name, email, phone, role_id, status) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([3, 'staff2', $staffPw, 'Carlos', 'Reyes', 'carlos@hotel.com', '+63-900-345-6789', 2, 'active']);

    // Room Types
    $pdo->exec("INSERT INTO room_types (id, name, description, base_price, max_occupancy, amenities) VALUES (1, 'Standard', 'Comfortable room with essential amenities.', 99.00, 2, '{\"wifi\":true,\"tv\":true,\"ac\":true}')");
    $pdo->exec("INSERT INTO room_types (id, name, description, base_price, max_occupancy, amenities) VALUES (2, 'Deluxe', 'Spacious room with premium amenities and city view.', 159.00, 3, '{\"wifi\":true,\"tv\":true,\"ac\":true,\"minibar\":true,\"bathtub\":true}')");
    $pdo->exec("INSERT INTO room_types (id, name, description, base_price, max_occupancy, amenities) VALUES (3, 'Suite', 'Luxurious suite with separate living area.', 249.00, 4, '{\"wifi\":true,\"tv\":true,\"ac\":true,\"minibar\":true,\"bathtub\":true,\"kitchenette\":true}')");
    $pdo->exec("INSERT INTO room_types (id, name, description, base_price, max_occupancy, amenities) VALUES (4, 'Presidential', 'Top-floor suite with butler service.', 499.00, 6, '{\"wifi\":true,\"tv\":true,\"ac\":true,\"minibar\":true,\"bathtub\":true,\"kitchenette\":true,\"butler\":true}')");

    // Rooms (20 rooms)
    $rooms = [
        [1,'101',1,1,'available',null],[2,'102',1,1,'occupied','Long-stay guest'],[3,'103',1,1,'available',null],[4,'104',1,1,'maintenance','Plumbing repair'],[5,'105',1,1,'available',null],
        [6,'201',2,2,'available',null],[7,'202',2,2,'reserved','VIP guest tomorrow'],[8,'203',2,2,'available',null],[9,'204',2,2,'available',null],[10,'205',2,2,'occupied','Extended stay'],
        [11,'301',3,3,'available',null],[12,'302',3,3,'available',null],[13,'303',3,3,'occupied',null],[14,'304',3,3,'available',null],[15,'305',3,3,'maintenance','Deep cleaning'],
        [16,'401',4,4,'available',null],[17,'402',4,4,'available',null],[18,'403',4,4,'reserved',null],[19,'404',4,4,'available',null],[20,'405',4,4,'available',null],
    ];
    $stmt = $pdo->prepare("INSERT INTO rooms (id, room_number, room_type_id, floor, status, notes) VALUES (?,?,?,?,?,?)");
    foreach ($rooms as $r) $stmt->execute($r);

    // Guests (10)
    $guests = [
        [1,'James','Wilson','james.wilson@email.com','+1-555-0101','Passport','US12345678','American','123 Main St','New York','United States','10001',1],
        [2,'Maria','Cruz','maria.cruz@email.com','+63-917-123-4567','Passport','PH98765432','Filipino','456 Rizal Ave','Manila','Philippines','1000',0],
        [3,'Kenji','Tanaka','kenji.tanaka@email.com','+81-90-1234-5678','Passport','JP11223344','Japanese','7-2 Shibuya','Tokyo','Japan','150-0002',0],
        [4,'Sarah','Johnson','sarah.j@email.com','+44-20-7946-0958','Driver License','UKDL998877','British','10 Downing St','London','United Kingdom','SW1A 2AA',1],
        [5,'Hans','Mueller','hans.m@email.com','+49-30-1234-5678','National ID','DE55667788','German','Brandenburger Str 1','Berlin','Germany','10117',0],
        [6,'Sofia','Garcia','sofia.g@email.com','+34-91-123-4567','Passport','ES44332211','Spanish','Calle Gran Via 5','Madrid','Spain','28013',0],
        [7,'David','Lee','david.lee@email.com','+82-2-1234-5678','Passport','KR77889900','South Korean','Gangnam-gu 10','Seoul','South Korea','06124',0],
        [8,'Emma','Brown','emma.b@email.com','+61-2-1234-5678','Driver License','AUDL554433','Australian','1 Harbour St','Sydney','Australia','2000',0],
        [9,'Raj','Patel','raj.p@email.com','+91-22-1234-5678','Passport','IN66778899','Indian','MG Road 15','Mumbai','India','400001',0],
        [10,'Ana','Santos','ana.s@email.com','+63-918-987-6543','National ID','PHID112233','Filipino','789 Mabini St','Cebu','Philippines','6000',0],
    ];
    $stmt = $pdo->prepare("INSERT INTO guests (id, first_name, last_name, email, phone, id_type, id_number, nationality, address, city, country, zip_code, vip_status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($guests as $g) $stmt->execute($g);

    // Reservations (5) - using PHP date functions for portability
    $today = date('Y-m-d');
    $reservations = [
        [1,'RES-00001',1,2, date('Y-m-d', strtotime('-3 days')), date('Y-m-d', strtotime('+2 days')), 1, 'checked_in', 'Extra pillows please', 495.00, 1],
        [2,'RES-00002',4,10, date('Y-m-d', strtotime('-1 day')),  date('Y-m-d', strtotime('+4 days')), 2, 'checked_in', 'Late check-out if possible', 795.00, 2],
        [3,'RES-00003',3,13, $today,                               date('Y-m-d', strtotime('+5 days')), 2, 'confirmed', 'Non-smoking room', 1245.00, 1],
        [4,'RES-00004',2,7,  date('Y-m-d', strtotime('+1 day')),  date('Y-m-d', strtotime('+3 days')), 1, 'confirmed', null, 477.00, 2],
        [5,'RES-00005',5,18, date('Y-m-d', strtotime('+5 days')), date('Y-m-d', strtotime('+8 days')), 3, 'pending', 'Airport pickup needed', 1497.00, 1],
    ];
    $stmt = $pdo->prepare("INSERT INTO reservations (id, reservation_code, guest_id, room_id, check_in_date, check_out_date, num_guests, status, special_requests, total_amount, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    foreach ($reservations as $r) $stmt->execute($r);

    // Check-ins
    $stmt = $pdo->prepare("INSERT INTO check_ins (id, reservation_id, room_id, guest_id, actual_check_in, actual_check_out, status, processed_by) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([1, 1, 2, 1, date('Y-m-d H:i:s', strtotime('-3 days')), null, 'active', 2]);
    $stmt->execute([2, 2, 10, 4, date('Y-m-d H:i:s', strtotime('-1 day')), null, 'active', 1]);

    // Bills
    $pdo->exec("INSERT INTO bills (id, bill_number, reservation_id, guest_id, room_charges, extra_charges, tax_amount, discount, total_amount, status, created_by) VALUES (1, 'BILL-00001', 1, 1, 495.00, 35.00, 63.60, 0.00, 593.60, 'unpaid', 2)");
    $pdo->exec("INSERT INTO bills (id, bill_number, reservation_id, guest_id, room_charges, extra_charges, tax_amount, discount, total_amount, status, created_by) VALUES (2, 'BILL-00002', 2, 4, 795.00, 0.00, 95.40, 50.00, 840.40, 'partial', 1)");

    // Payments
    $pdo->prepare("INSERT INTO payments (id, bill_id, amount, payment_method, payment_date, reference_number, processed_by) VALUES (?,?,?,?,?,?,?)")
        ->execute([1, 2, 400.00, 'credit_card', date('Y-m-d H:i:s'), 'CC-REF-00123', 1]);

    // Extra Charges
    $pdo->exec("INSERT INTO extra_charges (bill_id, description, amount, charge_type) VALUES (1, 'Minibar - Beverages', 20.00, 'minibar')");
    $pdo->exec("INSERT INTO extra_charges (bill_id, description, amount, charge_type) VALUES (1, 'Room Service - Dinner', 15.00, 'room_service')");

    echo "Seed data inserted.\n\n";

    // ========== CREATE INDEXES ==========
    echo "Creating indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_rooms_type ON rooms(room_type_id)",
        "CREATE INDEX IF NOT EXISTS idx_rooms_status ON rooms(status)",
        "CREATE INDEX IF NOT EXISTS idx_reservations_guest ON reservations(guest_id)",
        "CREATE INDEX IF NOT EXISTS idx_reservations_room ON reservations(room_id)",
        "CREATE INDEX IF NOT EXISTS idx_reservations_status ON reservations(status)",
        "CREATE INDEX IF NOT EXISTS idx_checkins_status ON check_ins(status)",
        "CREATE INDEX IF NOT EXISTS idx_bills_status ON bills(status)",
        "CREATE INDEX IF NOT EXISTS idx_payments_bill ON payments(bill_id)",
    ];
    foreach ($indexes as $sql) $pdo->exec($sql);

    echo "Indexes created.\n\n";

    // ========== DONE ==========
    echo "========================================\n";
    echo "Setup completed successfully!\n";
    echo "========================================\n\n";
    echo "Database file: $dbPath\n";
    echo "File size: " . round(filesize($dbPath) / 1024, 1) . " KB\n\n";
    echo "Default accounts:\n";
    echo "  Admin: admin / admin123\n";
    echo "  Staff: staff / staff123\n\n";
    echo "Run: php -S localhost:8000\n";
    echo "Then open: http://localhost:8000\n";

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";
