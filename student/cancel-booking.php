<?php
/**
 * CampusEvent Hub — Self-Service Booking Cancellation (student/cancel-booking.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

require_role(['student', 'admin']);

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$bookingId = (int)($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: ' . BASE_URL . '/student/bookings.php');
    exit;
}

// Fetch Booking
$stmt = $pdo->prepare("
    SELECT b.*, e.title as event_title, e.registration_deadline, e.available_seats, e.max_capacity
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    WHERE b.id = ? AND (b.user_id = ? OR ? = 'admin')
    LIMIT 1
");
$stmt->execute([$bookingId, $currentUser['id'], $currentUser['role']]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('danger', 'Booking record not found or unauthorized.');
    header('Location: ' . BASE_URL . '/student/bookings.php');
    exit;
}

if ($booking['status'] === 'cancelled') {
    setFlash('warning', 'This booking has already been cancelled.');
    header('Location: ' . BASE_URL . '/student/bookings.php');
    exit;
}

// Check deadline policy (Admin can override, students must cancel before deadline)
if ($currentUser['role'] !== 'admin' && strtotime($booking['registration_deadline']) < time()) {
    setFlash('danger', 'Cancellation is not permitted after the registration deadline.');
    header('Location: ' . BASE_URL . '/student/bookings.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Update Booking status to cancelled
    $updateBook = $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = 'Cancelled by student/admin before deadline', updated_at = NOW() WHERE id = ?");
    $updateBook->execute([$bookingId]);

    // 2. Increment Event Available Seats
    $qty = (int)$booking['quantity'];
    $newSeats = min((int)$booking['max_capacity'], (int)$booking['available_seats'] + $qty);
    $newStatus = ($newSeats > 0) ? 'open' : 'full';

    $updateEvt = $pdo->prepare("UPDATE events SET available_seats = ?, status = ? WHERE id = ?");
    $updateEvt->execute([$newSeats, $newStatus, $booking['event_id']]);

    // 3. Update payment status to refunded if payment existed
    $updatePay = $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE booking_id = ? AND status = 'successful'");
    $updatePay->execute([$bookingId]);

    // 4. Send Notification
    create_notification(
        $booking['user_id'],
        'Booking Cancelled',
        "Your booking for {$booking['event_title']} (Ref: {$booking['booking_reference']}) has been cancelled. Available seats have been released.",
        BASE_URL . '/student/bookings.php'
    );

    $pdo->commit();

    log_audit($currentUser['id'], 'BOOKING_CANCEL', "Cancelled booking ID {$bookingId} ({$booking['booking_reference']})");
    setFlash('success', "Booking #{$booking['booking_reference']} has been cancelled successfully. Your seat has been released.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Cancellation failed: ' . $e->getMessage());
}

header('Location: ' . BASE_URL . '/student/bookings.php');
exit;
