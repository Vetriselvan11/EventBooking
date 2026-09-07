<?php
/**
 * CampusEvent Hub — Faculty / Coordinator Dashboard (coordinator/dashboard.php)
 */

define('APP_ROOT', dirname(__DIR__));
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

// Fetch Coordinator Metrics
$metrics = [
    'assigned_events' => 0,
    'total_attendees' => 0,
    'total_checked_in' => 0,
    'total_revenue' => 0.00
];

try {
    // Assigned Events
    $evtStmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE coordinator_id = ?");
    $evtStmt->execute([$coordId]);
    $metrics['assigned_events'] = (int)$evtStmt->fetchColumn();

    // Total Attendees & Checked In
    $attStmt = $pdo->prepare("
        SELECT COUNT(a.id) as total_attendees,
               COALESCE(SUM(CASE WHEN a.is_checked_in = 1 THEN 1 ELSE 0 END), 0) as total_checked_in
        FROM booking_attendees a
        JOIN bookings b ON a.booking_id = b.id
        JOIN events e ON b.event_id = e.id
        WHERE e.coordinator_id = ? AND b.status = 'confirmed'
    ");
    $attStmt->execute([$coordId]);
    $attData = $attStmt->fetch();
    if ($attData) {
        $metrics['total_attendees'] = (int)$attData['total_attendees'];
        $metrics['total_checked_in'] = (int)$attData['total_checked_in'];
    }

    // Total Revenue
    $revStmt = $pdo->prepare("
        SELECT COALESCE(SUM(b.total_amount), 0)
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        WHERE e.coordinator_id = ? AND b.status = 'confirmed'
    ");
    $revStmt->execute([$coordId]);
    $metrics['total_revenue'] = floatval($revStmt->fetchColumn());

    // Fetch Assigned Events with Capacity Metrics
    $eventsStmt = $pdo->prepare("
        SELECT e.*, c.name as category_name,
               (SELECT COUNT(a.id) FROM booking_attendees a JOIN bookings b ON a.booking_id = b.id WHERE b.event_id = e.id AND b.status = 'confirmed') as booked_attendees,
               (SELECT COUNT(a.id) FROM booking_attendees a JOIN bookings b ON a.booking_id = b.id WHERE b.event_id = e.id AND b.status = 'confirmed' AND a.is_checked_in = 1) as checked_in_count
        FROM events e
        JOIN event_categories c ON e.category_id = c.id
        WHERE e.coordinator_id = ?
        ORDER BY e.event_date ASC
    ");
    $eventsStmt->execute([$coordId]);
    $assignedEvents = $eventsStmt->fetchAll();

} catch (PDOException $e) {
    $assignedEvents = [];
}

$pageTitle = 'Faculty Coordinator Console — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Coordinator Event Console</h1>
                <p>Welcome, <strong><?= e($currentUser['name']) ?></strong> (<?= e($currentUser['designation'] ?? 'Coordinator') ?> · <?= e($currentUser['department'] ?? 'Department') ?>)</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="<?= BASE_URL ?>/coordinator/events/create.php" class="btn btn-primary btn-sm">
                    <?= render_svg_icon('plus', '', 16) ?> Create Department Event
                </a>
                <a href="<?= BASE_URL ?>/coordinator/attendees/index.php" class="btn btn-outline-secondary btn-sm">
                    <?= render_svg_icon('check-circle', '', 16) ?> Open Check-In Desk
                </a>
            </div>
        </div>

        <!-- Metrics KPI Cards -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Assigned Events</span>
                    <span class="metric-value num-tabular"><?= $metrics['assigned_events'] ?></span>
                    <span class="metric-sub">Active department programs</span>
                </div>
                <div class="metric-icon-box metric-icon-blue">
                    <?= render_svg_icon('calendar', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Registered Attendees</span>
                    <span class="metric-value num-tabular"><?= $metrics['total_attendees'] ?></span>
                    <span class="metric-sub">Confirmed registrations</span>
                </div>
                <div class="metric-icon-box metric-icon-green">
                    <?= render_svg_icon('users', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Admitted / Checked In</span>
                    <span class="metric-value num-tabular"><?= $metrics['total_checked_in'] ?></span>
                    <span class="metric-sub">Gate verified attendees</span>
                </div>
                <div class="metric-icon-box metric-icon-purple">
                    <?= render_svg_icon('shield-check', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Total Revenue</span>
                    <span class="metric-value num-tabular"><?= format_currency($metrics['total_revenue']) ?></span>
                    <span class="metric-sub">Gross pass revenue</span>
                </div>
                <div class="metric-icon-box metric-icon-amber">
                    <?= render_svg_icon('currency', '', 22) ?>
                </div>
            </div>
        </div>

        <!-- Assigned Events Management Card -->
        <div class="card" style="margin-bottom:28px;">
            <div class="card-header">
                <h3 class="card-title">My Department Events & Real-Time Rosters</h3>
                <a href="<?= BASE_URL ?>/coordinator/events/create.php" class="btn btn-outline-primary btn-sm">Add New Event</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (!empty($assignedEvents)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Schedule</th>
                                    <th>Capacity & Registration</th>
                                    <th>Check-In Progress</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignedEvents as $ev): 
                                    $booked = (int)$ev['booked_attendees'];
                                    $max = (int)$ev['max_capacity'];
                                    $capPct = ($max > 0) ? round(($booked / $max) * 100) : 0;
                                    $checkedIn = (int)$ev['checked_in_count'];
                                    $checkInPct = ($booked > 0) ? round(($checkedIn / $booked) * 100) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--text-primary);">
                                                <a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $ev['id'] ?>" target="_blank">
                                                    <?= e($ev['title']) ?>
                                                </a>
                                            </div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= e($ev['category_name']) ?> · <?= format_currency($ev['ticket_price']) ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;"><?= format_date($ev['event_date']) ?></div>
                                            <div style="font-size:0.78rem; color:var(--text-muted);"><?= format_time($ev['start_time']) ?></div>
                                        </td>
                                        <td style="min-width:180px;">
                                            <div class="capacity-labels" style="margin-bottom:4px;">
                                                <span><strong><?= $booked ?></strong> / <?= $max ?> seats</span>
                                                <span><?= $capPct ?>%</span>
                                            </div>
                                            <div class="capacity-progress">
                                                <div class="capacity-fill <?= $capPct >= 90 ? 'fill-danger' : ($capPct >= 70 ? 'fill-warning' : '') ?>" style="width:<?= $capPct ?>%;"></div>
                                            </div>
                                        </td>
                                        <td style="min-width:160px;">
                                            <div style="font-size:0.85rem; font-weight:700; color:var(--primary);">
                                                <?= $checkedIn ?> / <?= $booked ?> Admitted (<?= $checkInPct ?>%)
                                            </div>
                                            <div class="capacity-progress" style="height:4px; margin-top:4px;">
                                                <div class="capacity-fill" style="background:#10b981; width:<?= $checkInPct ?>%;"></div>
                                            </div>
                                        </td>
                                        <td><?= event_status_badge($ev['status']) ?></td>
                                        <td style="text-align:right;">
                                            <div class="table-actions" style="justify-content:flex-end;">
                                                <a href="<?= BASE_URL ?>/coordinator/attendees/index.php?event_id=<?= $ev['id'] ?>" class="btn btn-outline-primary btn-sm" title="View Attendee List">
                                                    <?= render_svg_icon('users', '', 14) ?> Roster
                                                </a>
                                                <a href="<?= BASE_URL ?>/coordinator/events/edit.php?id=<?= $ev['id'] ?>" class="btn btn-outline-secondary btn-sm" title="Edit Event">
                                                    Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border:none;">
                        <?= render_svg_icon('calendar', 'empty-state-icon', 40) ?>
                        <h4 class="empty-state-title">No events assigned yet</h4>
                        <p class="empty-state-text">Create your department's first event to start accepting registrations.</p>
                        <a href="<?= BASE_URL ?>/coordinator/events/create.php" class="btn btn-primary btn-sm">Create New Event</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
