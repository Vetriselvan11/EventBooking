<?php
/**
 * CampusEvent Hub — Student Registration (register.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/validation.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}

$error = null;
$formData = [
    'name' => '',
    'email' => '',
    'student_id_number' => '',
    'department' => '',
    'year_of_study' => '1st Year',
    'phone' => '',
    'emergency_contact' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $formData['name'] = trim($_POST['name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $formData['password'] = $_POST['password'] ?? '';
    $formData['student_id_number'] = trim($_POST['student_id_number'] ?? '');
    $formData['department'] = trim($_POST['department'] ?? '');
    $formData['year_of_study'] = trim($_POST['year_of_study'] ?? '1st Year');
    $formData['phone'] = trim($_POST['phone'] ?? '');
    $formData['emergency_contact'] = trim($_POST['emergency_contact'] ?? '');

    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($formData['password'] !== $confirmPassword) {
        $error = 'The passwords you entered do not match.';
    } else {
        $res = register_student($formData);
        if ($res['success']) {
            // Auto login after successful registration
            login_user($formData['email'], $formData['password']);
            setFlash('success', 'Registration complete! Welcome to CampusEvent Hub.');
            header('Location: ' . BASE_URL . '/student/dashboard.php');
            exit;
        } else {
            $error = $res['error'];
        }
    }
}

$pageTitle = 'Student Registration — ' . APP_NAME;
require_once APP_ROOT . '/includes/header.php';
?>

<main class="main-content" style="background:#f1f5f9; padding:40px 0 70px 0;">
    <div class="container" style="max-width:680px;">
        
        <div class="card" style="box-shadow:var(--shadow-md); border-top:4px solid var(--primary);">
            <div class="card-header" style="display:block; padding:24px 28px 16px 28px;">
                <h2 style="font-size:1.45rem; font-weight:800; margin-bottom:4px;">Student Account Registration</h2>
                <p style="font-size:0.88rem; color:var(--text-muted); margin:0;">
                    Create your verified campus profile to reserve passes for hackathons, guest lectures, and symposiums.
                </p>
            </div>

            <div class="card-body" style="padding:28px;">
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="margin-bottom:20px;">
                        <div class="alert-content"><?= e($error) ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php">
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Full Legal Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Maya Lin" value="<?= e($formData['name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label form-label-required">Student ID / Roll Number</label>
                            <input type="text" name="student_id_number" class="form-control" placeholder="e.g. STU-2026-1049" value="<?= e($formData['student_id_number']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">University Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="maya.lin@campus.edu" value="<?= e($formData['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000" value="<?= e($formData['phone']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Department / Major</label>
                            <select name="department" class="form-select" required>
                                <option value="">Select Department...</option>
                                <option value="Computer Science & Engineering" <?= ($formData['department'] === 'Computer Science & Engineering') ? 'selected' : '' ?>>Computer Science & Engineering</option>
                                <option value="Electrical & Electronics Engineering" <?= ($formData['department'] === 'Electrical & Electronics Engineering') ? 'selected' : '' ?>>Electrical & Electronics Engineering</option>
                                <option value="Mechanical & Aerospace Engineering" <?= ($formData['department'] === 'Mechanical & Aerospace Engineering') ? 'selected' : '' ?>>Mechanical & Aerospace Engineering</option>
                                <option value="Business Administration & Management" <?= ($formData['department'] === 'Business Administration & Management') ? 'selected' : '' ?>>Business Administration & Management</option>
                                <option value="Bio-Medical & Health Sciences" <?= ($formData['department'] === 'Bio-Medical & Health Sciences') ? 'selected' : '' ?>>Bio-Medical & Health Sciences</option>
                                <option value="School of Fine Arts & Design" <?= ($formData['department'] === 'School of Fine Arts & Design') ? 'selected' : '' ?>>School of Fine Arts & Design</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label form-label-required">Year of Study</label>
                            <select name="year_of_study" class="form-select" required>
                                <option value="1st Year" <?= ($formData['year_of_study'] === '1st Year') ? 'selected' : '' ?>>1st Year (Freshman)</option>
                                <option value="2nd Year" <?= ($formData['year_of_study'] === '2nd Year') ? 'selected' : '' ?>>2nd Year (Sophomore)</option>
                                <option value="3rd Year" <?= ($formData['year_of_study'] === '3rd Year') ? 'selected' : '' ?>>3rd Year (Junior)</option>
                                <option value="4th Year" <?= ($formData['year_of_study'] === '4th Year') ? 'selected' : '' ?>>4th Year (Senior)</option>
                                <option value="Postgraduate / PhD" <?= ($formData['year_of_study'] === 'Postgraduate / PhD') ? 'selected' : '' ?>>Postgraduate / PhD</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Emergency Contact Name & Number (Optional)</label>
                        <input type="text" name="emergency_contact" class="form-control" placeholder="e.g. Parent Name (+1 555-123-4567)" value="<?= e($formData['emergency_contact']) ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Password (min 6 chars)</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label form-label-required">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div style="margin-top:10px;">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            Create Student Account <?= render_svg_icon('check-circle', '', 18) ?>
                        </button>
                    </div>
                </form>

            </div>

            <div class="card-footer" style="text-align:center; font-size:0.86rem;">
                Already have an account? <a href="<?= BASE_URL ?>/public/login.php" style="font-weight:600;">Sign In Instead</a>
            </div>
        </div>

    </div>
</main>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
