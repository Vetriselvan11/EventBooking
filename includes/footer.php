<?php
/**
 * Event Booking Management System
 * Universal Footer Template
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
?>
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-col footer-col-brand">
                <div class="footer-brand-header">
                    <div class="footer-brand-logo">
                        <?= render_svg_icon('ticket', '', 20) ?>
                    </div>
                    <span class="footer-brand-title"><?= e(APP_NAME) ?></span>
                </div>
                <p class="footer-tagline">
                    The centralized academic and enterprise event discovery, registration, and attendee credentialing platform.
                </p>
                <div class="system-status-indicator">
                    <span class="status-dot status-dot-online"></span>
                    <span class="status-label">Operational — <?= date('Y') ?> Academic Year</span>
                </div>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Platform Discovery</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/public/index.php">Platform Home</a></li>
                    <li><a href="<?= BASE_URL ?>/public/events.php">Browse All Events</a></li>
                    <li><a href="<?= BASE_URL ?>/public/events.php?type=free">Free Campus Events</a></li>
                    <li><a href="<?= BASE_URL ?>/public/verify-ticket.php">Credential / Ticket Verifier</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Role Portals</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/public/login.php">Universal Sign In</a></li>
                    <li><a href="<?= BASE_URL ?>/public/register.php">Student Account Creation</a></li>
                    <li><a href="<?= BASE_URL ?>/coordinator/dashboard.php">Faculty / Coordinator Console</a></li>
                    <li><a href="<?= BASE_URL ?>/admin/dashboard.php">Administrative Operations</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-heading">Security & Support</h4>
                <ul class="footer-links">
                    <li><a href="javascript:void(0)" onclick="alert('CampusEvent Hub enforces strict role-based access control, cryptographic pass tokens, and database concurrency controls.');">Security Architecture</a></li>
                    <li><a href="javascript:void(0)" onclick="alert('Demo/Academic Mode active. For database re-installation or migrations, visit /public/install.php');">Quick Setup & Installer</a></li>
                    <li><a href="mailto:events-support@campus.edu">Academic Support Desk</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-copy">
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Production-grade Event Booking Management System. Built with PHP 8, MySQL, and Modern Vanilla CSS.
            </div>
            <div class="footer-badges">
                <span class="tech-badge">PHP 8.2+</span>
                <span class="tech-badge">MySQL PDO</span>
                <span class="tech-badge">RBAC Security</span>
            </div>
        </div>
    </div>
</footer>

<!-- Core Vanilla JavaScript Bundle -->
<script src="<?= BASE_URL ?>/public/assets/js/app.js"></script>
<?php if (isset($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= e($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
