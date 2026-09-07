<?php
/**
 * CampusEvent Hub — Admin Create Event (admin/events/create.php)
 */

define('APP_ROOT', dirname(dirname(__DIR__)));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/validation.php';

require_role('admin');

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$error = null;

// Fetch Categories & Faculty Coordinators
$categories = $pdo->query("SELECT id, name FROM event_categories ORDER BY name ASC")->fetchAll();
$coordinators = $pdo->query("
    SELECT u.id, u.name, c.department, c.designation 
    FROM users u 
    JOIN coordinators c ON u.id = c.user_id 
    WHERE u.role = 'coordinator' AND u.status = 'active'
    ORDER BY u.name ASC
")->fetchAll();

$formData = [
    'category_id' => '',
    'coordinator_id' => '',
    'title' => '',
    'short_description' => '',
    'description' => '',
    'event_date' => date('Y-m-d', strtotime('+10 days')),
    'start_time' => '09:00',
    'end_time' => '17:00',
    'venue' => '',
    'venue_address' => '',
    'ticket_price' => '0.00',
    'max_capacity' => '200',
    'registration_deadline' => date('Y-m-d H:i', strtotime('+8 days 18:00')),
    'status' => 'open',
    'is_featured' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $formData['category_id'] = (int)($_POST['category_id'] ?? 0);
    $formData['coordinator_id'] = !empty($_POST['coordinator_id']) ? (int)$_POST['coordinator_id'] : null;
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

    if ($formData['category_id'] <= 0) {
        $error = 'Please select an event discipline / category.';
    } elseif (empty($formData['title'])) {
        $error = 'Event title is required.';
    } elseif ($formData['max_capacity'] <= 0) {
        $error = 'Max capacity must be at least 1 seat.';
    } else {
        try {
            $slug = slugify($formData['title']);
            $check = $pdo->prepare("SELECT id FROM events WHERE slug = ? LIMIT 1");
            $check->execute([$slug]);
            if ($check->fetch()) {
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
                $formData['coordinator_id'],
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
                $formData['max_capacity'],
                $formData['registration_deadline'],
                $formData['status'],
                $formData['is_featured']
            ]);

            $newId = (int)$pdo->lastInsertId();
            log_audit($currentUser['id'], 'ADMIN_EVENT_CREATE', "Admin created event #{$newId}: {$formData['title']}");
            setFlash('success', 'Event successfully created.');
            header('Location: ' . BASE_URL . '/admin/events/index.php');
            exit;

        } catch (PDOException $e) {
            $error = 'Insert failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Admin Create Event — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Create Campus Event (Administrator)</h1>
                <p>Publish an institution-wide event and assign a designated faculty coordinator.</p>
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
                            <input type="text" name="title" class="form-control" placeholder="e.g. National Inter-Collegiate AI Championship 2026" value="<?= e($formData['title']) ?>" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label class="form-label form-label-required">Category</label>
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
                        <label class="form-label">Assigned Faculty Coordinator (Optional)</label>
                        <select name="coordinator_id" class="form-select">
                            <option value="">Campus-Wide / Unassigned</option>
                            <?php foreach ($coordinators as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($formData['coordinator_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?> (<?= e($c['department']) ?> — <?= e($c['designation']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">Short Summary</label>
                        <input type="text" name="short_description" class="form-control" value="<?= e($formData['short_description']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label form-label-required">Full Description & Agenda</label>
                        <textarea name="description" class="form-textarea" rows="6" required><?= e($formData['description']) ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Date</label>
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
                            <label class="form-label form-label-required">Venue</label>
                            <input type="text" name="venue" class="form-control" placeholder="e.g. Spartan Arena" value="<?= e($formData['venue']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Physical Campus Address</label>
                            <input type="text" name="venue_address" class="form-control" placeholder="e.g. East Campus Athletic District" value="<?= e($formData['venue_address']) ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label form-label-required">Ticket Price ($ - 0 for Free)</label>
                            <input type="number" step="0.01" min="0" name="ticket_price" class="form-control" value="<?= e($formData['ticket_price']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">Max Capacity (Seats)</label>
                            <input type="number" min="1" name="max_capacity" class="form-control" value="<?= e($formData['max_capacity']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label form-label-required">Registration Deadline</label>
                            <input type="datetime-local" name="registration_deadline" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($formData['registration_deadline'])) ?>" required>
                        </div>
                    </div>

                    <div class="form-row" style="align-items:center; margin-top:10px;">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="open" <?= ($formData['status'] === 'open') ? 'selected' : '' ?>>Open for Booking</option>
                                <option value="draft" <?= ($formData['status'] === 'draft') ? 'selected' : '' ?>>Draft</option>
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
