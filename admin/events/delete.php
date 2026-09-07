<?php
/**
 * CampusEvent Hub — Delete / Cancel Event (admin/events/delete.php)
 */

define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

require_role('admin');

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$eventId = (int)($_GET['id'] ?? 0);

if ($eventId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch Event with Bookings count
$stmt = $pdo->prepare("
    SELECT e.*, (SELECT COUNT(id) FROM bookings WHERE event_id = e.id) as booking_count
    FROM events e
    WHERE e.id = ? LIMIT 1
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('danger', 'Event not found.');
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    if ($event['booking_count'] > 0) {
        // Event has active bookings -> Cancel safely rather than hard delete
        $updateEvt = $pdo->prepare("UPDATE events SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
        $updateEvt->execute([$eventId]);

        $updateBookings = $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = 'Event cancelled by administration', updated_at = NOW() WHERE event_id = ?");
        $updateBookings->execute([$eventId]);

        $updatePayments = $pdo->prepare("
            UPDATE payments p 
            JOIN bookings b ON p.booking_id = b.id 
            SET p.status = 'refunded' 
            WHERE b.event_id = ? AND p.status = 'successful'
        ");
        $updatePayments->execute([$eventId]);

        log_audit($currentUser['id'], 'ADMIN_EVENT_CANCEL', "Cancelled event #{$eventId} ({$event['title']}) and refunded {$event['booking_count']} booking(s).");
        setFlash('warning', "Event '{$event['title']}' has existing bookings. It was safely marked as CANCELLED and bookings were updated.");

    } else {
        // 0 bookings -> Safe to hard delete
        $del = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $del->execute([$eventId]);

        log_audit($currentUser['id'], 'ADMIN_EVENT_DELETE', "Hard deleted event #{$eventId} ({$event['title']})");
        setFlash('success', "Event '{$event['title']}' was deleted permanently.");
    }

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    setFlash('danger', 'Operation failed: ' . $e->getMessage());
}

header('Location: index.php');
exit;
