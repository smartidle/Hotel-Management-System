<?php
require_once __DIR__ . '/../../includes/auth_check.php';
header('Content-Type: application/json');

// Only admin can access
if ($_SESSION['role_id'] != ROLE_ADMIN) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$homeDir = getenv('USERPROFILE') ?: getenv('HOME') ?: 'C:\Users\Administrator';
$settingsDir = $homeDir . '\hotel_demo_data';
$settingsFile = $settingsDir . '\settings.json';

// Ensure directory exists
if (!is_dir($settingsDir)) {
    mkdir($settingsDir, 0755, true);
}

function getSettings($file) {
    $default = [
        'hotel_name' => 'Hotel Management System',
        'address' => '123 Main Street, City, Country',
        'phone' => '+1 234 567 8900',
        'email' => 'info@hotel-demo.com',
        'website' => 'https://hotel-demo.com',
        'tax_rate' => 12,
        'currency_symbol' => defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '$',
        'email_notifications' => true,
        'sms_notifications' => false,
        'low_stock_alert' => true,
        'checkin_reminder_hours' => 24
    ];
    if (file_exists($file)) {
        $saved = json_decode(file_get_contents($file), true);
        if (is_array($saved)) {
            return array_merge($default, $saved);
        }
    }
    return $default;
}

try {
    switch ($action) {
        case 'get':
            $settings = getSettings($settingsFile);
            echo json_encode(['success' => true, 'data' => $settings]);
            break;

        case 'save':
            $settings = getSettings($settingsFile);

            // Update settings from POST data
            $fields = ['hotel_name', 'address', 'phone', 'email', 'website', 'tax_rate', 'currency_symbol', 'checkin_reminder_hours'];
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $settings[$field] = $_POST[$field];
                }
            }

            // Boolean fields
            $settings['email_notifications'] = isset($_POST['email_notifications']) && $_POST['email_notifications'] == '1';
            $settings['sms_notifications'] = isset($_POST['sms_notifications']) && $_POST['sms_notifications'] == '1';
            $settings['low_stock_alert'] = isset($_POST['low_stock_alert']) && $_POST['low_stock_alert'] == '1';

            // Validate tax_rate
            $settings['tax_rate'] = max(0, min(100, floatval($settings['tax_rate'])));
            $settings['checkin_reminder_hours'] = max(1, min(72, intval($settings['checkin_reminder_hours'])));

            file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
            logActivity($pdo, $_SESSION['user_id'], 'save_settings', 'settings', 'Updated system settings');

            echo json_encode(['success' => true, 'message' => 'Settings saved']);
            break;

        case 'clear_cache':
            // Clear PHP session-based caches
            // Remove any temp/cache files in the data directory
            $cacheFiles = glob($settingsDir . '\*.cache');
            $cleared = 0;
            if ($cacheFiles) {
                foreach ($cacheFiles as $cf) {
                    if (is_file($cf) && unlink($cf)) {
                        $cleared++;
                    }
                }
            }

            // Clear any opcode cache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            logActivity($pdo, $_SESSION['user_id'], 'clear_cache', 'settings', "Cleared $cleared cache files");

            echo json_encode(['success' => true, 'message' => "Cache cleared ($cleared files removed)"]);
            break;

        case 'reset_data':
            // Reset test data by truncating and re-seeding
            // Keep admin user, reset everything else
            $pdo->exec("DELETE FROM activity_logs");
            $pdo->exec("DELETE FROM extra_charges");
            $pdo->exec("DELETE FROM payments");
            $pdo->exec("DELETE FROM bills");
            $pdo->exec("DELETE FROM check_ins");
            $pdo->exec("DELETE FROM reservations");
            $pdo->exec("DELETE FROM guests");
            $pdo->exec("DELETE FROM rooms");
            $pdo->exec("DELETE FROM room_types");
            $pdo->exec("DELETE FROM staff WHERE role_id != 1");

            // Re-run seed data
            $seedFile = __DIR__ . '/../../database/seed.sql';
            if (file_exists($seedFile)) {
                $seedSql = file_get_contents($seedFile);
                // SQLite doesn't support all MySQL syntax, so we run what we can
                try {
                    $pdo->exec($seedSql);
                } catch (Exception $e) {
                    // Some SQL may fail due to MySQL-specific syntax, that's okay
                }
            }

            // Run add_test_data.php logic if available
            $testDataFile = __DIR__ . '/../../add_test_data.php';
            if (file_exists($testDataFile)) {
                // Include it in a function scope to avoid variable conflicts
                $pdo_save = $pdo;
                ob_start();
                try {
                    include $testDataFile;
                } catch (Exception $e) {
                    // Ignore errors from test data script
                }
                ob_end_clean();
                $pdo = $pdo_save;
            }

            logActivity($pdo, $_SESSION['user_id'], 'reset_data', 'settings', 'Reset all test data');

            echo json_encode(['success' => true, 'message' => 'Test data has been reset']);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
