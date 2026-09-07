<?php
/**
 * Event Booking Management System
 * Database PDO Connection Singleton
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static bool $connectionAttempted = false;
    private static ?string $lastError = null;

    /**
     * Get the singleton PDO instance
     */
    public static function getConnection(): ?PDO {
        if (self::$instance === null && !self::$connectionAttempted) {
            self::$connectionAttempted = true;
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_PORT,
                    DB_NAME,
                    DB_CHARSET
                );

                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                self::$lastError = $e->getMessage();
                // We do not exit immediately so the installer or health check can catch this
            }
        }

        return self::$instance;
    }

    /**
     * Test connection without throwing fatal error
     */
    public static function isConnected(): bool {
        return self::getConnection() !== null;
    }

    /**
     * Get last connection error message
     */
    public static function getLastError(): ?string {
        return self::$lastError;
    }

    /**
     * Get raw connection to MySQL server without specifying dbname
     * (Useful for the auto-installer to CREATE DATABASE)
     */
    public static function getServerConnection(): ?PDO {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ];
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            return null;
        }
    }
}
