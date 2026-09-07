<?php
/**
 * CampusEvent Hub — Coordinator Attendance Reports (coordinator/reports/index.php)
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

// Check if CSV export requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportEventId = (int)($_GET['event_id'] ?? 0);

    $sql = "
        SELECT a.ticket_code, a.attendee_name, a.attendee_email, 
               CASE WHEN a.is_checked_in = 1 THEN 'Checked In' ELSE 'Pending' END as checkin_status,
               a.checked_in_at,
               b.booking_reference, b.total_amount, b.booked_at,
               e.title as event_title, e.event_date
        FROM booking_attendees a
        JOIN bookings b ON a.booking_id = b.id
        JOIN events e ON b.event_id = e.id
        WHERE (e.coordinator_id = ? OR ? = 'admin')
    ";
    $params = [$coordId, $currentUser['role']];

    if ($exportEventId > 0) {
        $sql .= " AND e.id = ?";
        $params[] = $exportEventId;
    }

    $sql .= " ORDER BY e.event_date DESC, a.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="attendee_roster_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Ticket Code', 'Attendee Name', 'Attendee Email', 'Admission Status', 'Checked In At', 'Booking Ref', 'Event Title', 'Event Date']);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['ticket_code'],
            $r['attendee_name'],
            $r['attendee_email'],
            $r['checkin_status'],
            $r['checked_in_at'] ?? '—',
            $r['booking_reference'],
            $r['event_title'],
            $r['event_date']
        ]);
    }
    fclose($out);
    exit;
}

// Fetch Performance Breakdown by Event
$stmt = $pdo->prepare("
    SELECT e.id, e.title, e.event_date, e.ticket_price, e.max_capacity,
           c.name as category_name,
           (SELECT COUNT(a.id) FROM booking_attendees a JOIN bookings b ON a.booking_id = b.id WHERE b.event_id = e.id AND b.status = 'confirmed') as total_registered,
           (SELECT COUNT(a.id) FROM booking_attendees a JOIN bookings b ON a.booking_id = b.id WHERE b.event_id = e.id AND b.status = 'confirmed' AND a.is_checked_in = 1) as total_admitted,
           (SELECT COALESCE(SUM(b.total_amount), 0) FROM bookings b WHERE b.event_id = e.id AND b.status = 'confirmed') as event_revenue
    FROM events e
    JOIN event_categories c ON e.category_id = c.id
    WHERE e.coordinator_id = ? OR ? = 'admin'
    ORDER BY e.event_date DESC
");
$stmt->execute([$coordId, $currentUser['role']]);
$reportRows = $stmt->fetchAll();

$pageTitle = 'Attendance Reports & Analytics — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Department Attendance Reports</h1>
                <p>Track show-up rates, admission stats, and export attendee manifests to CSV.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="index.php?export=csv" class="btn btn-primary btn-sm">
                    <?= render_svg_icon('download', '', 16) ?> Export All Rosters (CSV)
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Event Performance & Attendance Ratios</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <?php if (!empty($reportRows)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Event Date</th>
                                    <th>Registered</th>
                                    <th>Admitted (Show Rate)</th>
                                    <th>Revenue</th>
                                    <th style="text-align:right;">Export Manifest</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportRows as $row): 
                                    $reg = (int)$row['total_registered'];
                                    $adm = (int)$row['total_admitted'];
                                    $rate = ($reg > 0) ? round(($adm / $reg) * 100) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($row['title']) ?></strong>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= e($row['category_name']) ?></div>
                                        </td>
                                        <td><?= format_date($row['event_date']) ?></td>
                                        <td><strong><?= $reg ?></strong> / <?= (int)$row['max_capacity'] ?></td>
                                        <td>
                                            <div style="font-weight:700; color:var(--primary);">
                                                <?= $adm ?> (<?= $rate ?>%)
                                            </div>
                                            <div class="capacity-progress" style="height:4px; margin-top:4px; width:120px;">
                                                <div class="capacity-fill" style="background:#10b981; width:<?= $rate ?>%;"></div>
                                            </div>
                                        </td>
                                        <td class="td-price" style="font-weight:700;">
                                            <?= format_currency($row['event_revenue']) ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <a href="index.php?export=csv&event_id=<?= $row['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                                <?= render_svg_icon('download', '', 14) ?> CSV Roster
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border:none;">
                        <h4 class="empty-state-title">No report data available</h4>
                        <p class="empty-state-text">Publish events to start viewing attendance reports.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
