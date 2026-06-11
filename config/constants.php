<?php
/**
 * System Constants
 */

// Roles
define('ROLE_ADMIN', 1);
define('ROLE_STAFF', 2);

// Room Status
define('ROOM_AVAILABLE', 'available');
define('ROOM_OCCUPIED', 'occupied');
define('ROOM_MAINTENANCE', 'maintenance');
define('ROOM_RESERVED', 'reserved');

// Reservation Status
define('RES_PENDING', 'pending');
define('RES_CONFIRMED', 'confirmed');
define('RES_CHECKED_IN', 'checked_in');
define('RES_CHECKED_OUT', 'checked_out');
define('RES_CANCELLED', 'cancelled');
define('RES_NO_SHOW', 'no_show');

// Bill Status
define('BILL_UNPAID', 'unpaid');
define('BILL_PARTIAL', 'partial');
define('BILL_PAID', 'paid');

// Check-in Status
define('CHECKIN_ACTIVE', 'active');
define('CHECKIN_COMPLETED', 'completed');

// Payment Methods
define('PAY_CASH', 'cash');
define('PAY_CREDIT_CARD', 'credit_card');
define('PAY_DEBIT_CARD', 'debit_card');
define('PAY_BANK_TRANSFER', 'bank_transfer');
define('PAY_ONLINE', 'online');

// Charge Types
define('CHARGE_MINIBAR', 'minibar');
define('CHARGE_LAUNDRY', 'laundry');
define('CHARGE_ROOM_SERVICE', 'room_service');
define('CHARGE_PHONE', 'phone');
define('CHARGE_OTHER', 'other');
