<?php
/**
 * CampusEvent Hub — Admin Student Directory (admin/students/index.php)
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
$search = trim($_GET['q'] ?? '');
$deptFilter = trim($_GET['dept'] ?? '');

// Handle status toggling (Active <-> Suspended)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    require_csrf_token();
    $targetUserId = (int)$_POST['user_id'];
    $newStatus = ($_POST['current_status'] === 'active') ? 'suspended' : 'active';

    $update = $pdo->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ? AND role = 'student'");
    $update->execute([$newStatus, $targetUserId]);

    log_audit($currentUser['id'], 'STUDENT_STATUS_TOGGLE', "Changed student ID {$targetUserId} status to {$newStatus}");
    setFlash('success', "Student account status updated to '{$newStatus}'.");
}

$where = ["u.role = 'student'"];
$params = [];

if (!empty($search)) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ? OR s.student_id_number LIKE ?)";
    $t = "%{$search}%";
    $params[] = $t;
    $params[] = $t;
    $params[] = $t;
}

if (!empty($deptFilter)) {
    $where[] = "s.department = ?";
    $params[] = $deptFilter;
}

$sql = "
    SELECT u.*, s.student_id_number, s.department, s.year_of_study,
           (SELECT COUNT(id) FROM bookings WHERE user_id = u.id AND status = 'confirmed') as total_bookings,
           (SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE user_id = u.id AND status = 'confirmed') as total_spend
    FROM users u
    JOIN students s ON u.id = s.user_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY u.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Unique departments for filter
$deptStmt = $pdo->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Student Directory — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Enrolled Student Directory</h1>
                <p>Manage registered student accounts, verify institutional IDs, and inspect registration records.</p>
            </div>
            <div class="dashboard-header-actions">
                <span class="badge badge-neutral" style="padding:6px 12px; font-size:0.85rem;">
                    <strong><?= count($students) ?></strong> Students Registered
                </span>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <form method="GET" action="index.php" class="filter-toolbar">
            <div class="filter-group-left">
                <div class="search-input-wrapper">
                    <span class="search-input-icon"><?= render_svg_icon('search', '', 16) ?></span>
                    <input type="text" name="q" class="form-control search-input-field" placeholder="Search by name, email, or Student ID..." value="<?= e($search) ?>">
                </div>

                <div style="min-width:220px;">
                    <select name="dept" class="form-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= e($d) ?>" <?= ($deptFilter === $d) ? 'selected' : '' ?>>
                                <?= e($d) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if (!empty($search) || !empty($deptFilter)): ?>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (!empty($students)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Student ID</th>
                                    <th>Department</th>
                                    <th>Year</th>
                                    <th>Bookings</th>
                                    <th>Total Spend</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $stu): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--text-primary);"><?= e($stu['name']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= e($stu['email']) ?></div>
                                        </td>
                                        <td><code style="font-weight:700;"><?= e($stu['student_id_number']) ?></code></td>
                                        <td><?= e($stu['department']) ?></td>
                                        <td><span class="badge badge-secondary"><?= e($stu['year_of_study']) ?></span></td>
                                        <td><strong><?= (int)$stu['total_bookings'] ?></strong> events</td>
                                        <td class="td-price" style="font-weight:700;"><?= format_currency($stu['total_spend']) ?></td>
                                        <td>
                                            <span class="badge <?= ($stu['status'] === 'active') ? 'badge-success' : 'badge-danger' ?>">
                                                <?= ucfirst(e($stu['status'])) ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <div class="table-actions" style="justify-content:flex-end;">
                                                <a href="<?= BASE_URL ?>/admin/students/view.php?id=<?= $stu['id'] ?>" class="btn btn-outline-primary btn-sm" title="View Profile & Bookings">
                                                    Inspect
                                                </a>
                                                <form method="POST" action="index.php" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?= $stu['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $stu['status'] ?>">
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" data-confirm="Change status for this student?">
                                                        <?= ($stu['status'] === 'active') ? 'Suspend' : 'Activate' ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border:none;">
                        <h4 class="empty-state-title">No students found</h4>
                        <p class="empty-state-text">No student records match the search filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
