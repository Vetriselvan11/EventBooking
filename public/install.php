<?php
/**
 * Event Booking Management System
 * Web-Based Database Setup & Diagnostic Wizard
 */

define('APP_ROOT', dirname(__DIR__));
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$step = $_GET['step'] ?? 'intro';
$error = null;
$success = null;
$logs = [];

// Diagnostics
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '8.0.0', '>=');
$pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
$writableUploads = is_writable(__DIR__ . '/assets') || @mkdir(__DIR__ . '/assets/uploads', 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_install') {
    require_csrf_token();

    try {
        $logs[] = "Connecting to database target " . DB_HOST . ":" . DB_PORT . "...";
        $activePdo = null;

        // Try server connection first (for local XAMPP auto-create DB)
        $serverPdo = Database::getServerConnection();
        if ($serverPdo) {
            try {
                $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci;");
                $serverPdo->exec("USE `" . DB_NAME . "`;");
                $activePdo = $serverPdo;
                $logs[] = "Database `" . DB_NAME . "` ensured and active.";
            } catch (Exception $dbEx) {
                $logs[] = "Server connection mode bypassed: " . $dbEx->getMessage();
            }
        }

        // If not connected yet, try direct database connection (for cloud MySQL providers)
        if (!$activePdo) {
            $activePdo = Database::getConnection();
        }

        if (!$activePdo) {
            throw new Exception("Unable to connect to MySQL database. (Error: " . (Database::getLastError() ?? 'Check your DB credentials') . ")");
        }
        $logs[] = "Connected to database `" . DB_NAME . "` successfully.";

        $logs[] = "Executing database schema (tables, foreign keys, indexes)...";
        $schemaFile = APP_ROOT . '/database/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("schema.sql file not found.");
        }
        $schemaSql = file_get_contents($schemaFile);
        $activePdo->exec($schemaSql);
        $logs[] = "All relational tables created successfully.";

        $logs[] = "Importing seed records (Categories, Events, Coordinators, Students)...";
        $seedFile = APP_ROOT . '/database/seed.sql';
        if (!file_exists($seedFile)) {
            throw new Exception("seed.sql file not found.");
        }
        $seedSql = file_get_contents($seedFile);
        $activePdo->exec($seedSql);

        // Update with freshly hashed passwords using native Argon2id / Bcrypt
        $adminHash = hash_password('Admin@123');
        $coordHash = hash_password('Coord@123');
        $stuHash = hash_password('Student@123');

        $updateStmt = $activePdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@campus.edu'");
        $updateStmt->execute([$adminHash]);

        $updateCoord = $activePdo->prepare("UPDATE users SET password_hash = ? WHERE role = 'coordinator'");
        $updateCoord->execute([$coordHash]);

        $updateStu = $activePdo->prepare("UPDATE users SET password_hash = ? WHERE role = 'student'");
        $updateStu->execute([$stuHash]);

        $logs[] = "Initial demo accounts and password credentials configured.";
        $success = "Database initialized and seeded successfully!";
        $step = 'complete';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup & Diagnostics — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/main.css">
    <style>
        .installer-container {
            max-width: 760px;
            margin: 40px auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .installer-header {
            background: #0f172a;
            color: #ffffff;
            padding: 24px 32px;
            border-bottom: 3px solid #1e40af;
        }
        .installer-title { font-size: 1.4rem; font-weight: 700; margin: 0 0 6px 0; }
        .installer-sub { font-size: 0.9rem; color: #94a3b8; margin: 0; }
        .installer-body { padding: 32px; }
        .diag-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .diag-table th, .diag-table td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
        .diag-table th { background: #f8fafc; font-weight: 600; color: #334155; }
        .log-box {
            background: #0f172a;
            color: #38bdf8;
            font-family: monospace;
            font-size: 0.85rem;
            padding: 16px;
            border-radius: 6px;
            max-height: 240px;
            overflow-y: auto;
            margin: 20px 0;
            line-height: 1.5;
        }
        .account-card {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 12px;
        }
        .account-role { font-weight: 700; color: #1e40af; font-size: 0.95rem; margin-bottom: 4px; }
        .account-cred { font-size: 0.88rem; color: #334155; font-family: monospace; }
    </style>
</head>
<body style="background:#f1f5f9; min-height:100vh; padding:20px;">

<div class="installer-container">
    <div class="installer-header">
        <h1 class="installer-title">CampusEvent Hub Database Installer</h1>
        <p class="installer-sub">Automated System Diagnostics & MySQL 3NF Database Seeder</p>
    </div>

    <div class="installer-body">
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:24px;">
                <div class="alert-content">
                    <strong>Setup Encountered an Error:</strong><br>
                    <?= e($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:24px;">
                <div class="alert-content">
                    <strong><?= e($success) ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($step === 'intro'): ?>
            <h3 style="margin-top:0; color:#0f172a; font-size:1.15rem;">System Environment Diagnostics</h3>
            <p style="color:#475569; font-size:0.92rem; margin-bottom:16px;">
                The wizard will inspect your local PHP and MySQL configuration before provisioning the schema and sample records.
            </p>

            <table class="diag-table">
                <thead>
                    <tr>
                        <th>Requirement</th>
                        <th>Detected Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>PHP Version (>= 8.0)</td>
                        <td><?= e($phpVersion) ?></td>
                        <td><?= $phpOk ? '<span class="badge badge-success">Passed</span>' : '<span class="badge badge-danger">PHP 8.0+ Required</span>' ?></td>
                    </tr>
                    <tr>
                        <td>PDO MySQL Extension</td>
                        <td><?= $pdoOk ? 'Enabled (pdo_mysql)' : 'Disabled' ?></td>
                        <td><?= $pdoOk ? '<span class="badge badge-success">Passed</span>' : '<span class="badge badge-danger">Enable pdo_mysql in php.ini</span>' ?></td>
                    </tr>
                    <tr>
                        <td>Database Connection Target</td>
                        <td><?= e(DB_USER) ?>@<?= e(DB_HOST) ?>:<?= e(DB_PORT) ?> (DB: <?= e(DB_NAME) ?>)</td>
                        <td><span class="badge badge-neutral">Standard XAMPP</span></td>
                    </tr>
                </tbody>
            </table>

            <form method="POST" action="install.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="run_install">
                
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:16px; margin-bottom:24px;">
                    <h4 style="margin:0 0 6px 0; color:#1e40af; font-size:0.95rem;">What will happen when you initialize:</h4>
                    <ul style="margin:0; padding-left:20px; font-size:0.88rem; color:#1e293b; line-height:1.6;">
                        <li>Create MySQL database <code><?= e(DB_NAME) ?></code> with <code>utf8mb4</code> encoding</li>
                        <li>Build 10 normalized tables with primary keys, foreign key cascades, and performance indexes</li>
                        <li>Seed categories, demo events with real dates, bookings, payments, and digital ticket passes</li>
                        <li>Generate secure password hashes for Admin, Faculty Coordinators, and Students</li>
                    </ul>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:1rem; justify-content:center;">
                    <?= render_svg_icon('check-circle', '', 20) ?> Run 1-Click Database Setup & Seed
                </button>
            </form>

        <?php elseif ($step === 'complete'): ?>
            <h3 style="margin-top:0; color:#065f46; font-size:1.2rem;">✓ Platform Successfully Initialized!</h3>
            <p style="color:#475569; font-size:0.92rem;">
                All database tables have been provisioned and seeded with realistic demo records.
            </p>

            <?php if (!empty($logs)): ?>
                <div class="log-box">
                    <?php foreach ($logs as $log): ?>
                        <div>&gt; <?= e($log) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h4 style="color:#0f172a; margin:24px 0 12px 0;">Pre-Configured Demo Accounts</h4>

            <div class="account-card">
                <div class="account-role">1. System Administrator</div>
                <div class="account-cred">Email: <strong>admin@campus.edu</strong> | Password: <strong>Admin@123</strong></div>
            </div>

            <div class="account-card">
                <div class="account-role">2. Faculty / Event Coordinator</div>
                <div class="account-cred">Email: <strong>coordinator@campus.edu</strong> | Password: <strong>Coord@123</strong></div>
            </div>

            <div class="account-card">
                <div class="account-role">3. Student / Attendee</div>
                <div class="account-cred">Email: <strong>student@campus.edu</strong> | Password: <strong>Student@123</strong></div>
            </div>

            <div style="display:flex; gap:12px; margin-top:24px;">
                <a href="<?= BASE_URL ?>/public/login.php" class="btn btn-primary" style="flex:1; justify-content:center;">
                    Sign In to Portal <?= render_svg_icon('arrow-right', '', 18) ?>
                </a>
                <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-outline-secondary" style="flex:1; justify-content:center;">
                    Browse Events
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
