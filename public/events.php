<?php
/**
 * CampusEvent Hub — Event Catalog & Discovery (events.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

// Read Filter Parameters
$search = trim($_GET['q'] ?? '');
$categoryId = !empty($_GET['category']) ? (int)$_GET['category'] : null;
$dateFilter = $_GET['date'] ?? 'all';
$priceFilter = $_GET['type'] ?? 'all';
$availability = $_GET['availability'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'date_asc';

// Build Dynamic SQL Query with Prepared Parameters
$where = ["e.status != 'draft'"];
$params = [];

if (!empty($search)) {
    $where[] = "(e.title LIKE ? OR e.short_description LIKE ? OR e.venue LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($categoryId) {
    $where[] = "e.category_id = ?";
    $params[] = $categoryId;
}

if ($priceFilter === 'free') {
    $where[] = "e.ticket_price = 0";
} elseif ($priceFilter === 'paid') {
    $where[] = "e.ticket_price > 0";
}

if ($availability === 'available') {
    $where[] = "e.available_seats > 0 AND e.status IN ('open', 'almost_full')";
}

if ($dateFilter === 'today') {
    $where[] = "e.event_date = CURDATE()";
} elseif ($dateFilter === 'this_week') {
    $where[] = "YEARWEEK(e.event_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($dateFilter === 'this_month') {
    $where[] = "YEAR(e.event_date) = YEAR(CURDATE()) AND MONTH(e.event_date) = MONTH(CURDATE())";
} elseif ($dateFilter === 'upcoming') {
    $where[] = "e.event_date >= CURDATE()";
}

// Determine ORDER BY
$orderBy = "e.event_date ASC, e.start_time ASC";
if ($sortBy === 'date_desc') {
    $orderBy = "e.event_date DESC";
} elseif ($sortBy === 'price_asc') {
    $orderBy = "e.ticket_price ASC, e.event_date ASC";
} elseif ($sortBy === 'price_desc') {
    $orderBy = "e.ticket_price DESC, e.event_date ASC";
} elseif ($sortBy === 'title_asc') {
    $orderBy = "e.title ASC";
}

$sql = "
    SELECT e.*, c.name as category_name, u.name as coordinator_name
    FROM events e
    JOIN event_categories c ON e.category_id = c.id
    LEFT JOIN users u ON e.coordinator_id = u.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY {$orderBy}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Fetch Categories for Filter Dropdown
$categories = $pdo->query("SELECT id, name FROM event_categories ORDER BY name ASC")->fetchAll();

$pageTitle = 'Discover Campus Events — ' . APP_NAME;
require_once APP_ROOT . '/includes/header.php';
?>

<main class="main-content">
    <div class="container">
        
        <!-- Page Header -->
        <div class="dashboard-page-header">
            <div class="dashboard-header-title">
                <h1>Campus Event Catalog</h1>
                <p>Explore technical symposiums, research conferences, networking meetups, and athletic championships.</p>
            </div>
            <div class="dashboard-header-actions">
                <span class="badge badge-neutral" style="font-size:0.85rem; padding:6px 12px;">
                    <strong><?= count($events) ?></strong> Events Matching
                </span>
            </div>
        </div>

        <!-- Filter & Search Control Panel -->
        <form method="GET" action="events.php" class="filter-toolbar">
            <div class="filter-group-left">
                <!-- Search Input -->
                <div class="search-input-wrapper">
                    <span class="search-input-icon"><?= render_svg_icon('search', '', 18) ?></span>
                    <input type="text" name="q" class="form-control search-input-field" placeholder="Search event title, speaker, or venue..." value="<?= e($search) ?>">
                </div>

                <!-- Category Select -->
                <div style="min-width: 180px;">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($categoryId == $cat['id']) ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Filter -->
                <div style="min-width: 150px;">
                    <select name="date" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($dateFilter === 'all') ? 'selected' : '' ?>>Any Date</option>
                        <option value="upcoming" <?= ($dateFilter === 'upcoming') ? 'selected' : '' ?>>Upcoming Only</option>
                        <option value="today" <?= ($dateFilter === 'today') ? 'selected' : '' ?>>Today</option>
                        <option value="this_week" <?= ($dateFilter === 'this_week') ? 'selected' : '' ?>>This Week</option>
                        <option value="this_month" <?= ($dateFilter === 'this_month') ? 'selected' : '' ?>>This Month</option>
                    </select>
                </div>

                <!-- Price Filter -->
                <div style="min-width: 130px;">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= ($priceFilter === 'all') ? 'selected' : '' ?>>All Prices</option>
                        <option value="free" <?= ($priceFilter === 'free') ? 'selected' : '' ?>>Free Only</option>
                        <option value="paid" <?= ($priceFilter === 'paid') ? 'selected' : '' ?>>Paid Only</option>
                    </select>
                </div>

                <!-- Sort By -->
                <div style="min-width: 160px;">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="date_asc" <?= ($sortBy === 'date_asc') ? 'selected' : '' ?>>Date: Earliest First</option>
                        <option value="date_desc" <?= ($sortBy === 'date_desc') ? 'selected' : '' ?>>Date: Latest First</option>
                        <option value="price_asc" <?= ($sortBy === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= ($sortBy === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="title_asc" <?= ($sortBy === 'title_asc') ? 'selected' : '' ?>>Title: A to Z</option>
                    </select>
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                <?php if (!empty($search) || $categoryId || $dateFilter !== 'all' || $priceFilter !== 'all' || $sortBy !== 'date_asc'): ?>
                    <a href="events.php" class="btn btn-outline-secondary btn-sm" style="margin-left:4px;">Reset</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Events Listing Grid -->
        <?php if (!empty($events)): ?>
            <div class="events-grid">
                <?php foreach ($events as $event): 
                    $bookedCount = $event['max_capacity'] - $event['available_seats'];
                    $pct = ($event['max_capacity'] > 0) ? round(($bookedCount / $event['max_capacity']) * 100) : 0;
                    $fillClass = $pct >= 90 ? 'fill-danger' : ($pct >= 70 ? 'fill-warning' : '');
                ?>
                    <div class="event-card">
                        <div class="event-card-banner">
                            <svg width="100%" height="100%" viewBox="0 0 400 200" preserveAspectRatio="none" style="background:#1e293b;">
                                <defs>
                                    <pattern id="grid-evt-<?= $event['id'] ?>" width="24" height="24" patternUnits="userSpaceOnUse">
                                        <path d="M 24 0 L 0 0 0 24" fill="none" stroke="#334155" stroke-width="0.8"/>
                                    </pattern>
                                </defs>
                                <rect width="400" height="200" fill="url(#grid-evt-<?= $event['id'] ?>)" />
                                <text x="50%" y="52%" text-anchor="middle" fill="#93c5fd" font-size="14" font-weight="700" font-family="sans-serif">
                                    <?= e($event['category_name']) ?>
                                </text>
                            </svg>
                            <span class="event-category-badge"><?= e($event['category_name']) ?></span>
                            <span class="event-price-tag"><?= format_currency($event['ticket_price']) ?></span>
                        </div>

                        <div class="event-card-body">
                            <h3 class="event-card-title">
                                <a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $event['id'] ?>"><?= e($event['title']) ?></a>
                            </h3>
                            <p class="event-card-desc"><?= e($event['short_description']) ?></p>

                            <ul class="event-meta-list">
                                <li class="event-meta-item">
                                    <?= render_svg_icon('calendar', '', 16) ?>
                                    <span><?= format_date($event['event_date']) ?> · <?= format_time($event['start_time']) ?></span>
                                </li>
                                <li class="event-meta-item">
                                    <?= render_svg_icon('location', '', 16) ?>
                                    <span><?= e($event['venue']) ?></span>
                                </li>
                                <?php if (!empty($event['coordinator_name'])): ?>
                                    <li class="event-meta-item">
                                        <?= render_svg_icon('user', '', 16) ?>
                                        <span>Coord: <?= e($event['coordinator_name']) ?></span>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <div class="event-capacity-bar-wrap">
                                <div class="capacity-labels">
                                    <span>Capacity (<?= $pct ?>% booked)</span>
                                    <span><strong><?= (int)$event['available_seats'] ?></strong> / <?= (int)$event['max_capacity'] ?> seats</span>
                                </div>
                                <div class="capacity-progress">
                                    <div class="capacity-fill <?= $fillClass ?>" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </div>

                            <div class="event-card-footer">
                                <?= event_status_badge($event['status']) ?>
                                <a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">
                                    View Details &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= render_svg_icon('search', 'empty-state-icon', 48) ?>
                <h3 class="empty-state-title">No matching events found</h3>
                <p class="empty-state-text">
                    We could not find any events matching your search criteria. Try modifying your search keywords or clearing active filters.
                </p>
                <a href="events.php" class="btn btn-outline-primary btn-sm">Clear All Filters</a>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
