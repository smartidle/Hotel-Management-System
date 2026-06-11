<?php
require_once __DIR__ . '/../../includes/auth_check.php';
$page_title = 'Settings';
$active_page = 'settings';

// Only admin can access
if ($_SESSION['role_id'] != ROLE_ADMIN) {
    setFlash('error', t('access_denied'));
    redirect('/dashboard.php');
}

$baseUrl = getBaseUrl();

// Load settings from JSON file
$homeDir = getenv('USERPROFILE') ?: getenv('HOME') ?: 'C:\Users\Administrator';
$settingsDir = $homeDir . '\hotel_demo_data';
$settingsFile = $settingsDir . '\settings.json';

$defaultSettings = [
    'hotel_name' => 'Hotel Management System',
    'address' => '123 Main Street, City, Country',
    'phone' => '+1 234 567 8900',
    'email' => 'info@hotel-demo.com',
    'website' => 'https://hotel-demo.com',
    'tax_rate' => 12,
    'currency_symbol' => CURRENCY_SYMBOL,
    'email_notifications' => true,
    'sms_notifications' => false,
    'low_stock_alert' => true,
    'checkin_reminder_hours' => 24
];

if (file_exists($settingsFile)) {
    $saved = json_decode(file_get_contents($settingsFile), true);
    if (is_array($saved)) {
        $settings = array_merge($defaultSettings, $saved);
    } else {
        $settings = $defaultSettings;
    }
} else {
    $settings = $defaultSettings;
    // Create directory and default file
    if (!is_dir($settingsDir)) {
        mkdir($settingsDir, 0755, true);
    }
    file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT));
}

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h4><i class="bi bi-gear"></i> <?php echo t('nav_settings') ?: 'Settings'; ?></h4>
</div>

<div class="row g-4">
    <!-- Left Column -->
    <div class="col-lg-8">
        <!-- Hotel Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-building"></i> Hotel Information</h6>
            </div>
            <div class="card-body">
                <form id="hotelSettingsForm">
                    <input type="hidden" name="action" value="save">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Hotel Name</label>
                            <input type="text" class="form-control" name="hotel_name" value="<?php echo htmlspecialchars($settings['hotel_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($settings['phone']); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($settings['address']); ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($settings['email']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Website</label>
                            <input type="text" class="form-control" name="website" value="<?php echo htmlspecialchars($settings['website']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" class="form-control" name="tax_rate" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>" min="0" max="100" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" class="form-control" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>" maxlength="5">
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-bell"></i> Notification Settings</h6>
            </div>
            <div class="card-body">
                <form id="notificationSettingsForm">
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Email Notifications</strong>
                            <p class="text-muted mb-0 small">Receive booking confirmations and updates via email</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="email_notifications" id="emailNotif" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?> style="width: 48px; height: 24px; cursor: pointer;">
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>SMS Notifications</strong>
                            <p class="text-muted mb-0 small">Send SMS alerts for check-in/check-out reminders</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="sms_notifications" id="smsNotif" <?php echo $settings['sms_notifications'] ? 'checked' : ''; ?> style="width: 48px; height: 24px; cursor: pointer;">
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Low Stock Alert</strong>
                            <p class="text-muted mb-0 small">Get notified when inventory items are running low</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="low_stock_alert" id="lowStockAlert" <?php echo $settings['low_stock_alert'] ? 'checked' : ''; ?> style="width: 48px; height: 24px; cursor: pointer;">
                        </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Check-in Reminder (Hours)</strong>
                            <p class="text-muted mb-0 small">Hours before check-in to send a reminder</p>
                        </div>
                        <input type="number" class="form-control" name="checkin_reminder_hours" value="<?php echo htmlspecialchars($settings['checkin_reminder_hours']); ?>" min="1" max="72" style="width: 100px; text-align: center;">
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Save Notifications
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- System Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> System Info</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted">System Version</td>
                            <td class="fw-bold text-end">1.0.0</td>
                        </tr>
                        <tr>
                            <td class="text-muted">PHP Version</td>
                            <td class="fw-bold text-end"><?php echo PHP_VERSION; ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Database</td>
                            <td class="fw-bold text-end">SQLite <?php echo $pdo->query('SELECT sqlite_version()')->fetchColumn(); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Server Time</td>
                            <td class="fw-bold text-end" id="serverTime"><?php echo date('Y-m-d H:i:s'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Last Backup</td>
                            <td class="fw-bold text-end">
                                <?php
                                $backupMarker = $settingsDir . '\last_backup.txt';
                                if (file_exists($backupMarker)) {
                                    echo date('Y-m-d H:i', filemtime($backupMarker));
                                } else {
                                    echo '<span class="text-muted">Never</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="card border-0 shadow-sm border-danger" style="border: 2px solid #e74c3c !important;">
            <div class="card-header bg-transparent" style="border-bottom: 1px solid rgba(231,76,60,0.2);">
                <h6 class="mb-0 text-danger"><i class="bi bi-exclamation-triangle"></i> Danger Zone</h6>
            </div>
            <div class="card-body">
                <p class="small text-muted mb-3">These actions are irreversible. Please proceed with caution.</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-warning" id="btnClearCache">
                        <i class="bi bi-trash3"></i> Clear Cache
                    </button>
                    <button class="btn btn-outline-danger" id="btnResetData">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Test Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = document.querySelector('meta[name="baseUrl"]')?.content || '';
    const apiUrl = baseUrl ? baseUrl + '/modules/settings/api.php' : '/modules/settings/api.php';

    // Helper: show toast-like alert
    function showAlert(type, message) {
        const container = document.querySelector('.toast-container') || document.body;
        const toast = document.createElement('div');
        toast.className = 'toast-container position-fixed top-0 end-0 p-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="toast show align-items-center text-bg-${type === 'error' ? 'danger' : 'success'} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }

    // Save hotel settings
    document.getElementById('hotelSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.set('action', 'save');
        // Merge all settings
        formData.set('email_notifications', document.getElementById('emailNotif').checked ? '1' : '0');
        formData.set('sms_notifications', document.getElementById('smsNotif').checked ? '1' : '0');
        formData.set('low_stock_alert', document.getElementById('lowStockAlert').checked ? '1' : '0');

        try {
            const resp = await fetch(apiUrl, { method: 'POST', body: formData });
            const result = await resp.json();
            if (result.success) {
                showAlert('success', 'Settings saved successfully!');
            } else {
                showAlert('error', result.error || 'Failed to save settings');
            }
        } catch (err) {
            showAlert('error', 'Network error: ' + err.message);
        }
    });

    // Save notification settings
    document.getElementById('notificationSettingsForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.set('action', 'save');
        // Also include hotel info fields (hidden)
        const hotelForm = document.getElementById('hotelSettingsForm');
        hotelForm.querySelectorAll('input, textarea').forEach(el => {
            if (el.name !== 'action') formData.append(el.name, el.value);
        });
        formData.set('email_notifications', document.getElementById('emailNotif').checked ? '1' : '0');
        formData.set('sms_notifications', document.getElementById('smsNotif').checked ? '1' : '0');
        formData.set('low_stock_alert', document.getElementById('lowStockAlert').checked ? '1' : '0');

        try {
            const resp = await fetch(apiUrl, { method: 'POST', body: formData });
            const result = await resp.json();
            if (result.success) {
                showAlert('success', 'Notification settings saved!');
            } else {
                showAlert('error', result.error || 'Failed to save');
            }
        } catch (err) {
            showAlert('error', 'Network error: ' + err.message);
        }
    });

    // Clear cache
    document.getElementById('btnClearCache').addEventListener('click', async function() {
        if (!confirm('Are you sure you want to clear the cache?')) return;
        try {
            const formData = new FormData();
            formData.set('action', 'clear_cache');
            const resp = await fetch(apiUrl, { method: 'POST', body: formData });
            const result = await resp.json();
            if (result.success) {
                showAlert('success', 'Cache cleared successfully!');
            } else {
                showAlert('error', result.error || 'Failed');
            }
        } catch (err) {
            showAlert('error', 'Network error: ' + err.message);
        }
    });

    // Reset test data
    document.getElementById('btnResetData').addEventListener('click', async function() {
        if (!confirm('WARNING: This will reset all test data. This action cannot be undone. Are you sure?')) return;
        if (!confirm('This is your last chance. Proceed with data reset?')) return;
        try {
            const formData = new FormData();
            formData.set('action', 'reset_data');
            const resp = await fetch(apiUrl, { method: 'POST', body: formData });
            const result = await resp.json();
            if (result.success) {
                showAlert('success', 'Test data has been reset. Reloading...');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', result.error || 'Failed');
            }
        } catch (err) {
            showAlert('error', 'Network error: ' + err.message);
        }
    });

    // Update server time every second
    setInterval(function() {
        const el = document.getElementById('serverTime');
        if (el) {
            const now = new Date();
            el.textContent = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0');
        }
    }, 1000);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
