<?php
/**
 * Rai by Rai - Configuration File
 */

// Prevent direct access
if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}

// ─── DATABASE SETTINGS ───
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change this to your database password
define('DB_NAME', 'raibyrai_db');

// ─── EMAIL SETTINGS ───
// Change these settings for live SMTP email dispatching
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'rai@raibyrai.com'); // Your email
define('SMTP_PASS', ''); // Your email App Password
define('SMTP_FROM', 'rai@raibyrai.com');
define('SMTP_FROM_NAME', 'Rai by Rai Advisory');
define('ADMIN_EMAIL', 'rai@raibyrai.com'); // Where admin notifications go

// ─── ESTABLISH PDO CONNECTION ───
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // In production, do not expose $e->getMessage() for security
    die("Database Connection Failed: " . $e->getMessage());
}
