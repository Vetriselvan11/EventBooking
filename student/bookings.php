<?php
/**
 * CampusEvent Hub — Student Booking History (student/bookings.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

require_role('student');

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$userId = $currentUser['id'];

// Status Filter
$statusFilter = $_GET['status'] ?? 'all';

$where = ["b.user_id = ?"];
$params = [$userId];

if ($statusFilter !== 'all') {
    $where[] = "b.status = ?";
    $params[] = $statusFilter;
}

$sql = "
    SELECT b.*, 
           e.title as event_title, e.event_date, e.start_time, e.venue, e.registration_deadline,
           c.name as category_name,
           p.payment_method, p.status as payment_status
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN event_categories c ON e.category_id = c.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY b.booked_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'Booking History — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>My Booking History</h1>
                <p>Track all registered events, manage cancellations within policy, and review payment receipts.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-primary btn-sm">
                    <?= render_svg_icon('plus', '', 16) ?> Book New Event
                </a>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="filter-toolbar">
            <div class="filter-group-left">
                <span style="font-weight:600; font-size:0.88rem; color:var(--text-secondary);">Filter Status:</span>
                <a href="bookings.php?status=all" class="btn btn-sm <?= ($statusFilter === 'all') ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
                <a href="bookings.php?status=confirmed" class="btn btn-sm <?= ($statusFilter === 'confirmed') ? 'btn-primary' : 'btn-outline-secondary' ?>">Confirmed</a>
                <a href="bookings.php?status=pending_payment" class="btn btn-sm <?= ($statusFilter === 'pending_payment') ? 'btn-primary' : 'btn-outline-secondary' ?>">Pending Payment</a>
                <a href="bookings.php?status=cancelled" class="btn btn-sm <?= ($statusFilter === 'cancelled') ? 'btn-primary' : 'btn-outline-secondary' ?>">Cancelled</a>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (!empty($bookings)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Event Details</th>
                                    <th>Event Date</th>
                                    <th>Quantity</th>
                                    <th>Total Paid</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): 
                                    $canCancel = ($b['status'] === 'confirmed') && (strtotime($b['registration_deadline']) > time());
                                ?>
                                    <tr>
                                        <td>
                                            <span style="font-family:monospace; font-weight:700; color:var(--primary-900);">
                                                <?= e($b['booking_reference']) ?>
                                            </span>
                                            <div style="font-size:0.75rem; color:var(--text-muted);">
                                                <?= format_datetime($b['booked_at']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight:700; color:var(--text-primary);">
                                                <a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $b['event_id'] ?>">
                                                    <?= e($b['event_title']) ?>
                                                </a>
                                            </div>
                                            <div style="font-size:0.78rem; color:var(--text-secondary);"><?= e($b['venue']) ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;"><?= format_date($b['event_date']) ?></div>
                                            <div style="font-size:0.78rem; color:var(--text-muted);"><?= format_time($b['start_time']) ?></div>
                                        </td>
                                        <td><strong><?= (int)$b['quantity'] ?></strong> ticket(s)</td>
                                        <td class="td-price" style="font-weight:700;">
                                            <?= format_currency($b['total_amount']) ?>
                                        </td>
                                        <td><?= booking_status_badge($b['status']) ?></td>
                                        <td style="text-align:right;">
                                            <div class="table-actions" style="justify-content:flex-end;">
                                                <?php if ($b['status'] === 'confirmed'): ?>
                                                    <a href="<?= BASE_URL ?>/public/confirmation.php?ref=<?= e($b['booking_reference']) ?>" class="btn btn-outline-primary btn-sm">
                                                        Receipt & Passes
                                                    </a>
                                                <?php elseif ($b['status'] === 'pending_payment'): ?>
                                                    <a href="<?= BASE_URL ?>/public/payment.php?booking_id=<?= $b['id'] ?>" class="btn btn-primary btn-sm">
                                                        Pay Now
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($canCancel): ?>
                                                    <a href="<?= BASE_URL ?>/student/cancel-booking.php?id=<?= $b['id'] ?>" class="btn btn-outline-danger btn-sm" data-confirm="Are you sure you want to cancel this booking? This will release your seats.">
                                                        Cancel
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border:none;">
                        <?= render_svg_icon('ticket', 'empty-state-icon', 44) ?>
                        <h4 class="empty-state-title">No bookings found</h4>
                        <p class="empty-state-text">No records matching the selected status filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
