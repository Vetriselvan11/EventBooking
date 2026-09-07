<?php
/**
 * CampusEvent Hub — Administrator All Bookings (admin/bookings/index.php)
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

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(b.booking_reference LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR e.title LIKE ?)";
    $t = "%{$search}%";
    $params[] = $t;
    $params[] = $t;
    $params[] = $t;
    $params[] = $t;
}

if ($statusFilter !== 'all') {
    $where[] = "b.status = ?";
    $params[] = $statusFilter;
}

$sql = "
    SELECT b.*, 
           u.name as user_name, u.email as user_email,
           e.title as event_title, e.event_date,
           p.payment_method, p.transaction_reference
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN events e ON b.event_id = e.id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'successful'
    WHERE " . implode(" AND ", $where) . "
    ORDER BY b.booked_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle = 'All Bookings Audit — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Global Booking Records & Audit</h1>
                <p>Monitor all student ticket registrations, track pending payments, and review cancellations.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="<?= BASE_URL ?>/admin/reports/export.php?type=bookings" class="btn btn-outline-primary btn-sm">
                    <?= render_svg_icon('download', '', 16) ?> Export Bookings CSV
                </a>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <form method="GET" action="index.php" class="filter-toolbar">
            <div class="filter-group-left">
                <div class="search-input-wrapper">
                    <span class="search-input-icon"><?= render_svg_icon('search', '', 16) ?></span>
                    <input type="text" name="q" class="form-control search-input-field" placeholder="Search by Reference, Student Name, Email, or Event Title..." value="<?= e($search) ?>">
                </div>

                <div style="min-width:160px;">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($statusFilter === 'all') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="confirmed" <?= ($statusFilter === 'confirmed') ? 'selected' : '' ?>>Confirmed</option>
                        <option value="pending_payment" <?= ($statusFilter === 'pending_payment') ? 'selected' : '' ?>>Pending Payment</option>
                        <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                        <option value="refunded" <?= ($statusFilter === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                <?php if (!empty($search) || $statusFilter !== 'all'): ?>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (!empty($bookings)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Student</th>
                                    <th>Event Details</th>
                                    <th>Event Date</th>
                                    <th>Quantity</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Booked At</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $bk): ?>
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
                                        <td>
                                            <a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $bk['event_id'] ?>" target="_blank">
                                                <strong><?= e($bk['event_title']) ?></strong>
                                            </a>
                                        </td>
                                        <td><?= format_date($bk['event_date']) ?></td>
                                        <td><strong><?= (int)$bk['quantity'] ?></strong></td>
                                        <td class="td-price" style="font-weight:700;"><?= format_currency($bk['total_amount']) ?></td>
                                        <td><?= booking_status_badge($bk['status']) ?></td>
                                        <td style="font-size:0.8rem; color:var(--text-secondary);"><?= format_datetime($bk['booked_at']) ?></td>
                                        <td style="text-align:right;">
                                            <a href="view.php?id=<?= $bk['id'] ?>" class="btn btn-outline-secondary btn-sm">
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
                        <h4 class="empty-state-title">No bookings found</h4>
                        <p class="empty-state-text">No booking records match the current filter criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
