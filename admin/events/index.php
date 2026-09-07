<?php
/**
 * CampusEvent Hub — Administrator Event Management (admin/events/index.php)
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

$search = trim($_GET['q'] ?? '');
$categoryId = (int)($_GET['category'] ?? 0);
$statusFilter = $_GET['status'] ?? 'all';

$where = ["1=1"];
$params = [];

if (!empty($search)) {
    $where[] = "(e.title LIKE ? OR e.venue LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
}

if ($categoryId > 0) {
    $where[] = "e.category_id = ?";
    $params[] = $categoryId;
}

if ($statusFilter !== 'all') {
    $where[] = "e.status = ?";
    $params[] = $statusFilter;
}

$sql = "
    SELECT e.*, 
           c.name as category_name,
           u.name as coordinator_name,
           (SELECT COUNT(a.id) FROM booking_attendees a JOIN bookings b ON a.booking_id = b.id WHERE b.event_id = e.id AND b.status = 'confirmed') as booked_attendees
    FROM events e
    JOIN event_categories c ON e.category_id = c.id
    LEFT JOIN users u ON e.coordinator_id = u.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY e.event_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

$categories = $pdo->query("SELECT id, name FROM event_categories ORDER BY name ASC")->fetchAll();

$pageTitle = 'Manage Campus Events — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Campus Event Administration</h1>
                <p>Global management of all university events, faculty coordinator assignments, and seat capacities.</p>
            </div>
            <div class="dashboard-header-actions">
                <a href="<?= BASE_URL ?>/admin/events/create.php" class="btn btn-primary btn-sm">
                    <?= render_svg_icon('plus', '', 16) ?> Create New Event
                </a>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <form method="GET" action="index.php" class="filter-toolbar">
            <div class="filter-group-left">
                <div class="search-input-wrapper">
                    <span class="search-input-icon"><?= render_svg_icon('search', '', 16) ?></span>
                    <input type="text" name="q" class="form-control search-input-field" placeholder="Search event title or venue..." value="<?= e($search) ?>">
                </div>

                <div style="min-width:180px;">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="0">All Disciplines</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="min-width:150px;">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($statusFilter === 'all') ? 'selected' : '' ?>>All Statuses</option>
                        <option value="open" <?= ($statusFilter === 'open') ? 'selected' : '' ?>>Open</option>
                        <option value="almost_full" <?= ($statusFilter === 'almost_full') ? 'selected' : '' ?>>Almost Full</option>
                        <option value="full" <?= ($statusFilter === 'full') ? 'selected' : '' ?>>Sold Out</option>
                        <option value="draft" <?= ($statusFilter === 'draft') ? 'selected' : '' ?>>Draft</option>
                        <option value="completed" <?= ($statusFilter === 'completed') ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= ($statusFilter === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>

            <div style="display:flex; align-items:center; gap:8px;">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                <?php if (!empty($search) || $categoryId > 0 || $statusFilter !== 'all'): ?>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Events Data Table -->
        <div class="card">
            <div class="card-body" style="padding:0;">
                <?php if (!empty($events)): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event Title</th>
                                    <th>Category</th>
                                    <th>Faculty Coordinator</th>
                                    <th>Schedule</th>
                                    <th>Capacity</th>
                                    <th>Price</th>
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
                                            <div style="font-size:0.75rem; color:var(--text-muted);"><?= e($ev['venue']) ?></div>
                                        </td>
                                        <td><span class="badge badge-secondary"><?= e($ev['category_name']) ?></span></td>
                                        <td><?= e($ev['coordinator_name'] ?? 'Unassigned (Campus)') ?></td>
                                        <td>
                                            <div style="font-weight:600;"><?= format_date($ev['event_date']) ?></div>
                                            <div style="font-size:0.78rem; color:var(--text-muted);"><?= format_time($ev['start_time']) ?></div>
                                        </td>
                                        <td>
                                            <span class="num-tabular"><strong><?= $booked ?></strong> / <?= $max ?> (<?= $pct ?>%)</span>
                                        </td>
                                        <td class="td-price" style="font-weight:700;"><?= format_currency($ev['ticket_price']) ?></td>
                                        <td><?= event_status_badge($ev['status']) ?></td>
                                        <td style="text-align:right;">
                                            <div class="table-actions" style="justify-content:flex-end;">
                                                <a href="<?= BASE_URL ?>/admin/events/edit.php?id=<?= $ev['id'] ?>" class="btn btn-outline-secondary btn-sm" title="Edit event">
                                                    Edit
                                                </a>
                                                <a href="<?= BASE_URL ?>/admin/events/delete.php?id=<?= $ev['id'] ?>" class="btn btn-outline-danger btn-sm" data-confirm="Are you sure you want to cancel or delete this event? This will affect attendee bookings." title="Delete / Cancel">
                                                    Cancel
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
                        <p class="empty-state-text">No events match the current filter criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
