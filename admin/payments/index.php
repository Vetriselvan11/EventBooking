<?php
/**
 * CampusEvent Hub — Admin Payment Transactions Audit (admin/payments/index.php)
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
$methodFilter = $_GET['method'] ?? 'all';

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.transaction_reference LIKE ? OR b.booking_reference LIKE ? OR u.name LIKE ?)";
    $t = "%{$search}%";
    $params[] = $t;
    $params[] = $t;
    $params[] = $t;
}

if ($statusFilter !== 'all') {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

if ($methodFilter !== 'all') {
    $where[] = "p.payment_method = ?";
    $params[] = $methodFilter;
}

$sql = "
    SELECT p.*, 
           b.booking_reference, b.event_id,
           u.name as user_name, u.email as user_email,
           e.title as event_title
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    JOIN events e ON b.event_id = e.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY p.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Aggregation calculations
$totalVolume = 0;
$successfulCount = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'successful') {
        $totalVolume += floatval($p['amount']);
        $successfulCount++;
    }
}

$pageTitle = 'Payment Audit Trail — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Payment Audit Log & Settlements</h1>
                <p>Complete transaction audit logs across simulated and processed payment rails.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="<?= BASE_URL ?>/admin/reports/export.php?type=payments" class="btn btn-outline-primary btn-sm">
                    <?= render_svg_icon('download', '', 16) ?> Export Financial CSV
                </a>
            </div>
        </div>

        <!-- Metric Summary Strip -->
        <div class="metrics-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom:24px;">
            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Settled Transaction Volume</span>
                    <span class="metric-value num-tabular"><?= format_currency($totalVolume) ?></span>
                    <span class="metric-sub"><?= $successfulCount ?> settled transactions</span>
                </div>
                <div class="metric-icon-box metric-icon-green">
                    <?= render_svg_icon('currency', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Total Payment Records</span>
                    <span class="metric-value num-tabular"><?= count($payments) ?></span>
                    <span class="metric-sub">Including refunds & declines</span>
                </div>
                <div class="metric-icon-box metric-icon-blue">
                    <?= render_svg_icon('chart', '', 22) ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-info">
                    <span class="metric-label">Payment Security Rail</span>
                    <span class="metric-value" style="font-size:1.35rem;">Institutional</span>
                    <span class="metric-sub">PCI-DSS Mock Simulation</span>
                </div>
                <div class="metric-icon-box metric-icon-purple">
                    <?= render_svg_icon('shield-check', '', 22) ?>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <form method="GET" action="index.php" class="filter-toolbar">
            <div class="filter-group-left">
                <div class="search-input-wrapper">
                    <span class="search-input-icon"><?= render_svg_icon('search', '', 16) ?></span>
                    <input type="text" name="q" class="form-control search-input-field" placeholder="Search by Txn Ref, Booking Ref, or Student..." value="<?= e($search) ?>">
                </div>

                <div style="min-width:150px;">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($statusFilter === 'all') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="successful" <?= ($statusFilter === 'successful') ? 'selected' : '' ?>>Paid (Successful)</option>
                        <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="failed" <?= ($statusFilter === 'failed') ? 'selected' : '' ?>>Failed / Declined</option>
                        <option value="refunded" <?= ($statusFilter === 'refunded') ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>

                <div style="min-width:150px;">
                    <select name="method" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($methodFilter === 'all') ? 'selected' : '' ?>>All Methods</option>
                        <option value="card" <?= ($methodFilter === 'card') ? 'selected' : '' ?>>Credit/Debit Card</option>
                        <option value="upi" <?= ($methodFilter === 'upi') ? 'selected' : '' ?>>UPI FastPay</option>
                        <option value="netbanking" <?= ($methodFilter === 'netbanking') ? 'selected' : '' ?>>Net Banking</option>
                        <option value="free" <?= ($methodFilter === 'free') ? 'selected' : '' ?>>Zero-Cost Free Pass</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if (!empty($search) || $statusFilter !== 'all' || $methodFilter !== 'all'): ?>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (!empty($payments)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Transaction Ref</th>
                                    <th>Booking Ref</th>
                                    <th>Student</th>
                                    <th>Event</th>
                                    <th>Channel / Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Paid Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $pay): ?>
                                    <tr>
                                        <td><code style="font-weight:700; color:var(--primary-900);"><?= e($pay['transaction_reference']) ?></code></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/bookings/view.php?id=<?= $pay['booking_id'] ?>">
                                                <code><?= e($pay['booking_reference']) ?></code>
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?= e($pay['user_name']) ?></strong>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= e($pay['user_email']) ?></div>
                                        </td>
                                        <td><?= e($pay['event_title']) ?></td>
                                        <td><span class="badge badge-secondary"><?= strtoupper(e($pay['payment_method'])) ?></span></td>
                                        <td class="td-price" style="font-weight:700;"><?= format_currency($pay['amount']) ?></td>
                                        <td><?= payment_status_badge($pay['status']) ?></td>
                                        <td style="font-size:0.8rem; color:var(--text-secondary);"><?= format_datetime($pay['paid_at'] ?? $pay['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border:none;">
                        <h4 class="empty-state-title">No transactions recorded</h4>
                        <p class="empty-state-text">No payment records matching the selected parameters.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
