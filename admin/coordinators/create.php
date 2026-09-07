<?php
/**
 * CampusEvent Hub — Admin Add Coordinator (admin/coordinators/create.php)
 */

define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/validation.php';

require_role('admin');

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$error = null;

$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'department' => '',
    'designation' => 'Associate Professor & Event Lead',
    'office_location' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['department'] = trim($_POST['department'] ?? '');
    $formData['designation'] = trim($_POST['designation'] ?? '');
    $formData['office_location'] = trim($_POST['office_location'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($formData['name']) || strlen($formData['name']) < 2) {
        $error = 'Full name is required.';
    } elseif (!validate_email($formData['email'])) {
        $error = 'Please enter a valid institutional email address.';
    } elseif (!validate_password($password)) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    } elseif (empty($formData['department'])) {
        $error = 'Department is required.';
    } else {
        try {
            // Check duplicate email
            $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk->execute([$formData['email']]);
            if ($chk->fetch()) {
                $error = 'An account with this email address already exists.';
            } else {
                $pdo->beginTransaction();

                $hash = hash_password($password);
                $uStmt = $pdo->prepare("INSERT INTO users (role, name, email, password_hash, phone, status, created_at, updated_at) VALUES ('coordinator', ?, ?, ?, ?, 'active', NOW(), NOW())");
                $uStmt->execute([$formData['name'], $formData['email'], $hash, $formData['phone'] ?: null]);
                $newUserId = (int)$pdo->lastInsertId();

                $cStmt = $pdo->prepare("INSERT INTO coordinators (user_id, department, designation, office_location, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $cStmt->execute([$newUserId, $formData['department'], $formData['designation'], $formData['office_location'] ?: null]);

                $pdo->commit();

                log_audit($currentUser['id'], 'COORDINATOR_PROVISION', "Provisioned faculty coordinator {$formData['name']} ({$formData['email']})");
                setFlash('success', "Faculty Coordinator '{$formData['name']}' provisioned successfully.");
                header('Location: index.php');
                exit;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Failed to provision coordinator: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Faculty Coordinator — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Provision Faculty Coordinator</h1>
                <p>Grant faculty members administrative event creation and roster check-in privileges.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Roster</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width:760px;">
            <div class="card-body" style="padding:28px;">
                <form method="POST" action="create.php">
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Full Name & Title</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Robert Langdon" value="<?= e($formData['name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">University Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="r.langdon@campus.edu" value="<?= e($formData['email']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Academic Department</label>
                            <input type="text" name="department" class="form-control" placeholder="e.g. Department of Physics & Astronomy" value="<?= e($formData['department']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">Designation / Title</label>
                            <input type="text" name="designation" class="form-control" value="<?= e($formData['designation']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Office / Building Location</label>
                            <input type="text" name="office_location" class="form-control" placeholder="e.g. Einstein Wing, Suite 305" value="<?= e($formData['office_location']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000" value="<?= e($formData['phone']) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">Initial Temporary Password (min 6 chars)</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div style="display:flex; gap:12px; margin-top:24px; padding-top:18px; border-top:1px solid var(--border-subtle);">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Provision Coordinator Account
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
