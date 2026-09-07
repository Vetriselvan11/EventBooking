<?php
/**
 * Event Booking Management System
 * Authentication & Role-Based Access Control (RBAC)
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current authenticated user session data or null
 */
function get_current_user_data(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'         => (int)$_SESSION['user_id'],
        'name'       => $_SESSION['user_name'] ?? 'User',
        'email'      => $_SESSION['user_email'] ?? '',
        'role'       => $_SESSION['user_role'] ?? '',
        'avatar'     => $_SESSION['user_avatar'] ?? null,
        'student_id' => $_SESSION['student_id_number'] ?? null,
        'department' => $_SESSION['department'] ?? null
    ];
}

/**
 * Get current user ID
 */
function get_current_user_id(): ?int {
    return is_logged_in() ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current user role
 */
function get_current_role(): ?string {
    return is_logged_in() ? ($_SESSION['user_role'] ?? null) : null;
}

/**
 * Check if the user has specific role(s)
 */
function has_role(string|array $roles): bool {
    if (!is_logged_in()) {
        return false;
    }
    $currentRole = strtolower($_SESSION['user_role'] ?? '');
    if (is_array($roles)) {
        $allowed = array_map('strtolower', $roles);
        return in_array($currentRole, $allowed, true);
    }
    return $currentRole === strtolower($roles);
}

/**
 * Enforce that the user is logged in
 */
function require_login(string $redirectAfter = ''): void {
    if (!is_logged_in()) {
        if ($redirectAfter) {
            $_SESSION['redirect_after_login'] = $redirectAfter;
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/public/index.php');
        }
        setFlash('warning', 'Please sign in to access that page.');
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }
}

/**
 * Enforce that the user has a specific role
 */
function require_role(string|array $roles): void {
    require_login();

    if (!has_role($roles)) {
        http_response_code(403);
        $roleName = is_array($roles) ? implode(' or ', $roles) : $roles;
        die('<!DOCTYPE html><html><head><title>403 Access Denied</title><style>body{font-family:sans-serif;padding:40px;background:#f8fafc;color:#0f172a;text-align:center;} .box{max-width:500px;margin:50px auto;background:#fff;padding:30px;border-radius:8px;border:1px solid #e2e8f0;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);}</style></head><body><div class="box"><h2 style="color:#991b1b;">403 - Unauthorized Access</h2><p>You do not have administrative or appropriate permissions (' . htmlspecialchars($roleName) . ') to view this page.</p><a href="' . BASE_URL . '/public/index.php" style="display:inline-block;padding:10px 20px;background:#1e40af;color:#fff;text-decoration:none;border-radius:6px;margin-top:15px;">Return to Home</a></div></body></html>');
    }
}

/**
 * Authenticate user with email and password
 */
function login_user(string $email, string $password): array {
    $pdo = Database::getConnection();
    if (!$pdo) {
        return ['success' => false, 'error' => 'Database connection unavailable. Please check system setup.'];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   s.student_id_number, s.department as student_dept,
                   c.department as coord_dept, c.designation as coord_desig
            FROM users u
            LEFT JOIN students s ON u.id = s.user_id
            LEFT JOIN coordinators c ON u.id = c.user_id
            WHERE u.email = ? LIMIT 1
        ");
        $stmt->execute([trim($email)]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email address or password.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account is ' . $user['status'] . '. Please contact the system administrator.'];
        }

        if (!verify_password($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email address or password.'];
        }

        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_avatar'] = $user['avatar'];
        
        if ($user['role'] === 'student') {
            $_SESSION['student_id_number'] = $user['student_id_number'];
            $_SESSION['department'] = $user['student_dept'];
        } elseif ($user['role'] === 'coordinator') {
            $_SESSION['department'] = $user['coord_dept'];
            $_SESSION['designation'] = $user['coord_desig'];
        }

        log_audit($user['id'], 'LOGIN', 'User logged in successfully.');

        return ['success' => true, 'user' => $user];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Authentication query error: ' . $e->getMessage()];
    }
}

/**
 * Register a new student account
 */
function register_student(array $data): array {
    $pdo = Database::getConnection();
    if (!$pdo) {
        return ['success' => false, 'error' => 'Database connection failed.'];
    }

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $phone = trim($data['phone'] ?? '');
    $studentIdNumber = strtoupper(trim($data['student_id_number'] ?? ''));
    $department = trim($data['department'] ?? '');
    $yearOfStudy = trim($data['year_of_study'] ?? '1st Year');

    // Validation
    if (empty($name) || strlen($name) < 2) {
        return ['success' => false, 'error' => 'Please provide a valid full name.'];
    }
    if (!validate_email($email)) {
        return ['success' => false, 'error' => 'Please provide a valid email address.'];
    }
    if (!validate_password($password)) {
        return ['success' => false, 'error' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.'];
    }
    if (empty($studentIdNumber)) {
        return ['success' => false, 'error' => 'Student ID / Registration number is required.'];
    }
    if (empty($department)) {
        return ['success' => false, 'error' => 'Department / Major is required.'];
    }

    try {
        // Check for duplicate email
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'error' => 'An account with this email address already exists.'];
        }

        // Check for duplicate student ID
        $checkIdStmt = $pdo->prepare("SELECT id FROM students WHERE student_id_number = ? LIMIT 1");
        $checkIdStmt->execute([$studentIdNumber]);
        if ($checkIdStmt->fetch()) {
            return ['success' => false, 'error' => 'A student with this Student ID Number is already registered.'];
        }

        $pdo->beginTransaction();

        $hash = hash_password($password);
        $userStmt = $pdo->prepare("
            INSERT INTO users (role, name, email, password_hash, phone, status, created_at, updated_at)
            VALUES ('student', ?, ?, ?, ?, 'active', NOW(), NOW())
        ");
        $userStmt->execute([$name, $email, $hash, $phone ?: null]);
        $newUserId = (int)$pdo->lastInsertId();

        $studentStmt = $pdo->prepare("
            INSERT INTO students (user_id, student_id_number, department, year_of_study, emergency_contact)
            VALUES (?, ?, ?, ?, ?)
        ");
        $emergencyContact = trim($data['emergency_contact'] ?? '') ?: null;
        $studentStmt->execute([$newUserId, $studentIdNumber, $department, $yearOfStudy, $emergencyContact]);

        $pdo->commit();

        log_audit($newUserId, 'REGISTER', 'Student account registered.');
        create_notification($newUserId, 'Welcome to CampusEvent Hub!', 'Your student registration was successful. You can now browse and book campus events.', BASE_URL . '/public/events.php');

        return ['success' => true, 'user_id' => $newUserId];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'error' => 'Registration error: ' . $e->getMessage()];
    }
}

/**
 * Log out user and destroy session
 */
function logout_user(): void {
    $userId = get_current_user_id();
    if ($userId) {
        log_audit($userId, 'LOGOUT', 'User logged out.');
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}
