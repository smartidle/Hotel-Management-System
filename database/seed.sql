-- ============================================================
-- Hotel Management System - Seed Data
-- Database: hotel_management
-- ============================================================

USE `hotel_management`;

-- ============================================================
-- Roles
-- ============================================================
INSERT INTO `roles` (`id`, `role_name`, `description`) VALUES
(1, 'admin', 'System Administrator with full access'),
(2, 'staff', 'Hotel Staff with limited access');

-- ============================================================
-- Staff (Passwords: admin123 / staff123)
-- NOTE: These are bcrypt hashes. If login fails, regenerate
-- using: php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
-- ============================================================
INSERT INTO `staff` (`id`, `username`, `password`, `first_name`, `last_name`, `email`, `phone`, `role_id`, `status`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Administrator', 'admin@hotel.com', '+63-900-123-4567', 1, 'active'),
(2, 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria', 'Santos', 'maria@hotel.com', '+63-900-234-5678', 2, 'active'),
(3, 'staff2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos', 'Reyes', 'carlos@hotel.com', '+63-900-345-6789', 2, 'active');

-- ============================================================
-- Room Types
-- ============================================================
INSERT INTO `room_types` (`id`, `name`, `description`, `base_price`, `max_occupancy`, `amenities`) VALUES
(1, 'Standard', 'Comfortable room with essential amenities for a pleasant stay.', 99.00, 2, '{"wifi":true,"tv":true,"ac":true,"minibar":false}'),
(2, 'Deluxe', 'Spacious room with premium amenities and city view.', 159.00, 3, '{"wifi":true,"tv":true,"ac":true,"minibar":true,"bathtub":true}'),
(3, 'Suite', 'Luxurious suite with separate living area and panoramic views.', 249.00, 4, '{"wifi":true,"tv":true,"ac":true,"minibar":true,"bathtub":true,"kitchenette":true}'),
(4, 'Presidential', 'Top-floor presidential suite with exclusive amenities and butler service.', 499.00, 6, '{"wifi":true,"tv":true,"ac":true,"minibar":true,"bathtub":true,"kitchenette":true,"butler":true,"jacuzzi":true}');

-- ============================================================
-- Rooms (20 rooms across 4 floors)
-- ============================================================
INSERT INTO `rooms` (`id`, `room_number`, `room_type_id`, `floor`, `status`, `notes`) VALUES
-- Floor 1: Standard (101-105)
(1,  '101', 1, 1, 'available', NULL),
(2,  '102', 1, 1, 'occupied',  'Currently occupied by a long-stay guest'),
(3,  '103', 1, 1, 'available', NULL),
(4,  '104', 1, 1, 'maintenance', 'Under renovation - plumbing repair'),
(5,  '105', 1, 1, 'available', NULL),
-- Floor 2: Deluxe (201-205)
(6,  '201', 2, 2, 'available', NULL),
(7,  '202', 2, 2, 'reserved', 'Reserved for VIP guest arriving tomorrow'),
(8,  '203', 2, 2, 'available', NULL),
(9,  '204', 2, 2, 'available', NULL),
(10, '205', 2, 2, 'occupied',  'Guest extended stay'),
-- Floor 3: Suite (301-305)
(11, '301', 3, 3, 'available', NULL),
(12, '302', 3, 3, 'available', NULL),
(13, '303', 3, 3, 'occupied',  NULL),
(14, '304', 3, 3, 'available', NULL),
(15, '305', 3, 3, 'maintenance', 'Deep cleaning scheduled'),
-- Floor 4: Presidential (401-405)
(16, '401', 4, 4, 'available', NULL),
(17, '402', 4, 4, 'available', NULL),
(18, '403', 4, 4, 'reserved', NULL),
(19, '404', 4, 4, 'available', NULL),
(20, '405', 4, 4, 'available', NULL);

-- ============================================================
-- Guests (10 sample guests)
-- ============================================================
INSERT INTO `guests` (`id`, `first_name`, `last_name`, `email`, `phone`, `id_type`, `id_number`, `nationality`, `address`, `city`, `country`, `zip_code`, `vip_status`) VALUES
(1,  'James', 'Wilson', 'james.wilson@email.com', '+1-555-0101', 'Passport', 'US12345678', 'American', '123 Main St', 'New York', 'United States', '10001', TRUE),
(2,  'Maria', 'Cruz', 'maria.cruz@email.com', '+63-917-123-4567', 'Passport', 'PH98765432', 'Filipino', '456 Rizal Ave', 'Manila', 'Philippines', '1000', FALSE),
(3,  'Kenji', 'Tanaka', 'kenji.tanaka@email.com', '+81-90-1234-5678', 'Passport', 'JP11223344', 'Japanese', '7-2 Shibuya', 'Tokyo', 'Japan', '150-0002', FALSE),
(4,  'Sarah', 'Johnson', 'sarah.j@email.com', '+44-20-7946-0958', 'Driver License', 'UKDL998877', 'British', '10 Downing St', 'London', 'United Kingdom', 'SW1A 2AA', TRUE),
(5,  'Hans', 'Mueller', 'hans.m@email.com', '+49-30-1234-5678', 'National ID', 'DE55667788', 'German', 'Brandenburger Str 1', 'Berlin', 'Germany', '10117', FALSE),
(6,  'Sofia', 'Garcia', 'sofia.g@email.com', '+34-91-123-4567', 'Passport', 'ES44332211', 'Spanish', 'Calle Gran Via 5', 'Madrid', 'Spain', '28013', FALSE),
(7,  'David', 'Lee', 'david.lee@email.com', '+82-2-1234-5678', 'Passport', 'KR77889900', 'South Korean', 'Gangnam-gu 10', 'Seoul', 'South Korea', '06124', FALSE),
(8,  'Emma', 'Brown', 'emma.b@email.com', '+61-2-1234-5678', 'Driver License', 'AUDL554433', 'Australian', '1 Harbour St', 'Sydney', 'Australia', '2000', FALSE),
(9,  'Raj', 'Patel', 'raj.p@email.com', '+91-22-1234-5678', 'Passport', 'IN66778899', 'Indian', 'MG Road 15', 'Mumbai', 'India', '400001', FALSE),
(10, 'Ana', 'Santos', 'ana.s@email.com', '+63-918-987-6543', 'National ID', 'PHID112233', 'Filipino', '789 Mabini St', 'Cebu', 'Philippines', '6000', FALSE);

-- ============================================================
-- Reservations (5 sample reservations)
-- ============================================================
INSERT INTO `reservations` (`id`, `reservation_code`, `guest_id`, `room_id`, `check_in_date`, `check_out_date`, `num_guests`, `status`, `special_requests`, `total_amount`, `created_by`) VALUES
(1, 'RES-00001', 1, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 1, 'checked_in', 'Extra pillows please', 495.00, 1),
(2, 'RES-00002', 4, 10, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 2, 'checked_in', 'Late check-out if possible', 795.00, 2),
(3, 'RES-00003', 3, 13, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 2, 'confirmed', 'Non-smoking room', 1245.00, 1),
(4, 'RES-00004', 2, 7, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 1, 'confirmed', NULL, 477.00, 2),
(5, 'RES-00005', 5, 18, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), 3, 'pending', 'Airport pickup needed', 1497.00, 1);

-- ============================================================
-- Check-ins (for checked-in reservations)
-- ============================================================
INSERT INTO `check_ins` (`id`, `reservation_id`, `room_id`, `guest_id`, `actual_check_in`, `actual_check_out`, `status`, `processed_by`) VALUES
(1, 1, 2, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), NULL, 'active', 2),
(2, 2, 10, 4, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, 'active', 1);

-- ============================================================
-- Bills
-- ============================================================
INSERT INTO `bills` (`id`, `bill_number`, `reservation_id`, `guest_id`, `room_charges`, `extra_charges`, `tax_amount`, `discount`, `total_amount`, `status`, `created_by`) VALUES
(1, 'BILL-00001', 1, 1, 495.00, 35.00, 63.60, 0.00, 593.60, 'unpaid', 2),
(2, 'BILL-00002', 2, 4, 795.00, 0.00, 95.40, 50.00, 840.40, 'partial', 1);

-- ============================================================
-- Payments
-- ============================================================
INSERT INTO `payments` (`id`, `bill_id`, `amount`, `payment_method`, `payment_date`, `reference_number`, `processed_by`) VALUES
(1, 2, 400.00, 'credit_card', NOW(), 'CC-REF-00123', 1);

-- ============================================================
-- Extra Charges (for bill 1)
-- ============================================================
INSERT INTO `extra_charges` (`bill_id`, `description`, `amount`, `charge_type`) VALUES
(1, 'Minibar - Beverages', 20.00, 'minibar'),
(1, 'Room Service - Dinner', 15.00, 'room_service');
