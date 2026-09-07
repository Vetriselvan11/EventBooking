<?php
/**
 * CampusEvent Hub — Sign Out (logout.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/includes/auth.php';

logout_user();

// Flash message on next request
session_start();
setFlash('info', 'You have been signed out safely.');

header('Location: ' . BASE_URL . '/public/login.php');
exit;
