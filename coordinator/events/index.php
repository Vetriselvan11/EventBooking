<?php
/**
 * CampusEvent Hub — Coordinator Events List (coordinator/events/index.php)
 */

define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

require_role(['coordinator', 'admin']);

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$coordId = $currentUser['id'];

// Fetch Events
$stmt = $pdo->prepare("
    SELECT e.*, c.name as category_name,
           (SELECT COUNT(a.id) FROM booking_attendees a JOIN bookings b ON a.booking_id = b.id WHERE b.event_id = e.id AND b.status = 'confirmed') as booked_attendees
    FROM events e
    JOIN event_categories c ON e.category_id = c.id
    WHERE e.coordinator_id = ? OR ? = 'admin'
    ORDER BY e.event_date ASC
");
$stmt->execute([$coordId, $currentUser['role']]);
$events = $stmt->fetchAll();

$pageTitle = 'Department Events — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Department Event Management</h1>
                <p>Manage event details, monitor real-time capacity, and inspect attendee rosters.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="<?= BASE_URL ?>/coordinator/events/create.php" class="btn btn-primary btn-sm">
                    <?= render_svg_icon('plus', '', 16) ?> Add New Event
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (!empty($events)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Category</th>
                                    <th>Date & Time</th>
                                    <th>Venue</th>
                                    <th>Seats Sold / Capacity</th>
                                    <th>Ticket Price</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $ev): 
                                    $booked = (int)$ev['booked_attendees'];
                                    $max = (int)$ev['max_capacity'];
                                    $pct = ($max > 0) ? round(($booked / $max) * 100) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $ev['id'] ?>" target="_blank"><?= e($ev['title']) ?></a></strong>
                                        </td>
                                        <td><span class="badge badge-secondary"><?= e($ev['category_name']) ?></span></td>
                                        <td>
                                            <div><?= format_date($ev['event_date']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= format_time($ev['start_time']) ?></div>
                                        </td>
                                        <td><?= e($ev['venue']) ?></td>
                                        <td>
                                            <span class="num-tabular"><strong><?= $booked ?></strong> / <?= $max ?> (<?= $pct ?>%)</span>
                                        </td>
                                        <td class="td-price" style="font-weight:700;"><?= format_currency($ev['ticket_price']) ?></td>
                                        <td><?= event_status_badge($ev['status']) ?></td>
                                        <td style="text-align:right;">
                                            <div class="table-actions" style="justify-content:flex-end;">
                                                <a href="<?= BASE_URL ?>/coordinator/attendees/index.php?event_id=<?= $ev['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    Roster
                                                </a>
                                                <a href="<?= BASE_URL ?>/coordinator/events/edit.php?id=<?= $ev['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                                    Edit
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" style="border:none;">
                        <h4 class="empty-state-title">No events found</h4>
                        <p class="empty-state-text">Click the button below to publish your department's first event.</p>
                        <a href="<?= BASE_URL ?>/coordinator/events/create.php" class="btn btn-primary btn-sm">Create Event</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
