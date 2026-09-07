<?php
/**
 * CampusEvent Hub — Admin Faculty Coordinators (admin/coordinators/index.php)
 */

define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

require_role('admin');

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();

// Fetch Coordinators
$stmt = $pdo->query("
    SELECT u.*, c.department, c.designation, c.office_location,
           (SELECT COUNT(id) FROM events WHERE coordinator_id = u.id) as assigned_events,
           (SELECT COUNT(a.id) FROM booking_attendees a JOIN bookings b ON a.booking_id = b.id JOIN events e ON b.event_id = e.id WHERE e.coordinator_id = u.id AND b.status = 'confirmed') as total_attendees
    FROM users u
    JOIN coordinators c ON u.id = c.user_id
    WHERE u.role = 'coordinator'
    ORDER BY u.name ASC
");
$coordinators = $stmt->fetchAll();

$pageTitle = 'Faculty Coordinators — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Faculty & Department Coordinators</h1>
                <p>Manage event chairpersons, faculty organizers, and department leads.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="create.php" class="btn btn-primary btn-sm">
                    <?= render_svg_icon('plus', '', 16) ?> Add New Faculty Coordinator
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (!empty($coordinators)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Faculty Member</th>
                                    <th>Department</th>
                                    <th>Designation</th>
                                    <th>Office Location</th>
                                    <th>Assigned Events</th>
                                    <th>Total Attendees</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($coordinators as $coord): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:700; color:var(--text-primary);"><?= e($coord['name']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= e($coord['email']) ?></div>
                                        </td>
                                        <td><?= e($coord['department']) ?></td>
                                        <td><?= e($coord['designation']) ?></td>
                                        <td><?= e($coord['office_location'] ?? 'Campus Grounds') ?></td>
                                        <td><strong><?= (int)$coord['assigned_events'] ?></strong> events</td>
                                        <td><span class="badge badge-success"><?= (int)$coord['total_attendees'] ?> attendees</span></td>
                                        <td>
                                            <span class="badge <?= ($coord['status'] === 'active') ? 'badge-success' : 'badge-danger' ?>">
                                                <?= ucfirst(e($coord['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border:none;">
                        <h4 class="empty-state-title">No coordinators provisioned</h4>
                        <p class="empty-state-text">Create your first faculty coordinator account.</p>
                        <a href="create.php" class="btn btn-primary btn-sm">Add Coordinator</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
