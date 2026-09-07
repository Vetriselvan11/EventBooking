<?php
/**
 * CampusEvent Hub — Check-In Action Endpoint (coordinator/attendees/checkin.php)
 */

define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

require_role(['coordinator', 'admin']);

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $ticketId = (int)($_POST['ticket_id'] ?? 0);
    $eventId = (int)($_POST['event_id'] ?? 0);
    $newStatus = (int)($_POST['status'] ?? 1); // 1 for check-in, 0 for undo

    if ($ticketId > 0) {
        try {
            $checkedInAt = ($newStatus === 1) ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare("UPDATE booking_attendees SET is_checked_in = ?, checked_in_at = ? WHERE id = ?");
            $stmt->execute([$newStatus, $checkedInAt, $ticketId]);

            $actionText = ($newStatus === 1) ? 'Checked in' : 'Reverted check-in for';
            log_audit(get_current_user_id(), 'ATTENDEE_CHECKIN_UPDATE', "{$actionText} ticket ID {$ticketId}");
            setFlash('success', "Attendee check-in status updated successfully.");

        } catch (PDOException $e) {
            setFlash('danger', 'Failed to update check-in: ' . $e->getMessage());
        }
    }
}

$redirectUrl = BASE_URL . '/coordinator/attendees/index.php' . ($eventId > 0 ? '?event_id=' . $eventId : '');
header('Location: ' . $redirectUrl);
exit;
