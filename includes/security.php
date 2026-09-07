<?php
/**
 * Event Booking Management System
 * Security, CSRF Protection & Sanitization Utilities
 */

require_once __DIR__ . '/session.php';

/**
 * Generate or retrieve the current CSRF token
 */
function get_csrf_token(): string {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

/**
 * Render a hidden HTML CSRF input field
 */
function csrf_field(): string {
    $token = get_csrf_token();
    return '<input type="hidden" name="' . CSRF_TOKEN_KEY . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify incoming CSRF token on POST requests
 */
function verify_csrf_token(): bool {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    
    $token = $_POST[CSRF_TOKEN_KEY] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || empty($_SESSION[CSRF_TOKEN_KEY])) {
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_KEY], $token);
}

/**
 * Require valid CSRF token or terminate with 403
 */
function require_csrf_token(): void {
    if (!verify_csrf_token()) {
        http_response_code(403);
        die('<!DOCTYPE html><html><head><title>403 Forbidden</title><style>body{font-family:sans-serif;padding:40px;background:#f8fafc;color:#0f172a;text-align:center;} .box{max-width:500px;margin:50px auto;background:#fff;padding:30px;border-radius:8px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);}</style></head><body><div class="box"><h2>403 - Invalid Security Token</h2><p>Your session or form security token has expired or is invalid. Please return to the previous page and refresh.</p><a href="javascript:history.back()" style="display:inline-block;padding:10px 20px;background:#1e40af;color:#fff;text-decoration:none;border-radius:6px;margin-top:15px;">Go Back</a></div></body></html>');
    }
}

/**
 * Output escaping helper (XSS prevention)
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize string input
 */
function sanitize_string(string $input): string {
    return trim(filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS));
}

/**
 * Secure password hashing using Argon2id or Bcrypt
 */
function hash_password(string $plainPassword): string {
    if (defined('PASSWORD_ARGON2ID')) {
        return password_hash($plainPassword, PASSWORD_ARGON2ID, ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]);
    }
    return password_hash($plainPassword, PASSWORD_DEFAULT, ['cost' => 12]);
}

/**
 * Verify password against stored hash
 */
function verify_password(string $plainPassword, string $hash): bool {
    return password_verify($plainPassword, $hash);
}
