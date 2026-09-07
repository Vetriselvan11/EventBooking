<?php
/**
 * CampusEvent Hub — Student Dashboard (student/dashboard.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

// Enforce student role
require_role('student');

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$userId = $currentUser['id'];

// Fetch Student Metrics
$metrics = [
    'upcoming_passes' => 0,
    'total_bookings' => 0,
    'total_spent' => 0.00
];

try {
    // Upcoming Passes
    $passStmt = $pdo->prepare("
        SELECT COUNT(a.id)
        FROM booking_attendees a
        JOIN bookings b ON a.booking_id = b.id
        JOIN events e ON b.event_id = e.id
        WHERE b.user_id = ? AND b.status = 'confirmed' AND e.event_date >= CURDATE()
    ");
    $passStmt->execute([$userId]);
    $metrics['upcoming_passes'] = (int)$passStmt->fetchColumn();

    // Total Bookings
    $bookStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
    $bookStmt->execute([$userId]);
    $metrics['total_bookings'] = (int)$bookStmt->fetchColumn();

    // Total Spent
    $spentStmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE user_id = ? AND status = 'confirmed'");
    $spentStmt->execute([$userId]);
    $metrics['total_spent'] = floatval($spentStmt->fetchColumn());

    // Fetch Active Upcoming Registered Events
    $upcomingStmt = $pdo->prepare("
        SELECT b.id as booking_id, b.booking_reference, b.quantity, b.total_amount, b.booked_at,
               e.id as event_id, e.title as event_title, e.event_date, e.start_time, e.venue,
               c.name as category_name
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN event_categories c ON e.category_id = c.id
        WHERE b.user_id = ? AND b.status = 'confirmed' AND e.event_date >= CURDATE()
        ORDER BY e.event_date ASC
        LIMIT 4
    ");
    $upcomingStmt->execute([$userId]);
    $upcomingEvents = $upcomingStmt->fetchAll();

    // Fetch Notifications
    $notifStmt = $pdo->prepare("
        SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5
    ");
    $notifStmt->execute([$userId]);
    $notifications = $notifStmt->fetchAll();

} catch (PDOException $e) {
    $upcomingEvents = [];
    $notifications = [];
}

$pageTitle = 'Student Portal Dashboard — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        
        <!-- Header -->
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Student Portal Dashboard</h1>
                <p>Welcome back, <strong><?= e($currentUser['name']) ?></strong> (<?= e($currentUser['student_id'] ?? 'Student') ?> · <?= e($currentUser['department'] ?? 'Campus') ?>)</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-primary btn-sm">
                    <?= render_svg_icon('search', '', 16) ?> Explore Events
                </a>
            </div>
        </div>

        <!-- Metrics KPI Cards -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Active Upcoming Passes</span>
                    <span class="metric-value num-tabular"><?= $metrics['upcoming_passes'] ?></span>
                    <span class="metric-sub">Registered events pending</span>
                </div>
                <div class="metric-icon-box metric-icon-blue">
                    <?= render_svg_icon('ticket', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Total Bookings</span>
                    <span class="metric-value num-tabular"><?= $metrics['total_bookings'] ?></span>
                    <span class="metric-sub">Lifetime campus registrations</span>
                </div>
                <div class="metric-icon-box metric-icon-green">
                    <?= render_svg_icon('calendar', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Total Investment</span>
                    <span class="metric-value num-tabular"><?= format_currency($metrics['total_spent']) ?></span>
                    <span class="metric-sub">Passes & registration fees</span>
                </div>
                <div class="metric-icon-box metric-icon-amber">
                    <?= render_svg_icon('currency', '', 22) ?>
                </div>
            </div>
        </div>

        <!-- Main Workspace Split -->
        <div class="dashboard-grid-split">
            
            <!-- Left: Upcoming Registered Events -->
            <div>
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-header">
                        <h3 class="card-title">My Upcoming Events & Passes</h3>
                        <a href="<?= BASE_URL ?>/student/tickets.php" class="btn btn-outline-primary btn-sm">All Digital Passes</a>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (!empty($upcomingEvents)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Date & Time</th>
                                            <th>Venue</th>
                                            <th>Passes</th>
                                            <th style="text-align:right;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingEvents as $evt): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight:700; color:var(--text-primary);">
                                                        <a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $evt['event_id'] ?>">
                                                            <?= e($evt['event_title']) ?>
                                                        </a>
                                                    </div>
                                                    <div style="font-size:0.75rem; color:var(--text-muted); font-family:monospace;">
                                                        Ref: <?= e($evt['booking_reference']) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-weight:600;"><?= format_date($evt['event_date']) ?></div>
                                                    <div style="font-size:0.78rem; color:var(--text-muted);"><?= format_time($evt['start_time']) ?></div>
                                                </td>
                                                <td><?= e($evt['venue']) ?></td>
                                                <td>
                                                    <span class="badge badge-success"><?= (int)$evt['quantity'] ?> Pass(es)</span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <a href="<?= BASE_URL ?>/student/tickets.php" class="btn btn-outline-primary btn-sm">
                                                        <?= render_svg_icon('ticket', '', 14) ?> View Passes
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state" style="border:none;">
                                <?= render_svg_icon('calendar', 'empty-state-icon', 40) ?>
                                <h4 class="empty-state-title">No upcoming event registrations</h4>
                                <p class="empty-state-text">You have not booked passes for any upcoming campus events.</p>
                                <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-primary btn-sm">Browse Event Catalog</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Notifications & Quick Actions -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activity & Notices</h3>
                    </div>
                    <div class="card-body" style="padding:16px 20px;">
                        <?php if (!empty($notifications)): ?>
                            <div style="display:flex; flex-direction:column; gap:12px;">
                                <?php foreach ($notifications as $notif): ?>
                                    <div style="border-bottom:1px solid var(--border-subtle); padding-bottom:10px;">
                                        <div style="font-weight:700; font-size:0.86rem; color:var(--text-primary); margin-bottom:2px;">
                                            <?= e($notif['title']) ?>
                                        </div>
                                        <div style="font-size:0.8rem; color:var(--text-secondary); line-height:1.4; margin-bottom:4px;">
                                            <?= e($notif['message']) ?>
                                        </div>
                                        <div style="font-size:0.72rem; color:var(--text-muted);">
                                            <?= format_datetime($notif['created_at']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="font-size:0.85rem; color:var(--text-muted); margin:0;">No new notifications.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
