<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'bat50582_batuna');
define('DB_PASS', 'Batuna1907');
define('DB_NAME', 'bat50582_batuna');

// Security settings
define('HASH_SALT', bin2hex(random_bytes(32)));
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 1800); // 30 minutes
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Site settings
define('SITE_URL', 'https://batuna.vn');
define('SITE_NAME', 'Hacklink Market');

// Email settings
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@example.com');
define('SMTP_PASS', 'your-password');
define('SMTP_FROM', 'noreply@example.com');
define('SMTP_FROM_NAME', 'Hacklink Market');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Set default timezone
date_default_timezone_set('Europe/Istanbul');