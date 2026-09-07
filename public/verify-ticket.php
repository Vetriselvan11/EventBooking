<?php
/**
 * CampusEvent Hub — Ticket Verification Terminal (verify-ticket.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$queryCode = strtoupper(trim($_GET['code'] ?? ''));
$ticket = null;
$error = null;
$success = null;

// Handle Check-In Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_in') {
    require_csrf_token();
    $ticketId = (int)$_POST['ticket_id'];

    try {
        $update = $pdo->prepare("UPDATE booking_attendees SET is_checked_in = 1, checked_in_at = NOW() WHERE id = ?");
        $update->execute([$ticketId]);

        $success = "Attendee successfully checked in!";
        log_audit(get_current_user_id(), 'ATTENDEE_CHECKIN', "Checked in ticket ID {$ticketId}");
    } catch (PDOException $e) {
        $error = "Check-in failed: " . $e->getMessage();
    }
}

// Search for Ticket Code
if (!empty($queryCode)) {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               b.booking_reference, b.status as booking_status,
               e.title as event_title, e.event_date, e.start_time, e.end_time, e.venue,
               c.name as category_name
        FROM booking_attendees a
        JOIN bookings b ON a.booking_id = b.id
        JOIN events e ON b.event_id = e.id
        JOIN event_categories c ON e.category_id = c.id
        WHERE a.ticket_code = ? LIMIT 1
    ");
    $stmt->execute([$queryCode]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        $error = "No active ticket pass found matching code: '{$queryCode}'.";
    }
}

$pageTitle = 'Entrance Ticket Verifier — ' . APP_NAME;
require_once APP_ROOT . '/includes/header.php';
?>

<main class="main-content" style="background:#f1f5f9; padding:36px 0 70px 0;">
    <div class="container" style="max-width:700px;">

        <div class="dashboard-page-header" style="justify-content:center; text-align:center; margin-bottom:28px;">
            <div class="dashboard-header-title">
                <h1>Entrance Gate Ticket Scanner</h1>
                <p>Verify attendee digital pass codes and confirm venue admissions.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($success) ?></div>
            </div>
        <?php endif; ?>

        <!-- Lookup Form Card -->
        <div class="card" style="box-shadow:var(--shadow-sm); margin-bottom:28px;">
            <div class="card-body" style="padding:24px;">
                <form method="GET" action="verify-ticket.php">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label class="form-label">Scan QR / Enter Ticket Code</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" name="code" class="form-control num-tabular" placeholder="e.g. TCK-8841-A1" value="<?= e($queryCode) ?>" style="text-transform:uppercase; font-size:1.1rem; font-weight:700;" required autofocus>
                            <button type="submit" class="btn btn-primary" style="padding:0 24px;">
                                <?= render_svg_icon('search', '', 18) ?> Verify
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Verification Result Card -->
        <?php if ($ticket): ?>
            <div class="card" style="border-top:5px solid <?= $ticket['is_checked_in'] ? '#d97706' : '#059669' ?>; box-shadow:var(--shadow-md);">
                <div class="card-header" style="background:#f8fafc;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span class="badge <?= $ticket['is_checked_in'] ? 'badge-warning' : 'badge-success' ?>" style="font-size:0.85rem; padding:4px 10px;">
                            <?= $ticket['is_checked_in'] ? 'ALREADY CHECKED IN' : 'VALID PASS — ADMIT' ?>
                        </span>
                        <span style="font-family:monospace; font-weight:700; color:var(--text-muted);"><?= e($ticket['ticket_code']) ?></span>
                    </div>
                </div>

                <div class="card-body" style="padding:28px;">
                    <div style="margin-bottom:20px;">
                        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Attendee</div>
                        <h2 style="font-size:1.4rem; font-weight:800; color:var(--primary-900); margin-bottom:2px;">
                            <?= e($ticket['attendee_name']) ?>
                        </h2>
                        <div style="font-size:0.88rem; color:var(--text-secondary);"><?= e($ticket['attendee_email']) ?></div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin-bottom:24px; padding-top:16px; border-top:1px solid var(--border-subtle);">
                        <div>
                            <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Event</div>
                            <div style="font-weight:700; color:var(--text-primary);"><?= e($ticket['event_title']) ?></div>
                            <div style="font-size:0.8rem; color:var(--text-secondary);"><?= format_date($ticket['event_date']) ?></div>
                        </div>

                        <div>
                            <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Venue</div>
                            <div style="font-weight:700; color:var(--text-primary);"><?= e($ticket['venue']) ?></div>
                            <div style="font-size:0.8rem; color:var(--text-secondary);"><?= format_time($ticket['start_time']) ?></div>
                        </div>
                    </div>

                    <!-- Action Check-in Button -->
                    <?php if (!$ticket['is_checked_in']): ?>
                        <form method="POST" action="verify-ticket.php?code=<?= urlencode($ticket['ticket_code']) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="check_in">
                            <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">

                            <button type="submit" class="btn btn-primary btn-lg btn-block" style="background:#059669; border-color:#059669; padding:12px;">
                                <?= render_svg_icon('check-circle', '', 20) ?> Confirm Entrance Check-In
                            </button>
                        </form>
                    <?php else: ?>
                        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:var(--radius-sm); padding:12px; text-align:center; font-size:0.85rem; color:#92400e;">
                            Attendee was admitted on <strong><?= format_datetime($ticket['checked_in_at']) ?></strong>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
