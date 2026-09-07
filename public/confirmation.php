<?php
/**
 * CampusEvent Hub — Booking Confirmation: Step 3 (confirmation.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

require_login();

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$bookingRef = trim($_GET['ref'] ?? '');

if (empty($bookingRef)) {
    header('Location: ' . BASE_URL . '/student/bookings.php');
    exit;
}

// Fetch Full Booking with Attendees, Payments & Event
$stmt = $pdo->prepare("
    SELECT b.*, 
           e.title as event_title, e.event_date, e.start_time, e.end_time, e.venue, e.venue_address,
           c.name as category_name,
           p.transaction_reference, p.payment_method, p.paid_at
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN event_categories c ON e.category_id = c.id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'successful'
    WHERE b.booking_reference = ? AND (b.user_id = ? OR ? = 'admin')
    LIMIT 1
");
$stmt->execute([$bookingRef, $currentUser['id'], $currentUser['role']]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('danger', 'Booking record not found or access denied.');
    header('Location: ' . BASE_URL . '/student/bookings.php');
    exit;
}

// Fetch Attendees
$attStmt = $pdo->prepare("SELECT * FROM booking_attendees WHERE booking_id = ? ORDER BY id ASC");
$attStmt->execute([$booking['id']]);
$attendees = $attStmt->fetchAll();

$pageTitle = 'Booking Confirmed — Ref: ' . e($booking['booking_reference']) . ' — ' . APP_NAME;
require_once APP_ROOT . '/includes/header.php';
?>

<main class="main-content" style="background:#f1f5f9; padding:36px 0 70px 0;">
    <div class="container" style="max-width:820px;">

        <!-- Success Banner -->
        <div class="card" style="border-top:5px solid #059669; box-shadow:var(--shadow-md); margin-bottom:28px;">
            <div class="card-body" style="padding:36px; text-align:center;">
                
                <div style="width:64px; height:64px; background:#ecfdf5; color:#059669; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px auto;">
                    <?= render_svg_icon('check-circle', '', 36) ?>
                </div>

                <span class="badge badge-success" style="font-size:0.82rem; padding:5px 12px; margin-bottom:10px;">
                    Registration Confirmed & Verified
                </span>

                <h1 style="font-size:1.85rem; font-weight:800; color:var(--text-primary); margin-bottom:6px;">
                    You are registered for <?= e($booking['event_title']) ?>!
                </h1>

                <p style="color:var(--text-secondary); max-width:540px; margin:0 auto 20px auto; font-size:0.95rem;">
                    A confirmation notice has been recorded. Your digital entry passes are ready for gate check-in at the venue.
                </p>

                <div style="display:inline-block; background:#f8fafc; border:1px solid #cbd5e1; border-radius:var(--radius-sm); padding:10px 20px; font-family:monospace; font-size:1.1rem; font-weight:700; color:var(--primary-900);">
                    Booking Ref: <?= e($booking['booking_reference']) ?>
                </div>

            </div>
        </div>

        <!-- Event & Pass Details Card -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <h3 class="card-title">Event Logistics</h3>
            </div>
            <div class="card-body" style="padding:24px;">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:20px;">
                    <div>
                        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Date & Time</div>
                        <div style="font-weight:600; color:var(--text-primary);">
                            <?= format_date($booking['event_date'], 'l, M d, Y') ?>
                        </div>
                        <div style="font-size:0.82rem; color:var(--text-secondary);">
                            <?= format_time($booking['start_time']) ?> – <?= format_time($booking['end_time']) ?>
                        </div>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Venue</div>
                        <div style="font-weight:600; color:var(--text-primary);">
                            <?= e($booking['venue']) ?>
                        </div>
                        <div style="font-size:0.82rem; color:var(--text-secondary);">
                            <?= e($booking['venue_address'] ?: 'Campus Grounds') ?>
                        </div>
                    </div>

                    <div>
                        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Payment Total</div>
                        <div style="font-weight:700; color:var(--text-primary); font-size:1.05rem;">
                            <?= format_currency($booking['total_amount']) ?>
                        </div>
                        <div style="font-size:0.82rem; color:var(--text-muted);">
                            <?= strtoupper(e($booking['payment_method'] ?? 'Free')) ?> · <?= payment_status_badge('successful') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Individual Attendee Digital Passes List -->
        <div class="card" style="margin-bottom:28px;">
            <div class="card-header">
                <h3 class="card-title">Attendee Digital Entry Passes (<?= count($attendees) ?>)</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Attendee Name</th>
                                <th>Email</th>
                                <th>Digital Pass Code</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendees as $idx => $att): ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td><strong><?= e($att['attendee_name']) ?></strong></td>
                                    <td><?= e($att['attendee_email']) ?></td>
                                    <td>
                                        <span class="badge badge-neutral" style="font-family:monospace; font-size:0.82rem; letter-spacing:0.05em;">
                                            <?= e($att['ticket_code']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="<?= BASE_URL ?>/public/ticket-view.php?code=<?= e($att['ticket_code']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <?= render_svg_icon('printer', '', 14) ?> View / Print Pass
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Navigation Actions -->
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-primary">
                <?= render_svg_icon('ticket', '', 18) ?> Go to My Student Dashboard
            </a>
            <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-outline-secondary">
                Browse More Events
            </a>
        </div>

    </div>
</main>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
