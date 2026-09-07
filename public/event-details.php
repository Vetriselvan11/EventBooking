<?php
/**
 * CampusEvent Hub — Event Details Page (event-details.php)
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

$eventId = (int)($_GET['id'] ?? 0);
if ($eventId <= 0) {
    header('Location: ' . BASE_URL . '/public/events.php');
    exit;
}

// Fetch Complete Event Information
$stmt = $pdo->prepare("
    SELECT e.*, 
           c.name as category_name, c.slug as category_slug,
           u.name as coordinator_name, u.email as coordinator_email, u.phone as coordinator_phone,
           coord.department as coordinator_dept, coord.designation as coordinator_desig
    FROM events e
    JOIN event_categories c ON e.category_id = c.id
    LEFT JOIN users u ON e.coordinator_id = u.id
    LEFT JOIN coordinators coord ON u.id = coord.user_id
    WHERE e.id = ? LIMIT 1
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('danger', 'The requested event could not be found.');
    header('Location: ' . BASE_URL . '/public/events.php');
    exit;
}

// Registration Eligibility Calculations
$isDeadlinePassed = strtotime($event['registration_deadline']) < time();
$isEventPassed = strtotime($event['event_date'] . ' ' . $event['end_time']) < time();
$isSoldOut = ($event['available_seats'] <= 0) || ($event['status'] === 'full');
$canBook = ($event['status'] === 'open' || $event['status'] === 'almost_full') && !$isDeadlinePassed && !$isSoldOut && !$isEventPassed;

$bookedCount = $event['max_capacity'] - $event['available_seats'];
$capacityPct = ($event['max_capacity'] > 0) ? round(($bookedCount / $event['max_capacity']) * 100) : 0;

$pageTitle = e($event['title']) . ' — ' . APP_NAME;
require_once APP_ROOT . '/includes/header.php';
?>

<main class="main-content">
    <div class="container">

        <!-- Breadcrumb Bar -->
        <div style="margin-bottom:20px; font-size:0.85rem; color:var(--text-muted);">
            <a href="<?= BASE_URL ?>/public/index.php">Home</a> &gt; 
            <a href="<?= BASE_URL ?>/public/events.php">Events</a> &gt; 
            <a href="<?= BASE_URL ?>/public/events.php?category=<?= $event['category_id'] ?>"><?= e($event['category_name']) ?></a> &gt; 
            <span style="color:var(--text-primary); font-weight:600;"><?= e($event['title']) ?></span>
        </div>

        <!-- Master Split Layout -->
        <div style="display:grid; grid-template-columns: 1.8fr 1fr; gap:36px; align-items:start;">
            
            <!-- Left Column: Main Event Dossier -->
            <div>
                <!-- Event Header Card -->
                <div class="card" style="margin-bottom:28px;">
                    <div style="height:260px; background:#1e293b; position:relative; overflow:hidden;">
                        <svg width="100%" height="100%" viewBox="0 0 800 260" preserveAspectRatio="none">
                            <defs>
                                <pattern id="det-pattern-<?= $event['id'] ?>" width="30" height="30" patternUnits="userSpaceOnUse">
                                    <path d="M 30 0 L 0 0 0 30" fill="none" stroke="#334155" stroke-width="1"/>
                                </pattern>
                            </defs>
                            <rect width="800" height="260" fill="url(#det-pattern-<?= $event['id'] ?>)" />
                            <circle cx="400" cy="130" r="80" fill="rgba(30, 64, 175, 0.25)" />
                            <text x="50%" y="54%" text-anchor="middle" fill="#93c5fd" font-size="18" font-weight="700" font-family="sans-serif">
                                <?= e($event['category_name']) ?>
                            </text>
                        </svg>
                        <div style="position:absolute; top:16px; left:16px; display:flex; gap:8px;">
                            <span class="badge badge-secondary"><?= e($event['category_name']) ?></span>
                            <?= event_status_badge($event['status']) ?>
                        </div>
                    </div>

                    <div class="card-body" style="padding:28px;">
                        <h1 style="font-size:2rem; font-weight:800; line-height:1.2; margin-bottom:12px;">
                            <?= e($event['title']) ?>
                        </h1>
                        <p style="font-size:1.05rem; color:var(--text-secondary); line-height:1.6; margin-bottom:0;">
                            <?= e($event['short_description']) ?>
                        </p>
                    </div>
                </div>

                <!-- Event Details & Agenda Card -->
                <div class="card" style="margin-bottom:28px;">
                    <div class="card-header">
                        <h3 class="card-title">Comprehensive Event Overview & Agenda</h3>
                    </div>
                    <div class="card-body" style="padding:28px;">
                        <div style="font-size:0.95rem; color:var(--text-secondary); line-height:1.7; white-space:pre-line;">
                            <?= e($event['description']) ?>
                        </div>
                    </div>
                </div>

                <!-- Coordinator & Department Contact -->
                <?php if (!empty($event['coordinator_name'])): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Faculty Event Coordinator</h3>
                        </div>
                        <div class="card-body" style="display:flex; align-items:center; gap:18px;">
                            <div class="user-avatar-badge" style="width:48px; height:48px; font-size:1.2rem;">
                                <?= strtoupper(substr($event['coordinator_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h4 style="font-size:1.05rem; margin-bottom:2px;"><?= e($event['coordinator_name']) ?></h4>
                                <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:4px;">
                                    <?= e($event['coordinator_desig'] ?? 'Faculty Coordinator') ?> · <?= e($event['coordinator_dept'] ?? 'Academic Department') ?>
                                </div>
                                <div style="font-size:0.85rem; color:var(--primary);">
                                    <?= render_svg_icon('user', '', 14) ?> Contact: <a href="mailto:<?= e($event['coordinator_email']) ?>"><?= e($event['coordinator_email']) ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Right Column: Registration & Key Facts Card (Sticky) -->
            <div style="position:sticky; top:90px;">
                <div class="card" style="border-top:4px solid var(--primary);">
                    <div class="card-body" style="padding:24px;">
                        
                        <!-- Price Display -->
                        <div style="display:flex; align-items:baseline; justify-content:space-between; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid var(--border-subtle);">
                            <span style="font-size:0.88rem; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Entry Fee</span>
                            <span style="font-size:1.8rem; font-weight:800; color:var(--primary-900);">
                                <?= format_currency($event['ticket_price']) ?>
                            </span>
                        </div>

                        <!-- Schedule Highlights -->
                        <div style="display:flex; flex-direction:column; gap:14px; margin-bottom:24px;">
                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <div style="color:var(--primary);"><?= render_svg_icon('calendar', '', 20) ?></div>
                                <div>
                                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Date & Schedule</div>
                                    <div style="font-weight:600; color:var(--text-primary);">
                                        <?= format_date($event['event_date'], 'l, F d, Y') ?>
                                    </div>
                                    <div style="font-size:0.82rem; color:var(--text-secondary);">
                                        <?= format_time($event['start_time']) ?> – <?= format_time($event['end_time']) ?>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <div style="color:var(--primary);"><?= render_svg_icon('location', '', 20) ?></div>
                                <div>
                                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Venue & Location</div>
                                    <div style="font-weight:600; color:var(--text-primary);">
                                        <?= e($event['venue']) ?>
                                    </div>
                                    <?php if (!empty($event['venue_address'])): ?>
                                        <div style="font-size:0.82rem; color:var(--text-secondary);">
                                            <?= e($event['venue_address']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="display:flex; gap:12px; align-items:flex-start;">
                                <div style="color:var(--accent);"><?= render_svg_icon('clock', '', 20) ?></div>
                                <div>
                                    <div style="font-size:0.75rem; text-transform:uppercase; color:var(--text-muted); font-weight:700;">Registration Cutoff</div>
                                    <div style="font-weight:600; color:var(--text-primary);">
                                        <?= format_datetime($event['registration_deadline']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Capacity Bar -->
                        <div style="background:#f8fafc; border:1px solid var(--border-subtle); border-radius:var(--radius-sm); padding:14px; margin-bottom:24px;">
                            <div class="capacity-labels" style="margin-bottom:6px;">
                                <span style="font-weight:600; color:var(--text-primary);">Seat Availability</span>
                                <span><strong><?= (int)$event['available_seats'] ?></strong> remaining (<?= $capacityPct ?>% filled)</span>
                            </div>
                            <div class="capacity-progress">
                                <div class="capacity-fill <?= $capacityPct >= 90 ? 'fill-danger' : ($capacityPct >= 70 ? 'fill-warning' : '') ?>" style="width: <?= $capacityPct ?>%;"></div>
                            </div>
                        </div>

                        <!-- Booking Action Trigger -->
                        <?php if ($canBook): ?>
                            <a href="<?= BASE_URL ?>/public/booking.php?event_id=<?= $event['id'] ?>" class="btn btn-primary btn-lg btn-block">
                                <?= render_svg_icon('ticket', '', 20) ?> Proceed to Registration
                            </a>
                            <div style="text-align:center; font-size:0.78rem; color:var(--text-muted); margin-top:10px;">
                                Instant digital pass generation upon confirmation
                            </div>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-lg btn-block" disabled style="opacity:0.6; cursor:not-allowed;">
                                <?php 
                                    if ($isEventPassed) echo 'Event Concluded';
                                    elseif ($isDeadlinePassed) echo 'Registration Closed';
                                    elseif ($isSoldOut) echo 'Sold Out / Full';
                                    else echo 'Booking Unavailable';
                                ?>
                            </button>
                            <div style="text-align:center; font-size:0.8rem; color:var(--danger-text); margin-top:10px; font-weight:600;">
                                Registration is currently not accepting new bookings.
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        </div>

    </div>
</main>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
