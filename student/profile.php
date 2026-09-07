<?php
/**
 * CampusEvent Hub — Student Profile & Security Settings (student/profile.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/validation.php';

require_role('student');

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$userId = $currentUser['id'];

$error = null;
$success = null;

// Fetch Student Profile
$stmt = $pdo->prepare("
    SELECT u.*, s.student_id_number, s.department, s.year_of_study, s.emergency_contact
    FROM users u
    JOIN students s ON u.id = s.user_id
    WHERE u.id = ? LIMIT 1
");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $yearOfStudy = trim($_POST['year_of_study'] ?? '1st Year');
        $emergencyContact = trim($_POST['emergency_contact'] ?? '');

        if (empty($name) || strlen($name) < 2) {
            $error = 'Full name must be at least 2 characters long.';
        } else {
            try {
                $pdo->beginTransaction();

                $uStmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, updated_at = NOW() WHERE id = ?");
                $uStmt->execute([$name, $phone ?: null, $userId]);

                $sStmt = $pdo->prepare("UPDATE students SET year_of_study = ?, emergency_contact = ?, updated_at = NOW() WHERE user_id = ?");
                $sStmt->execute([$yearOfStudy, $emergencyContact ?: null, $userId]);

                $pdo->commit();

                $_SESSION['user_name'] = $name;
                $success = 'Profile details updated successfully.';
                log_audit($userId, 'PROFILE_UPDATE', 'Updated personal profile information.');

                // Refresh profile data
                $stmt->execute([$userId]);
                $profile = $stmt->fetch();

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Profile update failed: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (!verify_password($currentPass, $profile['password_hash'])) {
            $error = 'Current password entered is incorrect.';
        } elseif (!validate_password($newPass)) {
            $error = 'New password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'New password confirmation does not match.';
        } else {
            try {
                $newHash = hash_password($newPass);
                $pStmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
                $pStmt->execute([$newHash, $userId]);

                $success = 'Your password has been changed successfully.';
                log_audit($userId, 'PASSWORD_CHANGE', 'User updated account password.');
            } catch (Exception $e) {
                $error = 'Password change failed: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Student Profile & Security — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Profile & Account Security</h1>
                <p>Manage your academic registration details, contact numbers, and security credentials.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($success) ?></div>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:28px; align-items:start;">
            
            <!-- Left: Personal Information -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Student Profile Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label form-label-required">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= e($profile['name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Student ID Number (Locked)</label>
                                <input type="text" class="form-control" value="<?= e($profile['student_id_number']) ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email Address (Locked)</label>
                                <input type="email" class="form-control" value="<?= e($profile['email']) ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Department (Locked)</label>
                                <input type="text" class="form-control" value="<?= e($profile['department']) ?>" readonly style="background:#f1f5f9; cursor:not-allowed;">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Year of Study</label>
                                <select name="year_of_study" class="form-select">
                                    <option value="1st Year" <?= ($profile['year_of_study'] === '1st Year') ? 'selected' : '' ?>>1st Year</option>
                                    <option value="2nd Year" <?= ($profile['year_of_study'] === '2nd Year') ? 'selected' : '' ?>>2nd Year</option>
                                    <option value="3rd Year" <?= ($profile['year_of_study'] === '3rd Year') ? 'selected' : '' ?>>3rd Year</option>
                                    <option value="4th Year" <?= ($profile['year_of_study'] === '4th Year') ? 'selected' : '' ?>>4th Year</option>
                                    <option value="Postgraduate / PhD" <?= ($profile['year_of_study'] === 'Postgraduate / PhD') ? 'selected' : '' ?>>Postgraduate / PhD</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= e($profile['phone'] ?? '') ?>" placeholder="+1 555-000-0000">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Emergency Contact Info</label>
                            <input type="text" name="emergency_contact" class="form-control" value="<?= e($profile['emergency_contact'] ?? '') ?>" placeholder="Guardian Name and Phone">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Save Profile Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Change Password -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Change Password</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label class="form-label form-label-required">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label form-label-required">New Password (min 6 chars)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label form-label-required">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
