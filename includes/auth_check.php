<?php
/**
 * Authentication Check
 * Include this at the top of every protected page
 */
require_once __DIR__ . '/session_init.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    // Calculate redirect path to root index.php
    $script = $_SERVER['SCRIPT_NAME'];
    $depth = substr_count(trim(dirname($script), '/'), '/') - 0; // adjust based on project root
    // Simple approach: always redirect to root
    $base = '/';
    // Try to detect if we're in a subdirectory
    if (strpos($script, '/modules/') !== false) {
        $base = '../../index.php';
    } elseif (strpos($script, '/api/') !== false) {
        $base = '../index.php';
    } else {
        $base = 'index.php';
    }
    header('Location: ' . $base);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';
$LANG = loadLanguage();
