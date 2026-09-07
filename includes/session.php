<?php
/**
 * Event Booking Management System
 * Secure Session & Flash Messaging Bootstrap
 */

require_once dirname(__DIR__) . '/config/config.php';

// Configure secure session cookie settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/**
 * Flash Messaging Helpers
 */
function setFlash(string $type, string $message): void {
    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }
    $_SESSION['_flash'][] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlashMessages(): array {
    if (!isset($_SESSION['_flash']) || empty($_SESSION['_flash'])) {
        return [];
    }
    $messages = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
    return $messages;
}

function hasFlashMessages(): bool {
    return !empty($_SESSION['_flash']);
}

/**
 * Render flash messages as styled banners
 */
function renderFlashMessages(): string {
    $messages = getFlashMessages();
    if (empty($messages)) {
        return '';
    }

    $output = '<div class="flash-messages-container" id="flash-container">';
    foreach ($messages as $msg) {
        $type = htmlspecialchars($msg['type']);
        $text = htmlspecialchars($msg['message']);
        $output .= sprintf(
            '<div class="alert alert-%s alert-dismissible" role="alert">
                <div class="alert-content">%s</div>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
            </div>',
            $type,
            $text
        );
    }
    $output .= '</div>';
    return $output;
}
