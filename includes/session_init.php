<?php
/**
 * Session Initialization Helper
 * Ensures session save path exists before starting session
 */
function ensureSessionPath() {
    $savePath = ini_get('session.save_path');
    if (empty($savePath)) {
        // Use system temp directory
        $savePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php_sessions';
    }
    if (!is_dir($savePath)) {
        @mkdir($savePath, 0777, true);
    }
    if (is_dir($savePath) && is_writable($savePath)) {
        session_save_path($savePath);
    }
}
ensureSessionPath();