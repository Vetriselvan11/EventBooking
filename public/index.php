<?php
/**
 * CampusEvent Hub — Public Landing Page
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';

$pdo = Database::getConnection();

// Check if database needs installation
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

// Fetch Categories with Event Counts
$categories = [];
try {
    $catStmt = $pdo->query("
        SELECT c.*, COUNT(e.id) as event_count
        FROM event_categories c
        LEFT JOIN events e ON c.id = e.category_id AND e.status IN ('open', 'almost_full')
        GROUP BY c.id
        ORDER BY c.id ASC
    ");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    // If table doesn't exist yet, forward to installer
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

// Fetch Featured Events
$featuredEvents = [];
try {
    $featStmt = $pdo->query("
        SELECT e.*, c.name as category_name
        FROM events e
        JOIN event_categories c ON e.category_id = c.id
        WHERE e.status IN ('open', 'almost_full')
        ORDER BY e.is_featured DESC, e.event_date ASC
        LIMIT 6
    ");
    $featuredEvents = $featStmt->fetchAll();
} catch (PDOException $e) {
    $featuredEvents = [];
}

// Fetch Quick Upcoming Events for Hero Sidebar
$quickUpcoming = [];
try {
    $upStmt = $pdo->query("
        SELECT id, title, slug, event_date, start_time, venue, ticket_price
        FROM events
        WHERE status IN ('open', 'almost_full') AND event_date >= CURDATE()
        ORDER BY event_date ASC
        LIMIT 3
    ");
    $quickUpcoming = $upStmt->fetchAll();
} catch (PDOException $e) {
    $quickUpcoming = [];
}

// Platform Stats
$stats = [
    'events_count' => 0,
    'students_count' => 0,
    'bookings_count' => 0,
    'categories_count' => count($categories)
];
try {
    $stats['events_count'] = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status != 'draft'")->fetchColumn();
    $stats['students_count'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $stats['bookings_count'] = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
} catch (PDOException $e) {}

$pageTitle = APP_NAME . ' — University & Enterprise Event Platform';
$isLanding = true;
require_once APP_ROOT . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-content">
                <div class="hero-kicker">
                    <?= render_svg_icon('shield-check', '', 16) ?> Verified Academic & Enterprise Platform
                </div>
                <h1 class="hero-title">
                    Discover, Book & Attend <span class="hero-highlight">Campus Events</span> with Ease.
                </h1>
                <p class="hero-description">
                    The centralized management system for university symposiums, hackathons, academic conferences, athletic tournaments, and career fairs. Secure instant registration with digital QR passes.
                </p>
                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-primary btn-lg">
                        Explore Events <?= render_svg_icon('arrow-right', '', 18) ?>
                    </a>
                    <a href="<?= BASE_URL ?>/public/register.php" class="btn btn-outline-secondary btn-lg" style="color:#ffffff; border-color:rgba(255,255,255,0.3);">
                        Student Registration
                    </a>
                </div>
            </div>

            <!-- Hero Upcoming Preview Panel -->
            <div class="hero-stats-panel">
                <h3 class="hero-panel-title">Happening Soon On Campus</h3>
                <?php if (!empty($quickUpcoming)): ?>
                    <?php foreach ($quickUpcoming as $ev): ?>
                        <div class="hero-quick-event-item">
                            <div class="hero-event-date-badge">
                                <span class="hero-event-date-month"><?= date('M', strtotime($ev['event_date'])) ?></span>
                                <span class="hero-event-date-day"><?= date('d', strtotime($ev['event_date'])) ?></span>
                            </div>
                            <div class="hero-quick-event-details">
                                <h5><a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $ev['id'] ?>"><?= e($ev['title']) ?></a></h5>
                                <div class="hero-quick-event-meta">
                                    <?= render_svg_icon('location', '', 14) ?> <?= e($ev['venue']) ?> · 
                                    <strong><?= format_currency($ev['ticket_price']) ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#94a3b8; font-size:0.88rem; margin:0;">No upcoming events scheduled at this moment.</p>
                <?php endif; ?>
                <div style="margin-top:16px; text-align:right;">
                    <a href="<?= BASE_URL ?>/public/events.php" style="color:#60a5fa; font-size:0.85rem; font-weight:600;">
                        View All Upcoming &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Landing Content -->
<main class="main-content">
    <div class="container">

        <!-- Category Grid Section -->
        <div class="section-header-wrap">
            <div>
                <h2 class="section-title">Explore by Domain</h2>
                <p class="section-subtitle">Find symposiums, networking sessions, and competitions across disciplines</p>
            </div>
            <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-outline-primary btn-sm">All Categories</a>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= BASE_URL ?>/public/events.php?category=<?= $cat['id'] ?>" class="category-card">
                    <div class="category-icon-box">
                        <?= render_svg_icon('tag', '', 20) ?>
                    </div>
                    <div class="category-name"><?= e($cat['name']) ?></div>
                    <div class="category-count"><?= (int)$cat['event_count'] ?> Active Events</div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Featured Events Grid -->
        <div class="section-header-wrap" style="margin-top:40px;">
            <div>
                <h2 class="section-title">Featured Campus Events</h2>
                <p class="section-subtitle">High-impact workshops, guest lectures, and student competitions</p>
            </div>
            <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-outline-primary btn-sm">View Full Catalog</a>
        </div>

        <?php if (!empty($featuredEvents)): ?>
            <div class="events-grid">
                <?php foreach ($featuredEvents as $event): 
                    $bookedCount = $event['max_capacity'] - $event['available_seats'];
                    $pct = ($event['max_capacity'] > 0) ? round(($bookedCount / $event['max_capacity']) * 100) : 0;
                    $fillClass = $pct >= 90 ? 'fill-danger' : ($pct >= 70 ? 'fill-warning' : '');
                ?>
                    <div class="event-card">
                        <div class="event-card-banner">
                            <!-- High contrast architectural SVG placeholder banner -->
                            <svg width="100%" height="100%" viewBox="0 0 400 200" preserveAspectRatio="none" style="background:#1e293b;">
                                <defs>
                                    <pattern id="grid-<?= $event['id'] ?>" width="20" height="20" patternUnits="userSpaceOnUse">
                                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#334155" stroke-width="0.8"/>
                                    </pattern>
                                </defs>
                                <rect width="400" height="200" fill="url(#grid-<?= $event['id'] ?>)" />
                                <circle cx="200" cy="100" r="45" fill="rgba(30, 64, 175, 0.3)" />
                                <text x="50%" y="54%" text-anchor="middle" fill="#93c5fd" font-size="14" font-weight="700" font-family="sans-serif">
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
                            </ul>

                            <div class="event-capacity-bar-wrap">
                                <div class="capacity-labels">
                                    <span>Capacity</span>
                                    <span><strong><?= (int)$event['available_seats'] ?></strong> seats left</span>
                                </div>
                                <div class="capacity-progress">
                                    <div class="capacity-fill <?= $fillClass ?>" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </div>

                            <div class="event-card-footer">
                                <?= event_status_badge($event['status']) ?>
                                <a href="<?= BASE_URL ?>/public/event-details.php?id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">
                                    View & Book
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= render_svg_icon('calendar', 'empty-state-icon', 48) ?>
                <h3 class="empty-state-title">No events published yet</h3>
                <p class="empty-state-text">Check back soon for new campus conferences, hackathons, and seminars.</p>
            </div>
        <?php endif; ?>

        <!-- How It Works Timeline -->
        <div class="workflow-section">
            <div style="text-align:center; max-width:600px; margin:0 auto 36px auto;">
                <h2 class="section-title">How CampusEvent Hub Works</h2>
                <p class="section-subtitle">A seamless 3-step workflow from discovery to entrance check-in</p>
            </div>

            <div class="workflow-grid">
                <div class="workflow-card">
                    <div class="workflow-step-num">1</div>
                    <h3 class="workflow-card-title">Discover & Select</h3>
                    <p class="workflow-card-text">
                        Browse active academic symposiums, tech hackathons, or workshops with verified seat availability.
                    </p>
                </div>

                <div class="workflow-card">
                    <div class="workflow-step-num">2</div>
                    <h3 class="workflow-card-title">Instant Registration</h3>
                    <p class="workflow-card-text">
                        Authenticate with your student/faculty ID, specify attendee details, and complete zero-friction checkout.
                    </p>
                </div>

                <div class="workflow-card">
                    <div class="workflow-step-num">3</div>
                    <h3 class="workflow-card-title">Digital Entry Pass</h3>
                    <p class="workflow-card-text">
                        Receive instant cryptographic digital passes with QR verification for rapid gate check-in at the venue.
                    </p>
                </div>
            </div>
        </div>

        <!-- Platform Statistics Strip -->
        <div class="stats-strip">
            <div class="container">
                <div class="stats-grid">
                    <div>
                        <div class="stat-metric-num"><?= $stats['events_count'] ?>+</div>
                        <div class="stat-metric-label">Published Events</div>
                    </div>
                    <div>
                        <div class="stat-metric-num"><?= $stats['students_count'] ?>+</div>
                        <div class="stat-metric-label">Registered Students</div>
                    </div>
                    <div>
                        <div class="stat-metric-num"><?= $stats['bookings_count'] ?>+</div>
                        <div class="stat-metric-label">Confirmed Bookings</div>
                    </div>
                    <div>
                        <div class="stat-metric-num"><?= $stats['categories_count'] ?></div>
                        <div class="stat-metric-label">Academic Disciplines</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Banner -->
        <div class="cta-banner">
            <h2>Organizing an Academic or Campus Event?</h2>
            <p>
                Faculty members, student club chairs, and department coordinators can request event creation privileges to manage rosters and track live entrance check-ins.
            </p>
            <div style="display:flex; justify-content:center; gap:14px; flex-wrap:wrap;">
                <a href="<?= BASE_URL ?>/public/login.php" class="btn btn-accent btn-lg">
                    Coordinator Sign In <?= render_svg_icon('arrow-right', '', 18) ?>
                </a>
                <a href="<?= BASE_URL ?>/public/events.php" class="btn btn-outline-secondary btn-lg" style="color:#ffffff; border-color:rgba(255,255,255,0.3);">
                    Browse Event Directory
                </a>
            </div>
        </div>

    </div>
</main>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
