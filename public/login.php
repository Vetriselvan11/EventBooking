<?php
/**
 * CampusEvent Hub — User Sign In (login.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/validation.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = get_current_role();
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } elseif ($role === 'coordinator') {
        header('Location: ' . BASE_URL . '/coordinator/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . '/student/dashboard.php');
    }
    exit;
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        $res = login_user($email, $password);
        if ($res['success']) {
            setFlash('success', 'Welcome back, ' . e($res['user']['name']) . '!');
            
            // Redirect to intended URL if one was set
            if (!empty($_SESSION['redirect_after_login'])) {
                $target = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $target);
                exit;
            }

            // Otherwise redirect according to role
            $role = $res['user']['role'];
            if ($role === 'admin') {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } elseif ($role === 'coordinator') {
                header('Location: ' . BASE_URL . '/coordinator/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . '/student/dashboard.php');
            }
            exit;
        } else {
            $error = $res['error'];
        }
    }
}

$pageTitle = 'Sign In — ' . APP_NAME;
require_once APP_ROOT . '/includes/header.php';
?>

<main class="main-content" style="background:#f1f5f9; display:flex; align-items:center; justify-content:center; min-height: calc(100vh - 140px);">
    <div style="width:100%; max-width:440px; padding:20px;">
        
        <div class="card" style="box-shadow:var(--shadow-md); border-top:4px solid var(--primary);">
            <div class="card-header" style="text-align:center; display:block; padding:24px 24px 14px 24px;">
                <div class="brand-logo-mark" style="margin:0 auto 12px auto;">
                    <?= render_svg_icon('ticket', '', 20) ?>
                </div>
                <h2 style="font-size:1.35rem; font-weight:800; margin-bottom:4px;">Sign In to CampusEvent</h2>
                <p style="font-size:0.85rem; color:var(--text-muted); margin:0;">Access your events, registrations, or console</p>
            </div>

            <div class="card-body" style="padding:24px;">
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="margin-bottom:18px;">
                        <div class="alert-content"><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label class="form-label form-label-required">Institutional Email Address</label>
                        <input type="email" name="email" id="loginEmail" class="form-control" placeholder="username@campus.edu" value="<?= e($email) ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                            <label class="form-label form-label-required" style="margin-bottom:0;">Password</label>
                        </div>
                        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding:11px; margin-top:8px;">
                        Sign In <?= render_svg_icon('arrow-right', '', 18) ?>
                    </button>
                </form>

                <!-- Demo Account Fast-Fill Bar -->
                <div style="margin-top:24px; padding-top:18px; border-top:1px solid var(--border-subtle);">
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--text-muted); text-align:center; margin-bottom:10px;">
                        Quick Demo Account Selector
                    </div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:6px;">
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem; justify-content:center; padding:6px 4px;" onclick="fillLogin('admin@campus.edu', 'Admin@123')">
                            Admin
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem; justify-content:center; padding:6px 4px;" onclick="fillLogin('coordinator@campus.edu', 'Coord@123')">
                            Coordinator
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" style="font-size:0.75rem; justify-content:center; padding:6px 4px;" onclick="fillLogin('student@campus.edu', 'Student@123')">
                            Student
                        </button>
                    </div>
                </div>

            </div>

            <div class="card-footer" style="text-align:center; font-size:0.86rem;">
                Don't have a student account? <a href="<?= BASE_URL ?>/public/register.php" style="font-weight:600;">Register Here</a>
            </div>
        </div>

    </div>
</main>

<script>
function fillLogin(email, pass) {
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPassword').value = pass;
}
</script>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
