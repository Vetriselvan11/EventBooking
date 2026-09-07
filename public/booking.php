<?php
/**
 * CampusEvent Hub — Booking Workflow: Step 1 (booking.php)
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/validation.php';

// Enforce login before registration/booking
require_login();

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$eventId = (int)($_GET['event_id'] ?? $_POST['event_id'] ?? 0);

if ($eventId <= 0) {
    header('Location: ' . BASE_URL . '/public/events.php');
    exit;
}

// Fetch Event Details
$stmt = $pdo->prepare("
    SELECT e.*, c.name as category_name
    FROM events e
    JOIN event_categories c ON e.category_id = c.id
    WHERE e.id = ? LIMIT 1
");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlash('danger', 'Event not found.');
    header('Location: ' . BASE_URL . '/public/events.php');
    exit;
}

// Check if booking is permitted
$isDeadlinePassed = strtotime($event['registration_deadline']) < time();
$isSoldOut = ($event['available_seats'] <= 0) || ($event['status'] === 'full');

if ($isDeadlinePassed || $isSoldOut || !in_array($event['status'], ['open', 'almost_full'])) {
    setFlash('danger', 'Registration for this event is closed or at full capacity.');
    header('Location: ' . BASE_URL . '/public/event-details.php?id=' . $eventId);
    exit;
}

$maxSelectable = min(5, (int)$event['available_seats']);
$error = null;

// Handle Form Submission (Step 1 -> Step 2 or Free Confirmation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $quantity = (int)($_POST['quantity'] ?? 1);
    $attendeeNames = $_POST['attendee_names'] ?? [];
    $attendeeEmails = $_POST['attendee_emails'] ?? [];

    // Validation
    if ($quantity < 1 || $quantity > $maxSelectable) {
        $error = "Please select between 1 and {$maxSelectable} tickets.";
    } elseif (count($attendeeNames) !== $quantity || count($attendeeEmails) !== $quantity) {
        $error = "Please provide complete attendee names and emails for all {$quantity} ticket(s).";
    } else {
        // Validate each attendee
        for ($i = 0; $i < $quantity; $i++) {
            $name = trim($attendeeNames[$i] ?? '');
            $email = trim($attendeeEmails[$i] ?? '');
            if (empty($name) || strlen($name) < 2) {
                $error = "Attendee #" . ($i + 1) . " name is invalid.";
                break;
            }
            if (!validate_email($email)) {
                $error = "Attendee #" . ($i + 1) . " email address is invalid.";
                break;
            }
        }
    }

    if (!$error) {
        try {
            $pdo->beginTransaction();

            // 1. Lock event row for update and check real-time availability (Concurrency Protection)
            $lockStmt = $pdo->prepare("SELECT available_seats, ticket_price, max_capacity FROM events WHERE id = ? FOR UPDATE");
            $lockStmt->execute([$eventId]);
            $lockedEvent = $lockStmt->fetch();

            if (!$lockedEvent || $lockedEvent['available_seats'] < $quantity) {
                $pdo->rollBack();
                $error = "We're sorry, seats just sold out! Only {$lockedEvent['available_seats']} seats remaining.";
            } else {
                $unitPrice = floatval($lockedEvent['ticket_price']);
                $totalAmount = $unitPrice * $quantity;
                $bookingRef = generate_booking_reference();
                $isFree = ($totalAmount == 0);
                $initialStatus = $isFree ? 'confirmed' : 'pending_payment';

                // 2. Insert Booking Record
                $bookStmt = $pdo->prepare("
                    INSERT INTO bookings (booking_reference, user_id, event_id, quantity, unit_price, total_amount, status, booked_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $bookStmt->execute([
                    $bookingRef,
                    $currentUser['id'],
                    $eventId,
                    $quantity,
                    $unitPrice,
                    $totalAmount,
                    $initialStatus
                ]);
                $bookingId = (int)$pdo->lastInsertId();

                // 3. Insert Attendees / Ticket Passes
                $attStmt = $pdo->prepare("
                    INSERT INTO booking_attendees (booking_id, attendee_name, attendee_email, ticket_code, is_checked_in, created_at)
                    VALUES (?, ?, ?, ?, 0, NOW())
                ");

                for ($i = 0; $i < $quantity; $i++) {
                    $ticketCode = generate_ticket_code();
                    $attStmt->execute([
                        $bookingId,
                        trim($attendeeNames[$i]),
                        trim($attendeeEmails[$i]),
                        $ticketCode
                    ]);
                }

                // 4. Atomically Decrement Available Seats
                $newAvailable = $lockedEvent['available_seats'] - $quantity;
                $newStatus = ($newAvailable <= 0) ? 'full' : (($newAvailable <= ($lockedEvent['max_capacity'] * 0.15)) ? 'almost_full' : 'open');

                $updateEventStmt = $pdo->prepare("UPDATE events SET available_seats = ?, status = ? WHERE id = ?");
                $updateEventStmt->execute([$newAvailable, $newStatus, $eventId]);

                // 5. If Free Event, create zero-cost Payment Record immediately
                if ($isFree) {
                    $txnRef = generate_transaction_ref();
                    $payStmt = $pdo->prepare("
                        INSERT INTO payments (booking_id, transaction_reference, payment_method, amount, status, gateway_response, paid_at, created_at)
                        VALUES (?, ?, 'free', 0.00, 'successful', '{\"gateway\":\"Free Event Pass Authorization\"}', NOW(), NOW())
                    ");
                    $payStmt->execute([$bookingId, $txnRef]);

                    create_notification(
                        $currentUser['id'],
                        'Free Event Registration Confirmed',
                        "Your registration for {$event['title']} has been confirmed. Booking Reference: {$bookingRef}.",
                        BASE_URL . '/public/confirmation.php?ref=' . $bookingRef
                    );
                }

                $pdo->commit();

                log_audit($currentUser['id'], 'BOOKING_CREATE', "Created booking {$bookingRef} for Event #{$eventId} (Qty: {$quantity})");

                if ($isFree) {
                    setFlash('success', 'Your registration has been confirmed successfully!');
                    header('Location: ' . BASE_URL . '/public/confirmation.php?ref=' . $bookingRef);
                    exit;
                } else {
                    // Forward to Payment Gateway
                    header('Location: ' . BASE_URL . '/public/payment.php?booking_id=' . $bookingId);
                    exit;
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Failed to process booking: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Book Tickets: ' . e($event['title']) . ' — ' . APP_NAME;
$extraScripts = [BASE_URL . '/public/assets/js/booking.js'];
require_once APP_ROOT . '/includes/header.php';
?>

<main class="main-content" style="background:#f1f5f9; padding:36px 0 60px 0;">
    <div class="container" style="max-width:860px;">

        <!-- Step Indicator -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; background:#ffffff; padding:16px 24px; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
            <div style="display:flex; align-items:center; gap:10px; color:var(--primary); font-weight:700;">
                <span style="width:28px; height:28px; background:var(--primary); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.85rem;">1</span>
                <span>Attendee & Ticket Selection</span>
            </div>
            <div style="color:var(--text-muted);">&rarr;</div>
            <div style="display:flex; align-items:center; gap:10px; color:var(--text-muted); font-weight:600;">
                <span style="width:28px; height:28px; background:var(--bg-hover); color:var(--text-muted); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.85rem;">2</span>
                <span>Payment & Verification</span>
            </div>
            <div style="color:var(--text-muted);">&rarr;</div>
            <div style="display:flex; align-items:center; gap:10px; color:var(--text-muted); font-weight:600;">
                <span style="width:28px; height:28px; background:var(--bg-hover); color:var(--text-muted); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.85rem;">3</span>
                <span>Digital Pass Ready</span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <div class="alert-content"><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="booking.php?event_id=<?= $event['id'] ?>" id="bookingForm">
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">

            <div style="display:grid; grid-template-columns: 1.6fr 1fr; gap:28px; align-items:start;">
                
                <!-- Left: Attendee Details Input Cards -->
                <div>
                    <!-- Event Summary Card -->
                    <div class="card" style="margin-bottom:20px;">
                        <div class="card-body">
                            <span class="badge badge-secondary" style="margin-bottom:8px;"><?= e($event['category_name']) ?></span>
                            <h2 style="font-size:1.35rem; font-weight:800; margin-bottom:8px;"><?= e($event['title']) ?></h2>
                            <div style="font-size:0.85rem; color:var(--text-secondary); display:flex; gap:16px; flex-wrap:wrap;">
                                <span><?= render_svg_icon('calendar', '', 14) ?> <?= format_date($event['event_date']) ?></span>
                                <span><?= render_svg_icon('clock', '', 14) ?> <?= format_time($event['start_time']) ?></span>
                                <span><?= render_svg_icon('location', '', 14) ?> <?= e($event['venue']) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Selector -->
                    <div class="card" style="margin-bottom:20px;">
                        <div class="card-header">
                            <h3 class="card-title">1. Select Number of Tickets</h3>
                        </div>
                        <div class="card-body">
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div>
                                    <div style="font-weight:700; color:var(--text-primary);">Standard Entry Pass</div>
                                    <div style="font-size:0.82rem; color:var(--text-muted);">
                                        Max <?= $maxSelectable ?> tickets per transaction (<?= (int)$event['available_seats'] ?> left)
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnQtyMinus" style="width:36px; height:36px; padding:0; justify-content:center;">-</button>
                                    <input type="number" name="quantity" id="ticketQuantity" class="form-control" value="1" min="1" max="<?= $maxSelectable ?>" style="width:60px; text-align:center; font-weight:700;" readonly>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnQtyPlus" style="width:36px; height:36px; padding:0; justify-content:center;">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendee Dynamic Details -->
                    <div class="card" style="margin-bottom:20px;">
                        <div class="card-header">
                            <h3 class="card-title">2. Attendee Pass Information</h3>
                        </div>
                        <div class="card-body" id="attendeesContainer">
                            <!-- Default primary attendee (pre-filled with logged-in user) -->
                            <div class="attendee-row card" id="attendee_row_1" style="margin-bottom:14px; padding:16px; background:#f8fafc;">
                                <div style="font-weight:700; font-size:0.88rem; color:#1e40af; margin-bottom:10px;">
                                    Pass Holder #1 (Primary Registrant)
                                </div>
                                <div class="form-row">
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label form-label-required">Full Name</label>
                                        <input type="text" name="attendee_names[]" class="form-control" value="<?= e($currentUser['name']) ?>" required>
                                    </div>
                                    <div class="form-group" style="margin-bottom:0;">
                                        <label class="form-label form-label-required">Attendee Email</label>
                                        <input type="email" name="attendee_emails[]" class="form-control" value="<?= e($currentUser['email']) ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right: Order Summary Card -->
                <div style="position:sticky; top:90px;">
                    <div class="card" style="border-top:4px solid var(--primary);">
                        <div class="card-header">
                            <h3 class="card-title">Order Summary</h3>
                        </div>
                        <div class="card-body">
                            
                            <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:0.9rem;">
                                <span style="color:var(--text-secondary);">Unit Ticket Price:</span>
                                <span style="font-weight:700;" id="unitPrice" data-price="<?= $event['ticket_price'] ?>">
                                    <?= format_currency($event['ticket_price']) ?>
                                </span>
                            </div>

                            <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:0.9rem;">
                                <span style="color:var(--text-secondary);">Platform Processing Fee:</span>
                                <span style="font-weight:700; color:#059669;">$0.00 (Waived)</span>
                            </div>

                            <div style="height:1px; background:var(--border-subtle); margin:16px 0;"></div>

                            <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:20px;">
                                <span style="font-size:1.05rem; font-weight:800; color:var(--text-primary);">Total Amount:</span>
                                <span style="font-size:1.6rem; font-weight:800; color:var(--primary-900);" id="totalPrice">
                                    <?= format_currency($event['ticket_price']) ?>
                                </span>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg btn-block" style="padding:12px;">
                                <?= ($event['ticket_price'] == 0) ? 'Confirm Free Registration' : 'Proceed to Payment &rarr;' ?>
                            </button>

                            <div style="font-size:0.75rem; color:var(--text-muted); text-align:center; margin-top:12px; line-height:1.4;">
                                <?= render_svg_icon('shield-check', '', 14) ?> Secured with 256-bit institutional encryption and capacity lock.
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</main>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
