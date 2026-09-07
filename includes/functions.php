<?php
/**
 * Event Booking Management System
 * General Utility & Presentation Helper Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

/**
 * Format currency with symbol and 2 decimal places
 */
function format_currency(float|int|string|null $amount): string {
    $val = floatval($amount ?? 0);
    if ($val == 0) {
        return 'Free';
    }
    return CURRENCY_SYMBOL . number_format($val, 2);
}

/**
 * Format date nicely (e.g. "Sep 15, 2026")
 */
function format_date(?string $date, string $format = 'M d, Y'): string {
    if (empty($date)) return '—';
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '—';
}

/**
 * Format time nicely (e.g. "10:00 AM")
 */
function format_time(?string $time, string $format = 'h:i A'): string {
    if (empty($time)) return '—';
    $timestamp = strtotime($time);
    return $timestamp ? date($format, $timestamp) : '—';
}

/**
 * Format date and time together
 */
function format_datetime(?string $datetime, string $format = 'M d, Y · h:i A'): string {
    if (empty($datetime)) return '—';
    $timestamp = strtotime($datetime);
    return $timestamp ? date($format, $timestamp) : '—';
}

/**
 * Return styled badge HTML for event statuses
 */
function event_status_badge(string $status): string {
    $map = [
        'open'        => ['label' => 'Open for Booking', 'class' => 'badge-success'],
        'almost_full' => ['label' => 'Filling Fast',     'class' => 'badge-warning'],
        'full'        => ['label' => 'Sold Out',         'class' => 'badge-danger'],
        'completed'   => ['label' => 'Completed',        'class' => 'badge-neutral'],
        'cancelled'   => ['label' => 'Cancelled',        'class' => 'badge-danger'],
        'draft'       => ['label' => 'Draft',            'class' => 'badge-secondary']
    ];

    $item = $map[strtolower($status)] ?? ['label' => ucfirst($status), 'class' => 'badge-neutral'];
    return sprintf('<span class="badge %s">%s</span>', $item['class'], e($item['label']));
}

/**
 * Return styled badge HTML for booking statuses
 */
function booking_status_badge(string $status): string {
    $map = [
        'confirmed'       => ['label' => 'Confirmed',       'class' => 'badge-success'],
        'pending_payment' => ['label' => 'Pending Payment', 'class' => 'badge-warning'],
        'cancelled'       => ['label' => 'Cancelled',       'class' => 'badge-danger'],
        'refunded'        => ['label' => 'Refunded',        'class' => 'badge-info']
    ];

    $item = $map[strtolower($status)] ?? ['label' => ucfirst($status), 'class' => 'badge-neutral'];
    return sprintf('<span class="badge %s">%s</span>', $item['class'], e($item['label']));
}

/**
 * Return styled badge HTML for payment statuses
 */
function payment_status_badge(string $status): string {
    $map = [
        'successful' => ['label' => 'Paid',     'class' => 'badge-success'],
        'pending'    => ['label' => 'Pending',  'class' => 'badge-warning'],
        'failed'     => ['label' => 'Failed',   'class' => 'badge-danger'],
        'refunded'   => ['label' => 'Refunded', 'class' => 'badge-info']
    ];

    $item = $map[strtolower($status)] ?? ['label' => ucfirst($status), 'class' => 'badge-neutral'];
    return sprintf('<span class="badge %s">%s</span>', $item['class'], e($item['label']));
}

/**
 * Generate unique human-readable booking reference (e.g. EVT-2026-X8F2B)
 */
function generate_booking_reference(): string {
    $year = date('Y');
    $random = strtoupper(bin2hex(random_bytes(3))); // 6 hex characters
    return sprintf('EVT-%s-%s', $year, $random);
}

/**
 * Generate unique ticket pass reference (e.g. TCK-9A4B-3F1C)
 */
function generate_ticket_code(): string {
    $part1 = strtoupper(bin2hex(random_bytes(2)));
    $part2 = strtoupper(bin2hex(random_bytes(2)));
    return sprintf('TCK-%s-%s', $part1, $part2);
}

/**
 * Generate unique transaction reference (e.g. TXN-172554-8A3B)
 */
function generate_transaction_ref(): string {
    return 'TXN-' . time() . '-' . strtoupper(bin2hex(random_bytes(2)));
}

/**
 * Convert string into clean URL slug
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a-' . time() : $text;
}

/**
 * Record an audit log entry in the database
 */
function log_audit(?int $userId, string $action, ?string $details = null): void {
    $pdo = Database::getConnection();
    if (!$pdo) return;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt->execute([$userId, $action, $details, $ip]);
    } catch (PDOException $e) {
        // Silently catch audit log failure to not disrupt primary flow
    }
}

/**
 * Create a user notification
 */
function create_notification(int $userId, string $title, string $message, ?string $link = null): void {
    $pdo = Database::getConnection();
    if (!$pdo) return;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, link, is_read, created_at)
            VALUES (?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$userId, $title, $message, $link]);
    } catch (PDOException $e) {
        // Silently catch
    }
}

/**
 * Get active unread notifications for a user
 */
function get_unread_notifications(int $userId, int $limit = 5): array {
    $pdo = Database::getConnection();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Render Human-Crafted SVG Icon
 */
function render_svg_icon(string $name, string $extraClass = '', int $size = 20): string {
    $icons = [
        'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'location' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />',
        'ticket' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />',
        'user' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />',
        'check-circle' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'x' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />',
        'search' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />',
        'filter' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />',
        'currency' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
        'chart' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
        'download' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />',
        'printer' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.656h10.5z" />',
        'shield-check' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
        'plus' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />',
        'arrow-right' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />',
        'arrow-left' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />',
        'tag' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />'
    ];

    $svgContent = $icons[$name] ?? '<circle cx="12" cy="12" r="9" stroke-width="1.5" fill="none" stroke="currentColor"/>';
    return sprintf(
        '<svg class="icon icon-%s %s" width="%d" height="%d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">%s</svg>',
        e($name),
        e($extraClass),
        $size,
        $size,
        $svgContent
    );
}
