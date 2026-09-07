<?php
/**
 * Event Booking Management System
 * Universal Topbar & Header Template
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/session.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$pageTitle = $pageTitle ?? APP_NAME . ' — ' . APP_TAGLINE;
$currentUser = get_current_user_data();
$currentRole = get_current_role();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CampusEvent Hub — University & Enterprise Event Booking, Discovery, and Attendance Management System">
    <title><?= e($pageTitle) ?></title>
    
    <!-- Design System Stylesheets -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/main.css">
    <?php if (isset($isDashboard) && $isDashboard): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/dashboard.css">
    <?php endif; ?>
    <?php if (isset($isLanding) && $isLanding): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/landing.css">
    <?php endif; ?>
</head>
<body class="<?= isset($bodyClass) ? e($bodyClass) : '' ?>">

<!-- Top Universal Header -->
<header class="site-header" id="site-header">
    <div class="header-inner">
        <!-- Brand Logo & Name -->
        <a href="<?= BASE_URL ?>/public/index.php" class="brand-link">
            <div class="brand-logo-mark">
                <?= render_svg_icon('ticket', 'brand-icon', 22) ?>
            </div>
            <div class="brand-text">
                <span class="brand-name"><?= e(APP_NAME) ?></span>
                <span class="brand-badge">Academic & Enterprise</span>
            </div>
        </a>

        <!-- Desktop Navigation Links -->
        <nav class="main-nav" id="main-nav" aria-label="Main Navigation">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/public/index.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : '' ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/public/events.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'events.php') ? 'active' : '' ?>">Discover Events</a>
                </li>
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/public/verify-ticket.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'verify-ticket.php') ? 'active' : '' ?>">Verify Ticket</a>
                </li>
                
                <?php if ($currentUser): ?>
                    <!-- Role-Specific Direct Shortcut -->
                    <?php if ($currentRole === 'admin'): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="nav-link nav-link-portal">Admin Console</a>
                        </li>
                    <?php elseif ($currentRole === 'coordinator'): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/coordinator/dashboard.php" class="nav-link nav-link-portal">Coordinator Portal</a>
                        </li>
                    <?php elseif ($currentRole === 'student'): ?>
                        <li class="nav-item">
                            <a href="<?= BASE_URL ?>/student/dashboard.php" class="nav-link nav-link-portal">My Passes</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Right User Actions & Auth Profile -->
        <div class="header-actions">
            <?php if ($currentUser): ?>
                <div class="user-dropdown-wrapper">
                    <button type="button" class="user-profile-btn" id="userMenuBtn" aria-expanded="false" aria-haspopup="true">
                        <div class="user-avatar-badge">
                            <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                        </div>
                        <div class="user-profile-info">
                            <span class="user-profile-name"><?= e($currentUser['name']) ?></span>
                            <span class="user-profile-role"><?= ucfirst(e($currentUser['role'])) ?></span>
                        </div>
                        <svg class="dropdown-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>

                    <div class="dropdown-menu" id="userMenuDropdown" aria-labelledby="userMenuBtn">
                        <div class="dropdown-header">
                            <div class="dropdown-user-name"><?= e($currentUser['name']) ?></div>
                            <div class="dropdown-user-email"><?= e($currentUser['email']) ?></div>
                        </div>
                        <div class="dropdown-divider"></div>
                        
                        <?php if ($currentRole === 'admin'): ?>
                            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="dropdown-item">
                                <?= render_svg_icon('chart', 'item-icon', 16) ?> Admin Dashboard
                            </a>
                            <a href="<?= BASE_URL ?>/admin/events/index.php" class="dropdown-item">
                                <?= render_svg_icon('calendar', 'item-icon', 16) ?> Manage Events
                            </a>
                            <a href="<?= BASE_URL ?>/admin/bookings/index.php" class="dropdown-item">
                                <?= render_svg_icon('ticket', 'item-icon', 16) ?> All Bookings
                            </a>
                        <?php elseif ($currentRole === 'coordinator'): ?>
                            <a href="<?= BASE_URL ?>/coordinator/dashboard.php" class="dropdown-item">
                                <?= render_svg_icon('chart', 'item-icon', 16) ?> Coordinator Dashboard
                            </a>
                            <a href="<?= BASE_URL ?>/coordinator/events/index.php" class="dropdown-item">
                                <?= render_svg_icon('calendar', 'item-icon', 16) ?> My Events
                            </a>
                            <a href="<?= BASE_URL ?>/coordinator/attendees/index.php" class="dropdown-item">
                                <?= render_svg_icon('users', 'item-icon', 16) ?> Attendee Check-in
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/student/dashboard.php" class="dropdown-item">
                                <?= render_svg_icon('ticket', 'item-icon', 16) ?> My Dashboard
                            </a>
                            <a href="<?= BASE_URL ?>/student/tickets.php" class="dropdown-item">
                                <?= render_svg_icon('shield-check', 'item-icon', 16) ?> Entry Passes & QR
                            </a>
                            <a href="<?= BASE_URL ?>/student/bookings.php" class="dropdown-item">
                                <?= render_svg_icon('calendar', 'item-icon', 16) ?> Booking History
                            </a>
                            <a href="<?= BASE_URL ?>/student/profile.php" class="dropdown-item">
                                <?= render_svg_icon('user', 'item-icon', 16) ?> Account Profile
                            </a>
                        <?php endif; ?>

                        <div class="dropdown-divider"></div>
                        <a href="<?= BASE_URL ?>/public/logout.php" class="dropdown-item text-danger">
                            <svg class="item-icon text-danger" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                            Sign Out
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="<?= BASE_URL ?>/public/login.php" class="btn btn-outline-primary btn-sm">Sign In</a>
                    <a href="<?= BASE_URL ?>/public/register.php" class="btn btn-primary btn-sm">Student Register</a>
                </div>
            <?php endif; ?>

            <!-- Mobile Hamburger Toggle -->
            <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu" aria-expanded="false">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Off-Canvas Nav Menu -->
<div class="mobile-drawer" id="mobileDrawer">
    <div class="mobile-drawer-header">
        <span class="drawer-title"><?= e(APP_NAME) ?></span>
        <button type="button" class="drawer-close" id="drawerClose" aria-label="Close menu">&times;</button>
    </div>
    <div class="mobile-drawer-body">
        <ul class="mobile-nav-list">
            <li><a href="<?= BASE_URL ?>/public/index.php" class="mobile-nav-link">Home</a></li>
            <li><a href="<?= BASE_URL ?>/public/events.php" class="mobile-nav-link">Discover Events</a></li>
            <li><a href="<?= BASE_URL ?>/public/verify-ticket.php" class="mobile-nav-link">Verify Ticket</a></li>
            <?php if ($currentUser): ?>
                <li class="mobile-nav-divider"></li>
                <li class="mobile-nav-section-title"><?= strtoupper(e($currentRole)) ?> MENU</li>
                <?php if ($currentRole === 'admin'): ?>
                    <li><a href="<?= BASE_URL ?>/admin/dashboard.php" class="mobile-nav-link">Admin Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/events/index.php" class="mobile-nav-link">Manage Events</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/bookings/index.php" class="mobile-nav-link">All Bookings</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/payments/index.php" class="mobile-nav-link">Payments</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/reports/index.php" class="mobile-nav-link">Reports</a></li>
                <?php elseif ($currentRole === 'coordinator'): ?>
                    <li><a href="<?= BASE_URL ?>/coordinator/dashboard.php" class="mobile-nav-link">Coordinator Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/coordinator/events/index.php" class="mobile-nav-link">My Events</a></li>
                    <li><a href="<?= BASE_URL ?>/coordinator/attendees/index.php" class="mobile-nav-link">Attendee Check-in</a></li>
                    <li><a href="<?= BASE_URL ?>/coordinator/reports/index.php" class="mobile-nav-link">Reports</a></li>
                <?php else: ?>
                    <li><a href="<?= BASE_URL ?>/student/dashboard.php" class="mobile-nav-link">My Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/student/tickets.php" class="mobile-nav-link">My Digital Passes</a></li>
                    <li><a href="<?= BASE_URL ?>/student/bookings.php" class="mobile-nav-link">Booking History</a></li>
                    <li><a href="<?= BASE_URL ?>/student/profile.php" class="mobile-nav-link">My Profile</a></li>
                <?php endif; ?>
                <li class="mobile-nav-divider"></li>
                <li><a href="<?= BASE_URL ?>/public/logout.php" class="mobile-nav-link text-danger">Sign Out</a></li>
            <?php else: ?>
                <li class="mobile-nav-divider"></li>
                <li><a href="<?= BASE_URL ?>/public/login.php" class="mobile-nav-link">Sign In</a></li>
                <li><a href="<?= BASE_URL ?>/public/register.php" class="mobile-nav-link">Student Registration</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- Flash Alerts Wrapper -->
<div class="flash-wrapper-outer">
    <?= renderFlashMessages() ?>
</div>
