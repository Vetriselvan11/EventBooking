<?php
/**
 * CampusEvent Hub — Discipline & Category Management (admin/categories/index.php)
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
$success = null;

// Handle Add / Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            $error = 'Category name is required.';
        } else {
            $slug = slugify($name);
            try {
                $stmt = $pdo->prepare("INSERT INTO event_categories (name, slug, description, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$name, $slug, $description ?: null]);

                log_audit($currentUser['id'], 'CATEGORY_CREATE', "Created category {$name}");
                $success = "Category '{$name}' created successfully.";
            } catch (PDOException $e) {
                $error = 'Category with this name or slug already exists.';
            }
        }
    } elseif ($action === 'delete_category') {
        $catId = (int)$_POST['category_id'];
        
        // Check if category has events
        $chk = $pdo->prepare("SELECT COUNT(*) FROM events WHERE category_id = ?");
        $chk->execute([$catId]);
        $count = (int)$chk->fetchColumn();

        if ($count > 0) {
            $error = "Cannot delete this category because {$count} event(s) are currently categorized under it.";
        } else {
            $del = $pdo->prepare("DELETE FROM event_categories WHERE id = ?");
            $del->execute([$catId]);
            log_audit($currentUser['id'], 'CATEGORY_DELETE', "Deleted category ID {$catId}");
            $success = "Category deleted successfully.";
        }
    }
}

// Fetch all categories with event counts
$categories = $pdo->query("
    SELECT c.*, COUNT(e.id) as event_count
    FROM event_categories c
    LEFT JOIN events e ON c.id = e.category_id
    GROUP BY c.id
    ORDER BY c.name ASC
")->fetchAll();

$pageTitle = 'Event Categories — ' . APP_NAME;
$isDashboard = true;
require_once APP_ROOT . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once APP_ROOT . '/includes/sidebar.php'; ?>

    <main class="dashboard-workspace">
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Academic Disciplines & Categories</h1>
                <p>Organize campus events into structured disciplines and catalog categories.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($success) ?></div>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns: 1fr 1.6fr; gap:28px; align-items:start;">
            
            <!-- Left: Add Category Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add New Category</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_category">

                        <div class="form-group">
                            <label class="form-label form-label-required">Category Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Biomedical & Life Sciences" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="description" class="form-textarea" rows="3" placeholder="Brief summary of event domain..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <?= render_svg_icon('plus', '', 16) ?> Create Category
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Categories Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Existing Categories (<?= count($categories) ?>)</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Slug</th>
                                    <th>Events</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($cat['name']) ?></strong>
                                            <?php if (!empty($cat['description'])): ?>
                                                <div style="font-size:0.75rem; color:var(--text-muted);"><?= e($cat['description']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?= e($cat['slug']) ?></code></td>
                                        <td>
                                            <span class="badge badge-neutral"><?= (int)$cat['event_count'] ?> events</span>
                                        </td>
                                        <td style="text-align:right;">
                                            <?php if ((int)$cat['event_count'] === 0): ?>
                                                <form method="POST" action="index.php" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Are you sure you want to delete this category?">
                                                        Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span style="font-size:0.75rem; color:var(--text-muted);">In use</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
