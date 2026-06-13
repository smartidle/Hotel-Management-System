<?php
/**
 * Language Switch API
 */
require_once __DIR__ . '/../includes/session_init.php';
session_start();
require_once __DIR__ . '/../config/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lang = isset($_POST['lang']) ? $_POST['lang'] : DEFAULT_LANG;
    if (in_array($lang, SUPPORTED_LANGS)) {
        $_SESSION['lang'] = $lang;
    }
}

// Redirect back
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../dashboard.php';
header('Location: ' . $referer);
exit();
