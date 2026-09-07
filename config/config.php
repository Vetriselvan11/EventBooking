<?php
/**
 * Event Booking Management System
 * Global Application Configuration
 */

// Prevent direct script access if not included
defined('APP_ROOT') or define('APP_ROOT', dirname(__DIR__));

// Application Metadata
define('APP_NAME', 'CampusEvent Hub');
define('APP_TAGLINE', 'Enterprise Event Discovery & Booking Platform');
define('APP_VERSION', '1.0.0');

// Environment & Debugging (Set to false in production)
define('APP_DEBUG', true);
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Base URL Detection (Auto-adapts to XAMPP subfolders or root domain)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine the relative path from document root
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $appRoot = str_replace('\\', '/', APP_ROOT);
    $subDir = '';
    
    if (!empty($docRoot) && strpos($appRoot, $docRoot) === 0) {
        $subDir = substr($appRoot, strlen($docRoot));
    }
    
    // Clean up trailing slash
    $subDir = rtrim($subDir, '/');
    define('BASE_URL', $protocol . $host . $subDir);
}

// Security & Session Constants
define('SESSION_LIFETIME', 86400); // 24 hours in seconds
define('CSRF_TOKEN_KEY', '_csrf_token');
define('PASSWORD_MIN_LENGTH', 6);

// Currency Configuration
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_CODE', 'USD');

// Upload Paths
define('UPLOAD_DIR', APP_ROOT . '/public/assets/uploads');
define('UPLOAD_URL', BASE_URL . '/public/assets/uploads');

// Default Database Settings (XAMPP Standard Defaults)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'event_booking_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');
