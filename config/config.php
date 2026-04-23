<?php
/**
 * Configuration File
 * Update these settings based on your XAMPP setup
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Default XAMPP has no password
define('DB_NAME', 'stores');

// Site Configuration
// Allow overriding base URL via environment for fixed deployments.
$siteUrlFromEnv = trim((string)(getenv('SITE_URL') ?: ''));

if ($siteUrlFromEnv !== '') {
    define('SITE_URL', rtrim($siteUrlFromEnv, '/') . '/');
} else {
    // Auto-detect public URL (works for direct access and reverse proxies like ngrok).
    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        $forwardedProto === 'https'
    );

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = trim((string)explode(',', (string)$host)[0]);

    define('SITE_URL', $scheme . '://' . $host . '/stores/');
}
define('APP_NAME', 'Manica Skyview Stores');
define('APP_VERSION', '1.0.0');

// Mail Configuration
define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'no-reply@localhost');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);

// Environment Configuration
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV !== 'production');
define('APP_MAX_EXECUTION_TIME', (int)(getenv('APP_MAX_EXECUTION_TIME') ?: (APP_ENV === 'production' ? 120 : 0)));
define('APP_MEMORY_LIMIT', getenv('APP_MEMORY_LIMIT') ?: (APP_ENV === 'production' ? '512M' : '256M'));

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('REMEMBER_ME_DAYS', 30);

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'gif']);

// Timezone
date_default_timezone_set('UTC');

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

// Runtime limits (best effort; some hosts may override these values)
if (APP_MAX_EXECUTION_TIME >= 0) {
    @ini_set('max_execution_time', (string)APP_MAX_EXECUTION_TIME);
    if (APP_MAX_EXECUTION_TIME > 0) {
        @set_time_limit(APP_MAX_EXECUTION_TIME);
    } elseif (APP_MAX_EXECUTION_TIME === 0) {
        @set_time_limit(0);
    }
}
if (!empty(APP_MEMORY_LIMIT)) {
    @ini_set('memory_limit', (string)APP_MEMORY_LIMIT);
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
if (!APP_DEBUG) {
    header("Content-Security-Policy: default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; frame-ancestors 'self';");
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $secureCookie = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        $forwardedProto === 'https'
    );
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
