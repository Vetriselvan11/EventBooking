<?php
/**
 * Event Booking Management System
 * Database Installer & Migration CLI Script
 */

define('APP_ROOT', dirname(__DIR__));
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

echo "\n=======================================================\n";
echo " CampusEvent Hub — Database Setup & Migration Script\n";
echo "=======================================================\n\n";

try {
    // 1. Connect to MySQL Target
    echo "[1/4] Connecting to database target " . DB_HOST . ":" . DB_PORT . "...\n";
    $activePdo = null;

    $serverPdo = Database::getServerConnection();
    if ($serverPdo) {
        try {
            $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_CHARSET . "_unicode_ci;");
            $serverPdo->exec("USE `" . DB_NAME . "`;");
            $activePdo = $serverPdo;
            echo "      ✓ Database '" . DB_NAME . "' ensured and active.\n\n";
        } catch (Exception $dbEx) {
            echo "      - Server creation bypassed: " . $dbEx->getMessage() . "\n";
        }
    }

    if (!$activePdo) {
        $activePdo = Database::getConnection();
    }

    if (!$activePdo) {
        throw new Exception("Could not connect to database. Please verify MySQL service and credentials.\nError: " . (Database::getLastError() ?? 'Unknown error'));
    }
    echo "      ✓ Connected to database successfully.\n\n";

    // 2. Execute schema.sql
    echo "[2/4] Executing schema.sql DDL definitions...\n";
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found at " . $schemaFile);
    }
    $schemaSql = file_get_contents($schemaFile);
    $activePdo->exec($schemaSql);
    echo "      ✓ Tables, Foreign Keys, and Indexes created successfully.\n\n";

    // 3. Execute seed.sql and update password hashes dynamically
    echo "[3/4] Seeding initial data (Admin, Coordinators, Students, Events, Bookings)...\n";
    $seedFile = __DIR__ . '/seed.sql';
    if (!file_exists($seedFile)) {
        throw new Exception("Seed file not found at " . $seedFile);
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

    echo "      ✓ Seed records and password hashes generated successfully.\n\n";
    echo "=======================================================\n";
    echo " Setup Complete! You can now log in with demo accounts:\n\n";
    echo " 1. Administrator:\n";
    echo "    Email:    admin@campus.edu\n";
    echo "    Password: Admin@123\n\n";
    echo " 2. Faculty / Coordinator:\n";
    echo "    Email:    coordinator@campus.edu\n";
    echo "    Password: Coord@123\n\n";
    echo " 3. Student:\n";
    echo "    Email:    student@campus.edu\n";
    echo "    Password: Student@123\n";
    echo "=======================================================\n\n";

} catch (Exception $e) {
    echo "\n[ERROR] Setup failed: " . $e->getMessage() . "\n\n";
    exit(1);
}
