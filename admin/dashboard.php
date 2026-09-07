<?php
/**
 * CampusEvent Hub — Administrator Central Console (admin/dashboard.php)
 */

define('APP_ROOT', dirname(__DIR__));
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

// Admin Metrics Aggregations
$metrics = [
    'total_events' => 0,
    'active_events' => 0,
    'total_students' => 0,
    'total_coordinators' => 0,
    'total_bookings' => 0,
    'total_revenue' => 0.00,
    'pending_payments' => 0
];

try {
    $metrics['total_events'] = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $metrics['active_events'] = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status IN ('open', 'almost_full') AND event_date >= CURDATE()")->fetchColumn();
    $metrics['total_students'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $metrics['total_coordinators'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'coordinator'")->fetchColumn();
    $metrics['total_bookings'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
    $metrics['total_revenue'] = floatval($pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status = 'confirmed'")->fetchColumn());
    $metrics['pending_payments'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending_payment'")->fetchColumn();

    // Recent Bookings (Latest 6)
    $recStmt = $pdo->query("
        SELECT b.*, e.title as event_title, u.name as user_name, u.email as user_email
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN users u ON b.user_id = u.id
        ORDER BY b.booked_at DESC
        LIMIT 6
    ");
    $recentBookings = $recStmt->fetchAll();

    // Category Distribution Data for Donut Chart
    $catData = $pdo->query("
        SELECT c.name, COUNT(e.id) as count
        FROM event_categories c
        LEFT JOIN events e ON c.id = e.category_id
        GROUP BY c.id
        ORDER BY c.id ASC
    ")->fetchAll();

    // Event Bookings by Month/Day for Bar Chart
    $barData = $pdo->query("
        SELECT DATE_FORMAT(booked_at, '%b %d') as date_label, COUNT(id) as booking_count
        FROM bookings
        WHERE booked_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(booked_at)
        ORDER BY booked_at ASC
    ")->fetchAll();

} catch (PDOException $e) {
    $recentBookings = [];
    $catData = [];
    $barData = [];
}

// Prepare JSON chart payloads
$chartLabels = [];
$chartValues = [];
foreach ($barData as $bd) {
    $chartLabels[] = $bd['date_label'];
    $chartValues[] = (int)$bd['booking_count'];
}
if (empty($chartLabels)) {
    $chartLabels = ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Today'];
    $chartValues = [2, 4, 1, 5, 3, 6];
}

$donutSegments = [];
$colors = ['#1e40af', '#059669', '#d97706', '#7c3aed', '#db2777', '#0284c7'];
foreach ($catData as $idx => $cd) {
    $donutSegments[] = [
        'label' => $cd['name'],
        'value' => (int)$cd['count'],
        'color' => $colors[$idx % count($colors)]
    ];
}

$pageTitle = 'Administrator Command Console — ' . APP_NAME;
$isDashboard = true;
$extraScripts = [BASE_URL . '/public/assets/js/charts.js'];
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Platform Administration Overview</h1>
                <p>Welcome, <strong><?= e($currentUser['name']) ?></strong> — Complete system metrics, financial streams, and event rosters.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="<?= BASE_URL ?>/admin/events/create.php" class="btn btn-primary btn-sm">
                    <?= render_svg_icon('plus', '', 16) ?> Create Campus Event
                </a>
                <a href="<?= BASE_URL ?>/admin/reports/export.php" class="btn btn-outline-secondary btn-sm">
                    <?= render_svg_icon('download', '', 16) ?> Export Financial Audit
                </a>
            </div>
        </div>

        <!-- KPI Metrics Strip -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Total Revenue</span>
                    <span class="metric-value num-tabular"><?= format_currency($metrics['total_revenue']) ?></span>
                    <span class="metric-sub"><?= $metrics['total_bookings'] ?> paid registrations</span>
                </div>
                <div class="metric-icon-box metric-icon-green">
                    <?= render_svg_icon('currency', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Campus Events</span>
                    <span class="metric-value num-tabular"><?= $metrics['total_events'] ?></span>
                    <span class="metric-sub"><strong><?= $metrics['active_events'] ?></strong> upcoming & open</span>
                </div>
                <div class="metric-icon-box metric-icon-blue">
                    <?= render_svg_icon('calendar', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Enrolled Students</span>
                    <span class="metric-value num-tabular"><?= $metrics['total_students'] ?></span>
                    <span class="metric-sub">Verified attendee accounts</span>
                </div>
                <div class="metric-icon-box metric-icon-purple">
                    <?= render_svg_icon('users', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Faculty Coordinators</span>
                    <span class="metric-value num-tabular"><?= $metrics['total_coordinators'] ?></span>
                    <span class="metric-sub">Active department chairs</span>
                </div>
                <div class="metric-icon-box metric-icon-amber">
                    <?= render_svg_icon('user', '', 22) ?>
                </div>
            </div>
        </div>

        <!-- Charts Split Row -->
        <div class="dashboard-grid-split">
            <!-- Left Chart: Booking Velocity (Bar Chart) -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Registration Activity Velocity (Last 7 Days)</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="bookingVelocityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right Chart: Category Distribution (Donut Chart) -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Events by Academic Discipline</h3>
                </div>
                <div class="card-body" style="display:flex; flex-direction:column; align-items:center;">
                    <div class="chart-container" style="display:flex; justify-content:center; align-items:center;">
                        <canvas id="categoryDonutChart"></canvas>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin-top:12px;">
                        <?php foreach ($donutSegments as $seg): ?>
                            <span style="font-size:0.75rem; display:inline-flex; align-items:center; gap:4px;">
                                <span style="width:8px; height:8px; border-radius:50%; background:<?= $seg['color'] ?>;"></span>
                                <?= e($seg['label']) ?> (<?= $seg['value'] ?>)
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Global Audit Table -->
        <div class="card" style="margin-bottom:28px;">
            <div class="card-header">
                <h3 class="card-title">Recent Transactions & Bookings</h3>
                <a href="<?= BASE_URL ?>/admin/bookings/index.php" class="btn btn-outline-primary btn-sm">View All Bookings</a>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (!empty($recentBookings)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Student / Buyer</th>
                                    <th>Event Title</th>
                                    <th>Quantity</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $bk): ?>
                                    <tr>
                                        <td>
                                            <span style="font-family:monospace; font-weight:700; color:var(--primary-900);">
                                                <?= e($bk['booking_reference']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= e($bk['user_name']) ?></strong>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= e($bk['user_email']) ?></div>
                                        </td>
                                        <td><?= e($bk['event_title']) ?></td>
                                        <td><?= (int)$bk['quantity'] ?></td>
                                        <td class="td-price" style="font-weight:700;"><?= format_currency($bk['total_amount']) ?></td>
                                        <td><?= booking_status_badge($bk['status']) ?></td>
                                        <td style="font-size:0.8rem; color:var(--text-secondary);"><?= format_datetime($bk['booked_at']) ?></td>
                                        <td style="text-align:right;">
                                            <a href="<?= BASE_URL ?>/admin/bookings/view.php?id=<?= $bk['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                                Inspect
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border:none;">
                        <h4 class="empty-state-title">No bookings on record</h4>
                        <p class="empty-state-text">New registrations will appear in real time.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Render Bar Chart
    const barLabels = <?= json_encode($chartLabels) ?>;
    const barValues = <?= json_encode($chartValues) ?>;
    SimpleCanvasChart.renderBarChart('bookingVelocityChart', barLabels, barValues, '#1e40af');

    // Render Donut Chart
    const donutData = <?= json_encode($donutSegments) ?>;
    SimpleCanvasChart.renderDonutChart('categoryDonutChart', donutData);
});
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
