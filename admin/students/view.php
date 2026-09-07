<?php
/**
 * CampusEvent Hub — Admin View Student Profile (admin/students/view.php)
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

$studentUserId = (int)($_GET['id'] ?? 0);
if ($studentUserId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch Student Profile
$stmt = $pdo->prepare("
    SELECT u.*, s.student_id_number, s.department, s.year_of_study, s.emergency_contact
    FROM users u
    JOIN students s ON u.id = s.user_id
    WHERE u.id = ? AND u.role = 'student'
    LIMIT 1
");
$stmt->execute([$studentUserId]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('danger', 'Student not found.');
    header('Location: index.php');
    exit;
}

// Fetch all bookings made by this student
$bookStmt = $pdo->prepare("
    SELECT b.*, e.title as event_title, e.event_date, e.venue,
           p.payment_method, p.transaction_reference
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    LEFT JOIN payments p ON b.id = p.booking_id AND p.status = 'successful'
    WHERE b.user_id = ?
    ORDER BY b.booked_at DESC
");
$bookStmt->execute([$studentUserId]);
$bookings = $bookStmt->fetchAll();

$pageTitle = 'Student Profile: ' . e($student['name']) . ' — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Student Profile Dossier</h1>
                <p>Enrolled Student Information & Lifetime Booking History</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Directory</a>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 2fr; gap:28px; align-items:start;">
            
            <!-- Left: Profile Info Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profile Details</h3>
                </div>
                <div class="card-body">
                    <div style="text-align:center; margin-bottom:20px;">
                        <div class="user-avatar-badge" style="width:64px; height:64px; font-size:1.6rem; margin:0 auto 10px auto;">
                            <?= strtoupper(substr($student['name'], 0, 1)) ?>
                        </div>
                        <h2 style="font-size:1.3rem; margin-bottom:2px;"><?= e($student['name']) ?></h2>
                        <div style="font-size:0.85rem; color:var(--text-muted);"><?= e($student['email']) ?></div>
                        <div style="margin-top:8px;">
                            <span class="badge <?= ($student['status'] === 'active') ? 'badge-success' : 'badge-danger' ?>">
                                <?= ucfirst(e($student['status'])) ?> Account
                            </span>
                        </div>
                    </div>

                    <div style="border-top:1px solid var(--border-subtle); padding-top:16px;">
                        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Student ID Number</div>
                        <div style="font-weight:700; font-family:monospace; font-size:1.05rem; color:var(--primary-900); margin-bottom:12px;">
                            <?= e($student['student_id_number']) ?>
                        </div>

                        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Department</div>
                        <div style="font-weight:600; margin-bottom:12px;"><?= e($student['department']) ?></div>

                        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Year of Study</div>
                        <div style="font-weight:600; margin-bottom:12px;"><?= e($student['year_of_study']) ?></div>

                        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Contact Phone</div>
                        <div style="font-weight:600; margin-bottom:12px;"><?= e($student['phone'] ?? '—') ?></div>

                        <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Emergency Contact</div>
                        <div style="font-weight:600;"><?= e($student['emergency_contact'] ?? '—') ?></div>
                    </div>
                </div>
            </div>

            <!-- Right: Booking History Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Registration & Booking History (<?= count($bookings) ?>)</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (!empty($bookings)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Ref</th>
                                        <th>Event Title</th>
                                        <th>Date</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $bk): ?>
                                        <tr>
                                            <td><code style="font-weight:700;"><?= e($bk['booking_reference']) ?></code></td>
                                            <td><strong><?= e($bk['event_title']) ?></strong></td>
                                            <td><?= format_date($bk['event_date']) ?></td>
                                            <td><?= (int)$bk['quantity'] ?></td>
                                            <td class="td-price" style="font-weight:700;"><?= format_currency($bk['total_amount']) ?></td>
                                            <td><?= booking_status_badge($bk['status']) ?></td>
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
                            <p class="empty-state-text" style="margin:0;">No bookings on record for this student.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
