<?php
/**
 * CampusEvent Hub — Admin Reports & Executive Intelligence (admin/reports/index.php)
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

// 1. Revenue by Category
$catRevenue = $pdo->query("
    SELECT c.name, 
           COUNT(DISTINCT e.id) as total_events,
           COUNT(DISTINCT b.id) as total_bookings,
           COALESCE(SUM(b.total_amount), 0) as gross_revenue
    FROM event_categories c
    LEFT JOIN events e ON c.id = e.category_id
    LEFT JOIN bookings b ON e.id = b.event_id AND b.status = 'confirmed'
    GROUP BY c.id
    ORDER BY gross_revenue DESC
")->fetchAll();

// 2. High Capacity / Filling Fast Events (Saturation Alerts)
$capacityAlerts = $pdo->query("
    SELECT e.id, e.title, e.event_date, e.max_capacity, e.available_seats,
           c.name as category_name,
           (e.max_capacity - e.available_seats) as booked_seats,
           ROUND(((e.max_capacity - e.available_seats) / e.max_capacity) * 100) as fill_pct
    FROM events e
    JOIN event_categories c ON e.category_id = c.id
    WHERE e.status IN ('open', 'almost_full', 'full') AND e.event_date >= CURDATE()
    ORDER BY fill_pct DESC
    LIMIT 6
")->fetchAll();

// 3. Top Performing Events by Revenue
$topEvents = $pdo->query("
    SELECT e.id, e.title, e.event_date, e.ticket_price,
           COUNT(DISTINCT b.id) as total_bookings,
           COALESCE(SUM(b.total_amount), 0) as total_revenue
    FROM events e
    LEFT JOIN bookings b ON e.id = b.event_id AND b.status = 'confirmed'
    GROUP BY e.id
    ORDER BY total_revenue DESC
    LIMIT 5
")->fetchAll();

$pageTitle = 'Analytics & Executive Reports — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Platform Analytics & Executive Reports</h1>
                <p>Institutional attendance breakdown, discipline revenue shares, and downloadable CSV audits.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="export.php?type=financial" class="btn btn-primary btn-sm">
                    <?= render_svg_icon('download', '', 16) ?> Export Financial Summary
                </a>
            </div>
        </div>

        <!-- Quick Export Cards Grid -->
        <div class="card" style="margin-bottom:28px;">
            <div class="card-header">
                <h3 class="card-title">Direct Data Export Tools (CSV Format)</h3>
            </div>
            <div class="card-body">
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
                    <a href="export.php?type=bookings" class="card" style="padding:16px; text-decoration:none; border:1px solid var(--border-subtle); display:flex; align-items:center; gap:12px;">
                        <div class="metric-icon-box metric-icon-blue" style="width:36px; height:36px;">
                            <?= render_svg_icon('ticket', '', 18) ?>
                        </div>
                        <div>
                            <div style="font-weight:700; color:var(--text-primary);">All Bookings Manifest</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Download CSV &rarr;</div>
                        </div>
                    </a>

                    <a href="export.php?type=payments" class="card" style="padding:16px; text-decoration:none; border:1px solid var(--border-subtle); display:flex; align-items:center; gap:12px;">
                        <div class="metric-icon-box metric-icon-green" style="width:36px; height:36px;">
                            <?= render_svg_icon('currency', '', 18) ?>
                        </div>
                        <div>
                            <div style="font-weight:700; color:var(--text-primary);">Payment Transactions Log</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Download CSV &rarr;</div>
                        </div>
                    </a>

                    <a href="export.php?type=students" class="card" style="padding:16px; text-decoration:none; border:1px solid var(--border-subtle); display:flex; align-items:center; gap:12px;">
                        <div class="metric-icon-box metric-icon-purple" style="width:36px; height:36px;">
                            <?= render_svg_icon('users', '', 18) ?>
                        </div>
                        <div>
                            <div style="font-weight:700; color:var(--text-primary);">Student Registry</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Download CSV &rarr;</div>
                        </div>
                    </a>

                    <a href="export.php?type=events" class="card" style="padding:16px; text-decoration:none; border:1px solid var(--border-subtle); display:flex; align-items:center; gap:12px;">
                        <div class="metric-icon-box metric-icon-amber" style="width:36px; height:36px;">
                            <?= render_svg_icon('calendar', '', 18) ?>
                        </div>
                        <div>
                            <div style="font-weight:700; color:var(--text-primary);">Events Performance</div>
                            <div style="font-size:0.75rem; color:var(--text-muted);">Download CSV &rarr;</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Split Grid: Category Breakdown + Saturation Alerts -->
        <div class="dashboard-grid-split">
            
            <!-- Discipline Financial Share -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Discipline Revenue & Attendance Share</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Discipline</th>
                                    <th>Events</th>
                                    <th>Bookings</th>
                                    <th style="text-align:right;">Gross Volume</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($catRevenue as $cr): ?>
                                    <tr>
                                        <td><strong><?= e($cr['name']) ?></strong></td>
                                        <td><?= (int)$cr['total_events'] ?></td>
                                        <td><?= (int)$cr['total_bookings'] ?></td>
                                        <td class="td-price" style="text-align:right; font-weight:700;">
                                            <?= format_currency($cr['gross_revenue']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Capacity Saturation Alerts -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Capacity Saturation & Velocity</h3>
                </div>
                <div class="card-body" style="padding:20px;">
                    <?php if (!empty($capacityAlerts)): ?>
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <?php foreach ($capacityAlerts as $ca): ?>
                                <div>
                                    <div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:4px;">
                                        <span style="font-weight:700; color:var(--text-primary);"><?= e($ca['title']) ?></span>
                                        <span style="font-weight:700;"><?= (int)$ca['fill_pct'] ?>%</span>
                                    </div>
                                    <div class="capacity-progress">
                                        <div class="capacity-fill <?= $ca['fill_pct'] >= 90 ? 'fill-danger' : ($ca['fill_pct'] >= 70 ? 'fill-warning' : '') ?>" style="width:<?= (int)$ca['fill_pct'] ?>%;"></div>
                                    </div>
                                    <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                                        <?= (int)$ca['booked_seats'] ?> / <?= (int)$ca['max_capacity'] ?> seats filled · <?= format_date($ca['event_date']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="font-size:0.88rem; color:var(--text-muted); margin:0;">No active events to report.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
