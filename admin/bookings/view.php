<?php
/**
 * CampusEvent Hub — Admin Inspect Booking (admin/bookings/view.php)
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
$bookingId = (int)($_GET['id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: index.php');
    exit;
}

// Handle Manual Status Override by Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    require_csrf_token();
    $newStatus = $_POST['status'] ?? 'confirmed';

    try {
        $update = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$newStatus, $bookingId]);

        log_audit($currentUser['id'], 'ADMIN_BOOKING_STATUS_OVERRIDE', "Changed booking #{$bookingId} status to {$newStatus}");
        setFlash('success', "Booking status updated to '{$newStatus}'.");
    } catch (PDOException $e) {
        setFlash('danger', 'Update failed: ' . $e->getMessage());
    }
}

// Fetch Full Booking Details
$stmt = $pdo->prepare("
    SELECT b.*, 
           u.name as user_name, u.email as user_email, u.phone as user_phone,
           e.title as event_title, e.event_date, e.start_time, e.end_time, e.venue, e.venue_address,
           c.name as category_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN events e ON b.event_id = e.id
    JOIN event_categories c ON e.category_id = c.id
    WHERE b.id = ? LIMIT 1
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('danger', 'Booking record not found.');
    header('Location: index.php');
    exit;
}

// Fetch Attendees / Passes
$attStmt = $pdo->prepare("SELECT * FROM booking_attendees WHERE booking_id = ? ORDER BY id ASC");
$attStmt->execute([$bookingId]);
$attendees = $attStmt->fetchAll();

// Fetch Payment History
$payStmt = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC");
$payStmt->execute([$bookingId]);
$payments = $payStmt->fetchAll();

$pageTitle = 'Inspect Booking Ref: ' . e($booking['booking_reference']) . ' — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Inspect Booking: <code><?= e($booking['booking_reference']) ?></code></h1>
                <p>Detailed transaction log, attendee credentials, and manual status overrides.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Back to All Bookings</a>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1.8fr 1fr; gap:28px; align-items:start;">
            
            <!-- Left: Logistics & Attendee Passes -->
            <div>
                <!-- Booking Summary Card -->
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-header">
                        <h3 class="card-title">Registration Summary</h3>
                        <div><?= booking_status_badge($booking['status']) ?></div>
                    </div>
                    <div class="card-body">
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                            <div>
                                <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Purchaser / Student</div>
                                <div style="font-weight:700; color:var(--text-primary);"><?= e($booking['user_name']) ?></div>
                                <div style="font-size:0.82rem; color:var(--text-secondary);"><?= e($booking['user_email']) ?></div>
                                <?php if (!empty($booking['user_phone'])): ?>
                                    <div style="font-size:0.8rem; color:var(--text-muted);"><?= e($booking['user_phone']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Event Details</div>
                                <div style="font-weight:700; color:var(--text-primary);">
                                    <a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $booking['event_id'] ?>" target="_blank"><?= e($booking['event_title']) ?></a>
                                </div>
                                <div style="font-size:0.82rem; color:var(--text-secondary);"><?= format_date($booking['event_date']) ?> · <?= e($booking['venue']) ?></div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:16px; border-top:1px solid var(--border-subtle); padding-top:16px;">
                            <div>
                                <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Pass Quantity</div>
                                <div style="font-weight:700; font-size:1.1rem;"><?= (int)$booking['quantity'] ?> Tickets</div>
                            </div>
                            <div>
                                <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Unit Price</div>
                                <div style="font-weight:700; font-size:1.1rem;"><?= format_currency($booking['unit_price']) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Total Amount</div>
                                <div style="font-weight:800; font-size:1.2rem; color:var(--primary-900);"><?= format_currency($booking['total_amount']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendee Passes Table -->
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-header">
                        <h3 class="card-title">Attendee Passes (<?= count($attendees) ?>)</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Pass Code</th>
                                        <th>Attendee Name</th>
                                        <th>Attendee Email</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendees as $att): ?>
                                        <tr>
                                            <td><code style="font-weight:700; color:var(--primary);"><?= e($att['ticket_code']) ?></code></td>
                                            <td><strong><?= e($att['attendee_name']) ?></strong></td>
                                            <td><?= e($att['attendee_email']) ?></td>
                                            <td>
                                                <?php if ($att['is_checked_in']): ?>
                                                    <span class="badge badge-success">✓ Admitted</span>
                                                <?php else: ?>
                                                    <span class="badge badge-neutral">Unused</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align:right;">
                                                <a href="<?= BASE_URL ?>/public/ticket-view.php?code=<?= e($att['ticket_code']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                                                    Pass Voucher
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment Audit Log -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Payment Transactions Log</h3>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (!empty($payments)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Transaction Ref</th>
                                            <th>Method</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Paid At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $pay): ?>
                                            <tr>
                                                <td><code><?= e($pay['transaction_reference']) ?></code></td>
                                                <td><span class="badge badge-secondary"><?= strtoupper(e($pay['payment_method'])) ?></span></td>
                                                <td class="td-price" style="font-weight:700;"><?= format_currency($pay['amount']) ?></td>
                                                <td><?= payment_status_badge($pay['status']) ?></td>
                                                <td><?= format_datetime($pay['paid_at'] ?? $pay['created_at']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p style="padding:20px; font-size:0.88rem; color:var(--text-muted); margin:0;">No payment transactions recorded.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Admin Status Override Card -->
            <div>
                <div class="card" style="border-top:4px solid var(--primary);">
                    <div class="card-header">
                        <h3 class="card-title">Administrative Actions</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="view.php?id=<?= $booking['id'] ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_status">

                            <div class="form-group">
                                <label class="form-label">Override Booking Status</label>
                                <select name="status" class="form-select">
                                    <option value="confirmed" <?= ($booking['status'] === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="pending_payment" <?= ($booking['status'] === 'pending_payment') ? 'selected' : '' ?>>Pending Payment</option>
                                    <option value="cancelled" <?= ($booking['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="refunded" <?= ($booking['status'] === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block" style="margin-top:10px;">
                                Update Status
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
