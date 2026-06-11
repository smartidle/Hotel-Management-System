<?php
/**
 * Common Utility Functions
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Set flash message in session
 */
function setFlash($type, $message) {
    if (!isset($_SESSION)) session_start();
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Load language file based on session preference
 */
function loadLanguage() {
    global $LANG;
    $lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : DEFAULT_LANG;
    if (!in_array($lang, SUPPORTED_LANGS)) {
        $lang = DEFAULT_LANG;
    }
    $langFile = __DIR__ . '/../lang/' . $lang . '.php';
    if (file_exists($langFile)) {
        require $langFile;
    } else {
        require __DIR__ . '/../lang/en.php';
    }
    return $LANG;
}

/**
 * Translate key - returns translated string or key itself
 */
function t($key) {
    global $LANG;
    return isset($LANG[$key]) ? $LANG[$key] : $key;
}

/**
 * Generate unique code with prefix (e.g., RES-00001)
 */
function generateCode($prefix, $pdo, $table, $column) {
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING($column, " . (strlen($prefix) + 2) . ") AS UNSIGNED) AS max_code FROM $table WHERE $column LIKE ?");
    $like = $prefix . '-%';
    $stmt = $pdo->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$like]);
    $last = $stmt->fetch();
    $next = 1;
    if ($last) {
        $parts = explode('-', $last[$column]);
        $next = (int)end($parts) + 1;
    }
    return $prefix . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
}

/**
 * Format currency amount
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format((float)$amount, 2, '.', ',');
}

/**
 * Log activity to database
 */
function logActivity($pdo, $userId, $action, $module, $description = '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, module, description, ip_address) VALUES (?, ?, ?, ?, ?)");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->execute([$userId, $action, $module, $description, $ip]);
    } catch (Exception $e) {
        // Silently fail - logging should not break the app
    }
}

/**
 * Generate CSRF token
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get current language
 */
function getCurrentLang() {
    return isset($_SESSION['lang']) ? $_SESSION['lang'] : DEFAULT_LANG;
}

/**
 * Calculate date difference in days
 */
function daysBetween($date1, $date2) {
    $d1 = new DateTime($date1);
    $d2 = new DateTime($date2);
    $diff = $d1->diff($d2);
    return $diff->days;
}

/**
 * Get base URL for the application
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME']);
    // Go up if we're in a subdirectory like modules/rooms/
    if (strpos($script, '/modules') !== false || strpos($script, '/api') !== false) {
        $script = dirname($script);
        if (strpos($script, '/modules') !== false) {
            $script = dirname($script);
        }
    }
    $script = rtrim($script, '/\\');
    return $protocol . '://' . $host . $script;
}
