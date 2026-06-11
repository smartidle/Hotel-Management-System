<?php
/**
 * Database Configuration
 * PDO connection to SQLite (no MySQL installation required)
 */
$homeDir = getenv('USERPROFILE') ?: getenv('HOME') ?: 'C:\Users\Administrator';
$dbPath = $homeDir . '\hotel_demo_data\hotel_management.sqlite';

try {
    $pdo = new PDO(
        "sqlite:" . $dbPath,
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    // Enable foreign key support in SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
