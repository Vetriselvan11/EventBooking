<?php
/**
 * CampusEvent Hub — Edit Event (coordinator/events/edit.php)
 */

define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/validation.php';

require_role(['coordinator', 'admin']);

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$eventId = (int)($_GET['id'] ?? 0);

if ($eventId <= 0) {
    header('Location: index.php');
    exit;
}

// Fetch existing event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND (coordinator_id = ? OR ? = 'admin') LIMIT 1");
$stmt->execute([$eventId, $currentUser['id'], $currentUser['role']]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('danger', 'Event not found or access unauthorized.');
    header('Location: index.php');
    exit;
}

$categories = $pdo->query("SELECT id, name FROM event_categories ORDER BY name ASC")->fetchAll();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $categoryId = (int)($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $shortDesc = trim($_POST['short_description'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime = trim($_POST['end_time'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $venueAddress = trim($_POST['venue_address'] ?? '');
    $ticketPrice = floatval($_POST['ticket_price'] ?? 0);
    $maxCapacity = (int)($_POST['max_capacity'] ?? 0);
    $deadline = trim($_POST['registration_deadline'] ?? '');
    $status = $_POST['status'] ?? 'open';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if ($categoryId <= 0) {
        $error = 'Please select a valid category.';
    } elseif (empty($title)) {
        $error = 'Title is required.';
    } elseif ($maxCapacity <= 0) {
        $error = 'Max capacity must be at least 1.';
    } else {
        try {
            // Recalculate available seats based on existing bookings
            $bookedStmt = $pdo->prepare("
                SELECT COUNT(a.id) 
                FROM booking_attendees a 
                JOIN bookings b ON a.booking_id = b.id 
                WHERE b.event_id = ? AND b.status = 'confirmed'
            ");
            $bookedStmt->execute([$eventId]);
            $bookedCount = (int)$bookedStmt->fetchColumn();

            $newAvailable = max(0, $maxCapacity - $bookedCount);

            $update = $pdo->prepare("
                UPDATE events SET
                    category_id = ?, title = ?, short_description = ?, description = ?,
                    event_date = ?, start_time = ?, end_time = ?, venue = ?, venue_address = ?,
                    ticket_price = ?, max_capacity = ?, available_seats = ?,
                    registration_deadline = ?, status = ?, is_featured = ?, updated_at = NOW()
                WHERE id = ?
            ");

            $update->execute([
                $categoryId, $title, $shortDesc, $desc,
                $eventDate, $startTime, $endTime, $venue, $venueAddress ?: null,
                $ticketPrice, $maxCapacity, $newAvailable,
                $deadline, $status, $isFeatured,
                $eventId
            ]);

            log_audit($currentUser['id'], 'EVENT_UPDATE', "Updated event #{$eventId} details.");
            setFlash('success', 'Event updated successfully.');

            if ($currentUser['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/events/index.php');
            } else {
                header('Location: ' . BASE_URL . '/coordinator/events/index.php');
            }
            exit;

        } catch (PDOException $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Event: ' . e($event['title']) . ' — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Edit Campus Event</h1>
                <p>Modify schedule details, manage capacity bounds, and adjust publication status.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body" style="padding:28px;">
                <form method="POST" action="edit.php?id=<?= $event['id'] ?>">
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <div class="form-group" style="flex:2;">
                            <label class="form-label form-label-required">Event Title</label>
                            <input type="text" name="title" class="form-control" value="<?= e($event['title']) ?>" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label class="form-label form-label-required">Discipline / Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($event['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">Short Summary</label>
                        <input type="text" name="short_description" class="form-control" value="<?= e($event['short_description']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">Full Description & Agenda</label>
                        <textarea name="description" class="form-textarea" rows="6" required><?= e($event['description']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Event Date</label>
                            <input type="date" name="event_date" class="form-control" value="<?= e($event['event_date']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="<?= e($event['start_time']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">End Time</label>
                            <input type="time" name="end_time" class="form-control" value="<?= e($event['end_time']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Venue / Hall</label>
                            <input type="text" name="venue" class="form-control" value="<?= e($event['venue']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Physical Campus Address</label>
                            <input type="text" name="venue_address" class="form-control" value="<?= e($event['venue_address'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Ticket Price ($)</label>
                            <input type="number" step="0.01" min="0" name="ticket_price" class="form-control" value="<?= e($event['ticket_price']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">Maximum Seating Capacity</label>
                            <input type="number" min="1" name="max_capacity" class="form-control" value="<?= e($event['max_capacity']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">Registration Deadline</label>
                            <input type="datetime-local" name="registration_deadline" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['registration_deadline'])) ?>" required>
                        </div>
                    </div>

                    <div class="form-row" style="align-items:center; margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="open" <?= ($event['status'] === 'open') ? 'selected' : '' ?>>Open for Booking</option>
                                <option value="almost_full" <?= ($event['status'] === 'almost_full') ? 'selected' : '' ?>>Almost Full</option>
                                <option value="full" <?= ($event['status'] === 'full') ? 'selected' : '' ?>>Full / Sold Out</option>
                                <option value="draft" <?= ($event['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
                                <option value="completed" <?= ($event['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= ($event['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group" style="padding-top:24px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:0.9rem;">
                                <input type="checkbox" name="is_featured" value="1" <?= $event['is_featured'] ? 'checked' : '' ?>>
                                Pin as Featured Event
                            </label>
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; margin-top:24px; padding-top:18px; border-top:1px solid var(--border-subtle);">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Save Changes
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
