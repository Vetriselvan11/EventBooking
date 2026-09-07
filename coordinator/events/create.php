<?php
/**
 * CampusEvent Hub — Create Event (coordinator/events/create.php)
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
$error = null;

// Fetch Categories
$categories = $pdo->query("SELECT id, name FROM event_categories ORDER BY name ASC")->fetchAll();

$formData = [
    'category_id' => '',
    'title' => '',
    'short_description' => '',
    'description' => '',
    'event_date' => date('Y-m-d', strtotime('+7 days')),
    'start_time' => '09:00',
    'end_time' => '17:00',
    'venue' => '',
    'venue_address' => '',
    'ticket_price' => '0.00',
    'max_capacity' => '100',
    'registration_deadline' => date('Y-m-d H:i', strtotime('+5 days 18:00')),
    'status' => 'open',
    'is_featured' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $formData['category_id'] = (int)($_POST['category_id'] ?? 0);
    $formData['title'] = trim($_POST['title'] ?? '');
    $formData['short_description'] = trim($_POST['short_description'] ?? '');
    $formData['description'] = trim($_POST['description'] ?? '');
    $formData['event_date'] = trim($_POST['event_date'] ?? '');
    $formData['start_time'] = trim($_POST['start_time'] ?? '');
    $formData['end_time'] = trim($_POST['end_time'] ?? '');
    $formData['venue'] = trim($_POST['venue'] ?? '');
    $formData['venue_address'] = trim($_POST['venue_address'] ?? '');
    $formData['ticket_price'] = floatval($_POST['ticket_price'] ?? 0);
    $formData['max_capacity'] = (int)($_POST['max_capacity'] ?? 0);
    $formData['registration_deadline'] = trim($_POST['registration_deadline'] ?? '');
    $formData['status'] = $_POST['status'] ?? 'open';
    $formData['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;

    // Validation
    if ($formData['category_id'] <= 0) {
        $error = 'Please select a valid event category.';
    } elseif (empty($formData['title']) || strlen($formData['title']) < 3) {
        $error = 'Event title must be at least 3 characters long.';
    } elseif (empty($formData['short_description'])) {
        $error = 'Short summary description is required.';
    } elseif (empty($formData['description'])) {
        $error = 'Comprehensive event details and agenda are required.';
    } elseif (!validate_date($formData['event_date'])) {
        $error = 'Please specify a valid event date.';
    } elseif ($formData['max_capacity'] <= 0) {
        $error = 'Maximum seating capacity must be at least 1.';
    } else {
        try {
            $slug = slugify($formData['title']);
            
            // Ensure unique slug
            $checkSlug = $pdo->prepare("SELECT id FROM events WHERE slug = ? LIMIT 1");
            $checkSlug->execute([$slug]);
            if ($checkSlug->fetch()) {
                $slug .= '-' . time();
            }

            $stmt = $pdo->prepare("
                INSERT INTO events (
                    category_id, coordinator_id, title, slug, short_description, description,
                    event_date, start_time, end_time, venue, venue_address, ticket_price,
                    max_capacity, available_seats, registration_deadline, status, is_featured,
                    created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    NOW(), NOW()
                )
            ");

            $stmt->execute([
                $formData['category_id'],
                $currentUser['id'],
                $formData['title'],
                $slug,
                $formData['short_description'],
                $formData['description'],
                $formData['event_date'],
                $formData['start_time'],
                $formData['end_time'],
                $formData['venue'],
                $formData['venue_address'] ?: null,
                $formData['ticket_price'],
                $formData['max_capacity'],
                $formData['max_capacity'], // available_seats starts at max_capacity
                $formData['registration_deadline'],
                $formData['status'],
                $formData['is_featured']
            ]);

            $newEventId = (int)$pdo->lastInsertId();
            log_audit($currentUser['id'], 'EVENT_CREATE', "Created event #{$newEventId}: {$formData['title']}");
            setFlash('success', 'Event published successfully!');

            if ($currentUser['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/events/index.php');
            } else {
                header('Location: ' . BASE_URL . '/coordinator/events/index.php');
            }
            exit;

        } catch (PDOException $e) {
            $error = 'Database insert failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Create Department Event — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Create New Campus Event</h1>
                <p>Configure event schedule, venue specs, seat limits, and ticket tiering.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body" style="padding:28px;">
                <form method="POST" action="create.php">
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <div class="form-group" style="flex:2;">
                            <label class="form-label form-label-required">Event Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Annual Autonomous Robotics Symposium 2026" value="<?= e($formData['title']) ?>" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label class="form-label form-label-required">Discipline / Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($formData['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= e($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">Short Summary / Hook (1-2 sentences)</label>
                        <input type="text" name="short_description" class="form-control" placeholder="A concise summary displayed on discovery cards..." value="<?= e($formData['short_description']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">Comprehensive Event Description & Agenda</label>
                        <textarea name="description" class="form-textarea" rows="6" placeholder="Full schedule, speakers, prerequisites, and attendance guidelines..." required><?= e($formData['description']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Event Date</label>
                            <input type="date" name="event_date" class="form-control" value="<?= e($formData['event_date']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="<?= e($formData['start_time']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">End Time</label>
                            <input type="time" name="end_time" class="form-control" value="<?= e($formData['end_time']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Venue / Hall Name</label>
                            <input type="text" name="venue" class="form-control" placeholder="e.g. Turing Hall Auditorium" value="<?= e($formData['venue']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Physical Campus Address / Gate Instructions</label>
                            <input type="text" name="venue_address" class="form-control" placeholder="e.g. Science Quad, North Gate 3" value="<?= e($formData['venue_address']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Ticket Price ($ - 0 for Free)</label>
                            <input type="number" step="0.01" min="0" name="ticket_price" class="form-control" value="<?= e($formData['ticket_price']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">Maximum Seating Capacity</label>
                            <input type="number" min="1" name="max_capacity" class="form-control" value="<?= e($formData['max_capacity']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">Registration Cutoff Deadline</label>
                            <input type="datetime-local" name="registration_deadline" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($formData['registration_deadline'])) ?>" required>
                        </div>
                    </div>

                    <div class="form-row" style="align-items:center; margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Publication Status</label>
                            <select name="status" class="form-select">
                                <option value="open" <?= ($formData['status'] === 'open') ? 'selected' : '' ?>>Open for Booking</option>
                                <option value="draft" <?= ($formData['status'] === 'draft') ? 'selected' : '' ?>>Draft (Hidden)</option>
                                <option value="almost_full" <?= ($formData['status'] === 'almost_full') ? 'selected' : '' ?>>Almost Full</option>
                                <option value="full" <?= ($formData['status'] === 'full') ? 'selected' : '' ?>>Full / Sold Out</option>
                            </select>
                        </div>
                        <div class="form-group" style="padding-top:24px;">
                            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:0.9rem;">
                                <input type="checkbox" name="is_featured" value="1" <?= $formData['is_featured'] ? 'checked' : '' ?>>
                                Pin as Featured Event on Landing Page
                            </label>
                        </div>
                    </div>

                    <div style="display:flex; gap:12px; margin-top:24px; padding-top:18px; border-top:1px solid var(--border-subtle);">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <?= render_svg_icon('check-circle', '', 18) ?> Publish Event
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
