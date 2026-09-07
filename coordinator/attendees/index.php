<?php
/**
 * CampusEvent Hub — Coordinator Attendee Check-In Desk (coordinator/attendees/index.php)
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

$currentUser = get_current_user_data();
$coordId = $currentUser['id'];

$eventId = (int)($_GET['event_id'] ?? 0);
$search = trim($_GET['q'] ?? '');
$checkinFilter = $_GET['checkin'] ?? 'all';

// Fetch Events assigned to this coordinator for the selector dropdown
$evtStmt = $pdo->prepare("SELECT id, title FROM events WHERE coordinator_id = ? OR ? = 'admin' ORDER BY event_date DESC");
$evtStmt->execute([$coordId, $currentUser['role']]);
$coordinatorEvents = $evtStmt->fetchAll();

if ($eventId === 0 && !empty($coordinatorEvents)) {
    $eventId = (int)$coordinatorEvents[0]['id'];
}

// Build Query for Attendees
$where = ["b.status = 'confirmed'"];
$params = [];

if ($eventId > 0) {
    $where[] = "b.event_id = ?";
    $params[] = $eventId;
}

if (!empty($search)) {
    $where[] = "(a.attendee_name LIKE ? OR a.attendee_email LIKE ? OR a.ticket_code LIKE ? OR b.booking_reference LIKE ?)";
    $t = "%{$search}%";
    $params[] = $t;
    $params[] = $t;
    $params[] = $t;
    $params[] = $t;
}

if ($checkinFilter === 'checked') {
    $where[] = "a.is_checked_in = 1";
} elseif ($checkinFilter === 'pending') {
    $where[] = "a.is_checked_in = 0";
}

$sql = "
    SELECT a.*, 
           b.booking_reference, b.booked_at,
           e.title as event_title, e.event_date, e.venue
    FROM booking_attendees a
    JOIN bookings b ON a.booking_id = b.id
    JOIN events e ON b.event_id = e.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY a.is_checked_in ASC, a.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendees = $stmt->fetchAll();

// Counts for the active event
$totalCount = count($attendees);
$checkedInCount = 0;
foreach ($attendees as $a) {
    if ($a['is_checked_in']) $checkedInCount++;
}

$pageTitle = 'Live Attendee Check-In Desk — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Live Attendee Check-In Desk</h1>
                <p>Verify gate admissions, mark attendee attendance, and search pass codes in real time.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="<?= BASE_URL ?>/public/verify-ticket.php" target="_blank" class="btn btn-outline-primary btn-sm">
                    <?= render_svg_icon('shield-check', '', 16) ?> Scanner Terminal
                </a>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <form method="GET" action="index.php" class="filter-toolbar">
            <div class="filter-group-left">
                <!-- Event Selector -->
                <div style="min-width:240px;">
                    <select name="event_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($coordinatorEvents as $ev): ?>
                            <option value="<?= $ev['id'] ?>" <?= ($eventId == $ev['id']) ? 'selected' : '' ?>>
                                <?= e($ev['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="search-input-wrapper">
                    <span class="search-input-icon"><?= render_svg_icon('search', '', 16) ?></span>
                    <input type="text" name="q" class="form-control search-input-field" placeholder="Search attendee name, email, or pass code..." value="<?= e($search) ?>">
                </div>

                <!-- Check-in Status Filter -->
                <div style="min-width:140px;">
                    <select name="checkin" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($checkinFilter === 'all') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="pending" <?= ($checkinFilter === 'pending') ? 'selected' : '' ?>>Not Checked In</option>
                        <option value="checked" <?= ($checkinFilter === 'checked') ? 'selected' : '' ?>>Admitted / Checked In</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:12px;">
                <span class="badge badge-success" style="font-size:0.85rem; padding:6px 12px;">
                    <strong><?= $checkedInCount ?></strong> / <?= $totalCount ?> Admitted
                </span>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </div>
        </form>

        <!-- Attendee Table -->
        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (!empty($attendees)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pass Code</th>
                                    <th>Attendee Name</th>
                                    <th>Attendee Email</th>
                                    <th>Booking Ref</th>
                                    <th>Admission Status</th>
                                    <th style="text-align:right;">Gate Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendees as $att): ?>
                                    <tr>
                                        <td>
                                            <span style="font-family:monospace; font-weight:700; color:var(--primary-900); font-size:0.92rem;">
                                                <?= e($att['ticket_code']) ?>
                                            </span>
                                        </td>
                                        <td><strong><?= e($att['attendee_name']) ?></strong></td>
                                        <td><?= e($att['attendee_email']) ?></td>
                                        <td>
                                            <span style="font-family:monospace; font-size:0.82rem; color:var(--text-muted);">
                                                <?= e($att['booking_reference']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($att['is_checked_in']): ?>
                                                <span class="badge badge-success">✓ Checked-In (<?= date('h:i A', strtotime($att['checked_in_at'])) ?>)</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Awaiting Gate Check-In</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <form method="POST" action="checkin.php" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="ticket_id" value="<?= $att['id'] ?>">
                                                <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                                <input type="hidden" name="status" value="<?= $att['is_checked_in'] ? '0' : '1' ?>">
                                                
                                                <?php if ($att['is_checked_in']): ?>
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Undo check-in">
                                                        Undo Check-In
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-primary btn-sm" style="background:#059669; border-color:#059669;">
                                                        <?= render_svg_icon('check-circle', '', 14) ?> Admit / Check In
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border:none;">
                        <?= render_svg_icon('users', 'empty-state-icon', 40) ?>
                        <h4 class="empty-state-title">No attendees found</h4>
                        <p class="empty-state-text">No confirmed attendees match the selected event or search filters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
