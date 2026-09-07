<?php
/**
 * CampusEvent Hub — Student Digital Passes & QR (student/tickets.php)
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

// Fetch all attendee tickets belonging to this student's confirmed bookings
$stmt = $pdo->prepare("
    SELECT a.*, 
           b.booking_reference, b.booked_at,
           e.id as event_id, e.title as event_title, e.event_date, e.start_time, e.venue,
           c.name as category_name
    FROM booking_attendees a
    JOIN bookings b ON a.booking_id = b.id
    JOIN events e ON b.event_id = e.id
    JOIN event_categories c ON e.category_id = c.id
    WHERE b.user_id = ? AND b.status = 'confirmed'
    ORDER BY e.event_date ASC, a.id ASC
");
$stmt->execute([$userId]);
$passes = $stmt->fetchAll();

$pageTitle = 'My Digital Passes & QR — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>My Digital Entry Passes</h1>
                <p>Present these official cryptographic passes and QR codes for instant admission at event gates.</p>
            </div>
            <div class="dashboard-header-actions">
                <span class="badge badge-neutral" style="padding:6px 12px; font-size:0.85rem;">
                    <strong><?= count($passes) ?></strong> Total Passes
                </span>
            </div>
        </div>

        <?php if (!empty($passes)): ?>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:24px;">
                <?php foreach ($passes as $p): 
                    $isPast = strtotime($p['event_date']) < strtotime(date('Y-m-d'));
                ?>
                    <div class="card" style="border-top:4px solid <?= $p['is_checked_in'] ? '#059669' : ($isPast ? '#64748b' : 'var(--primary)') ?>;">
                        <div class="card-header" style="background:#f8fafc;">
                            <span class="badge badge-secondary"><?= e($p['category_name']) ?></span>
                            <?php if ($p['is_checked_in']): ?>
                                <span class="badge badge-success">✓ Admitted</span>
                            <?php elseif ($isPast): ?>
                                <span class="badge badge-neutral">Event Concluded</span>
                            <?php else: ?>
                                <span class="badge badge-info">Valid Entry Pass</span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <h3 style="font-size:1.15rem; font-weight:700; margin-bottom:8px;">
                                <a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $p['event_id'] ?>"><?= e($p['event_title']) ?></a>
                            </h3>

                            <div style="background:#f1f5f9; border-radius:var(--radius-sm); padding:12px; margin-bottom:16px;">
                                <div style="font-size:0.72rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Attendee</div>
                                <div style="font-weight:700; color:var(--text-primary);"><?= e($p['attendee_name']) ?></div>
                                <div style="font-size:0.78rem; color:var(--text-secondary);"><?= e($p['attendee_email']) ?></div>
                            </div>

                            <ul class="event-meta-list" style="border:none; padding:0; margin-bottom:18px;">
                                <li class="event-meta-item">
                                    <?= render_svg_icon('calendar', '', 14) ?>
                                    <span><?= format_date($p['event_date']) ?> · <?= format_time($p['start_time']) ?></span>
                                </li>
                                <li class="event-meta-item">
                                    <?= render_svg_icon('location', '', 14) ?>
                                    <span><?= e($p['venue']) ?></span>
                                </li>
                                <li class="event-meta-item">
                                    <?= render_svg_icon('ticket', '', 14) ?>
                                    <span style="font-family:monospace; font-weight:700; color:var(--primary);"><?= e($p['ticket_code']) ?></span>
                                </li>
                            </ul>

                            <a href="<?= BASE_URL ?>/public/ticket-view.php?code=<?= e($p['ticket_code']) ?>" target="_blank" class="btn btn-primary btn-block btn-sm">
                                <?= render_svg_icon('printer', '', 16) ?> View / Print Digital Pass
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= render_svg_icon('shield-check', 'empty-state-icon', 48) ?>
                <h3 class="empty-state-title">No active passes issued</h3>
                <p class="empty-state-text">You have not registered for any upcoming events yet.</p>
                <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-primary btn-sm">Browse Event Directory</a>
            </div>
        <?php endif; ?>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
