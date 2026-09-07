<?php
/**
 * CampusEvent Hub — CSV Data Export Engine (admin/reports/export.php)
 */

define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/auth.php';

require_role('admin');

$pdo = Database::getConnection();
if (!$pdo) {
    die('Database connection unavailable.');
}

$type = $_GET['type'] ?? 'bookings';
$filename = 'campusevent_' . $type . '_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

if ($type === 'bookings') {
    fputcsv($out, ['Booking Ref', 'Student Name', 'Student Email', 'Event Title', 'Event Date', 'Quantity', 'Unit Price', 'Total Amount', 'Status', 'Booked At']);

    $stmt = $pdo->query("
        SELECT b.booking_reference, u.name as student_name, u.email as student_email,
               e.title as event_title, e.event_date,
               b.quantity, b.unit_price, b.total_amount, b.status, b.booked_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN events e ON b.event_id = e.id
        ORDER BY b.booked_at DESC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['booking_reference'],
            $row['student_name'],
            $row['student_email'],
            $row['event_title'],
            $row['event_date'],
            $row['quantity'],
            $row['unit_price'],
            $row['total_amount'],
            $row['status'],
            $row['booked_at']
        ]);
    }

} elseif ($type === 'payments' || $type === 'financial') {
    fputcsv($out, ['Transaction Ref', 'Booking Ref', 'Payer Name', 'Payer Email', 'Event Title', 'Payment Method', 'Amount', 'Status', 'Paid Timestamp']);

    $stmt = $pdo->query("
        SELECT p.transaction_reference, b.booking_reference, u.name as payer_name, u.email as payer_email,
               e.title as event_title, p.payment_method, p.amount, p.status, p.paid_at
        FROM payments p
        JOIN bookings b ON p.booking_id = b.id
        JOIN users u ON b.user_id = u.id
        JOIN events e ON b.event_id = e.id
        ORDER BY p.created_at DESC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['transaction_reference'],
            $row['booking_reference'],
            $row['payer_name'],
            $row['payer_email'],
            $row['event_title'],
            strtoupper($row['payment_method']),
            $row['amount'],
            $row['status'],
            $row['paid_at'] ?? '—'
        ]);
    }

} elseif ($type === 'students') {
    fputcsv($out, ['Student ID Number', 'Full Name', 'University Email', 'Department', 'Year of Study', 'Phone', 'Emergency Contact', 'Status', 'Registered Date']);

    $stmt = $pdo->query("
        SELECT s.student_id_number, u.name, u.email, s.department, s.year_of_study, u.phone, s.emergency_contact, u.status, u.created_at
        FROM users u
        JOIN students s ON u.id = s.user_id
        WHERE u.role = 'student'
        ORDER BY s.student_id_number ASC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['student_id_number'],
            $row['name'],
            $row['email'],
            $row['department'],
            $row['year_of_study'],
            $row['phone'] ?? '—',
            $row['emergency_contact'] ?? '—',
            $row['status'],
            $row['created_at']
        ]);
    }

} elseif ($type === 'events') {
    fputcsv($out, ['Event ID', 'Title', 'Category', 'Coordinator', 'Date', 'Start Time', 'End Time', 'Venue', 'Ticket Price', 'Max Capacity', 'Available Seats', 'Status', 'Created Date']);

    $stmt = $pdo->query("
        SELECT e.id, e.title, c.name as category_name, u.name as coordinator_name,
               e.event_date, e.start_time, e.end_time, e.venue, e.ticket_price,
               e.max_capacity, e.available_seats, e.status, e.created_at
        FROM events e
        JOIN event_categories c ON e.category_id = c.id
        LEFT JOIN users u ON e.coordinator_id = u.id
        ORDER BY e.event_date DESC
    ");

    while ($row = $stmt->fetch()) {
        fputcsv($out, [
            $row['id'],
            $row['title'],
            $row['category_name'],
            $row['coordinator_name'] ?? 'Unassigned',
            $row['event_date'],
            $row['start_time'],
            $row['end_time'],
            $row['venue'],
            $row['ticket_price'],
            $row['max_capacity'],
            $row['available_seats'],
            $row['status'],
            $row['created_at']
        ]);
    }
}

fclose($out);
exit;
