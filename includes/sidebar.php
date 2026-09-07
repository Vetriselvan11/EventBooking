<?php
/**
 * Event Booking Management System
 * Role-Based Dashboard Sidebar Navigation
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$currentRole = get_current_role();
$currentUser = get_current_user_data();
$currentScript = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="dashboard-sidebar" id="dashboardSidebar">
    <div class="sidebar-brand-badge">
        <div class="sidebar-role-tag role-tag-<?= e($currentRole) ?>">
            <?= strtoupper(e($currentRole)) ?> CONSOLE
        </div>
        <div class="sidebar-user-summary">
            <span class="user-greeting">Logged in as</span>
            <span class="user-name-full"><?= e($currentUser['name'] ?? 'User') ?></span>
            <?php if (!empty($currentUser['department'])): ?>
                <span class="user-dept-sub"><?= e($currentUser['department']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Dashboard Navigation">
        <?php if ($currentRole === 'admin'): ?>
            <!-- ADMIN NAVIGATION -->
            <div class="nav-section-label">Core Administration</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link <?= ($currentScript === 'dashboard.php' && $currentDir === 'admin') ? 'active' : '' ?>">
                        <?= render_svg_icon('chart', 'sb-icon', 18) ?>
                        <span class="sb-text">Overview Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/events/index.php" class="sidebar-link <?= ($currentDir === 'events') ? 'active' : '' ?>">
                        <?= render_svg_icon('calendar', 'sb-icon', 18) ?>
                        <span class="sb-text">Event Management</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/categories/index.php" class="sidebar-link <?= ($currentDir === 'categories') ? 'active' : '' ?>">
                        <?= render_svg_icon('tag', 'sb-icon', 18) ?>
                        <span class="sb-text">Categories</span>
                    </a>
                </li>
            </ul>

            <div class="nav-section-label">Users & Rosters</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>/admin/students/index.php" class="sidebar-link <?= ($currentDir === 'students') ? 'active' : '' ?>">
                        <?= render_svg_icon('users', 'sb-icon', 18) ?>
                        <span class="sb-text">Student Directory</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/coordinators/index.php" class="sidebar-link <?= ($currentDir === 'coordinators') ? 'active' : '' ?>">
                        <?= render_svg_icon('user', 'sb-icon', 18) ?>
                        <span class="sb-text">Faculty Coordinators</span>
                    </a>
                </li>
            </ul>

            <div class="nav-section-label">Transactions & Audit</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>/admin/bookings/index.php" class="sidebar-link <?= ($currentDir === 'bookings') ? 'active' : '' ?>">
                        <?= render_svg_icon('ticket', 'sb-icon', 18) ?>
                        <span class="sb-text">All Bookings</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/payments/index.php" class="sidebar-link <?= ($currentDir === 'payments') ? 'active' : '' ?>">
                        <?= render_svg_icon('currency', 'sb-icon', 18) ?>
                        <span class="sb-text">Payment Audit Log</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/reports/index.php" class="sidebar-link <?= ($currentDir === 'reports') ? 'active' : '' ?>">
                        <?= render_svg_icon('download', 'sb-icon', 18) ?>
                        <span class="sb-text">Reports & Export</span>
                    </a>
                </li>
            </ul>

        <?php elseif ($currentRole === 'coordinator'): ?>
            <!-- COORDINATOR NAVIGATION -->
            <div class="nav-section-label">Event Operations</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>/coordinator/dashboard.php" class="sidebar-link <?= ($currentScript === 'dashboard.php' && $currentDir === 'coordinator') ? 'active' : '' ?>">
                        <?= render_svg_icon('chart', 'sb-icon', 18) ?>
                        <span class="sb-text">Coordinator Overview</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/coordinator/events/index.php" class="sidebar-link <?= ($currentDir === 'events') ? 'active' : '' ?>">
                        <?= render_svg_icon('calendar', 'sb-icon', 18) ?>
                        <span class="sb-text">Assigned Events</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/coordinator/events/create.php" class="sidebar-link">
                        <?= render_svg_icon('plus', 'sb-icon', 18) ?>
                        <span class="sb-text">Create Department Event</span>
                    </a>
                </li>
            </ul>

            <div class="nav-section-label">Attendance & Verification</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>/coordinator/attendees/index.php" class="sidebar-link <?= ($currentDir === 'attendees') ? 'active' : '' ?>">
                        <?= render_svg_icon('check-circle', 'sb-icon', 18) ?>
                        <span class="sb-text">Live Check-In Desk</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/public/verify-ticket.php" target="_blank" class="sidebar-link">
                        <?= render_svg_icon('shield-check', 'sb-icon', 18) ?>
                        <span class="sb-text">Pass Scanner Tool</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/coordinator/reports/index.php" class="sidebar-link <?= ($currentDir === 'reports') ? 'active' : '' ?>">
                        <?= render_svg_icon('download', 'sb-icon', 18) ?>
                        <span class="sb-text">Attendance Reports</span>
                    </a>
                </li>
            </ul>

        <?php else: ?>
            <!-- STUDENT NAVIGATION -->
            <div class="nav-section-label">Student Portal</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>/student/dashboard.php" class="sidebar-link <?= ($currentScript === 'dashboard.php') ? 'active' : '' ?>">
                        <?= render_svg_icon('ticket', 'sb-icon', 18) ?>
                        <span class="sb-text">Student Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/student/tickets.php" class="sidebar-link <?= ($currentScript === 'tickets.php') ? 'active' : '' ?>">
                        <?= render_svg_icon('shield-check', 'sb-icon', 18) ?>
                        <span class="sb-text">My Entry Passes (QR)</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/student/bookings.php" class="sidebar-link <?= ($currentScript === 'bookings.php') ? 'active' : '' ?>">
                        <?= render_svg_icon('calendar', 'sb-icon', 18) ?>
                        <span class="sb-text">Booking Records</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/student/profile.php" class="sidebar-link <?= ($currentScript === 'profile.php') ? 'active' : '' ?>">
                        <?= render_svg_icon('user', 'sb-icon', 18) ?>
                        <span class="sb-text">Profile & Security</span>
                    </a>
                </li>
            </ul>

            <div class="nav-section-label">Quick Discovery</div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= BASE_URL ?>/public/events.php" class="sidebar-link">
                        <?= render_svg_icon('search', 'sb-icon', 18) ?>
                        <span class="sb-text">Explore Upcoming Events</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>

        <div class="nav-section-label">System</div>
        <ul class="sidebar-menu">
            <li>
                <a href="<?= BASE_URL ?>/public/logout.php" class="sidebar-link text-danger">
                    <svg class="sb-icon text-danger" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                    <span class="sb-text">Sign Out</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
