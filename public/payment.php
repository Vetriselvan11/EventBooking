<?php
/**
 * CampusEvent Hub — Payment Workflow: Step 2 (payment.php)
 * Transparent Simulated Enterprise Payment Gateway
 */

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/auth.php';
require_once APP_ROOT . '/includes/validation.php';

require_login();

$pdo = Database::getConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/public/install.php');
    exit;
}

$currentUser = get_current_user_data();
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: ' . BASE_URL . '/student/bookings.php');
    exit;
}

// Fetch Booking with Event details
$stmt = $pdo->prepare("
    SELECT b.*, e.title as event_title, e.event_date, e.start_time, e.venue, c.name as category_name
    FROM bookings b
    JOIN events e ON b.event_id = e.id
    JOIN event_categories c ON e.category_id = c.id
    WHERE b.id = ? AND (b.user_id = ? OR ? = 'admin')
    LIMIT 1
");
$stmt->execute([$bookingId, $currentUser['id'], $currentUser['role']]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('danger', 'Booking record not found or unauthorized.');
    header('Location: ' . BASE_URL . '/student/bookings.php');
    exit;
}

// If already confirmed, redirect to confirmation
if ($booking['status'] === 'confirmed') {
    header('Location: ' . BASE_URL . '/public/confirmation.php?ref=' . $booking['booking_reference']);
    exit;
}

$error = null;

// Process Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_payment') {
    require_csrf_token();

    $paymentMethod = $_POST['payment_method'] ?? 'card';
    $outcome = $_POST['simulated_outcome'] ?? 'success'; // 'success' or 'fail'
    $txnRef = generate_transaction_ref();
    $amount = floatval($booking['total_amount']);

    if ($outcome === 'fail') {
        // Record failed payment
        $failStmt = $pdo->prepare("
            INSERT INTO payments (booking_id, transaction_reference, payment_method, amount, status, gateway_response, created_at)
            VALUES (?, ?, ?, ?, 'failed', '{\"error\":\"Payment declined: Insufficient funds or invalid security code (Demo Simulation)\"}', NOW())
        ");
        $failStmt->execute([$bookingId, $txnRef, $paymentMethod, $amount]);

        $error = "Transaction declined by simulated card gateway. Please verify your credentials or try a different method.";
    } else {
        // Successful payment execution
        try {
            $pdo->beginTransaction();

            // 1. Insert Payment Record
            $payDetails = json_encode([
                'gateway' => 'Campus Simulated Gateway Rail',
                'method' => $paymentMethod,
                'auth_code' => 'AUTH_' . strtoupper(bin2hex(random_bytes(3))),
                'timestamp' => date('c')
            ]);

            $payStmt = $pdo->prepare("
                INSERT INTO payments (booking_id, transaction_reference, payment_method, amount, status, gateway_response, paid_at, created_at)
                VALUES (?, ?, ?, ?, 'successful', ?, NOW(), NOW())
            ");
            $payStmt->execute([$bookingId, $txnRef, $paymentMethod, $amount, $payDetails]);

            // 2. Update Booking Status to Confirmed
            $updateBook = $pdo->prepare("UPDATE bookings SET status = 'confirmed', updated_at = NOW() WHERE id = ?");
            $updateBook->execute([$bookingId]);

            // 3. User Notification
            create_notification(
                $booking['user_id'],
                'Payment Received & Booking Confirmed',
                "Payment of " . format_currency($amount) . " for {$booking['event_title']} was successful. Ref: {$booking['booking_reference']}.",
                BASE_URL . '/public/confirmation.php?ref=' . $booking['booking_reference']
            );

            $pdo->commit();

            log_audit($currentUser['id'], 'PAYMENT_SUCCESS', "Settled payment {$txnRef} for booking {$booking['booking_reference']} ({$amount})");
            setFlash('success', 'Payment processed successfully! Your tickets are confirmed.');
            header('Location: ' . BASE_URL . '/public/confirmation.php?ref=' . $booking['booking_reference']);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Failed to record transaction: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Payment Checkout — ' . APP_NAME;
require_once APP_ROOT . '/includes/header.php';
?>

<main class="main-content" style="background:#f1f5f9; padding:36px 0 60px 0;">
    <div class="container" style="max-width:860px;">

        <!-- Step Indicator -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; background:#ffffff; padding:16px 24px; border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
            <div style="display:flex; align-items:center; gap:10px; color:#059669; font-weight:600;">
                <span style="width:28px; height:28px; background:#ecfdf5; color:#059669; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.85rem;">✓</span>
                <span>Attendee Information</span>
            </div>
            <div style="color:var(--text-muted);">&rarr;</div>
            <div style="display:flex; align-items:center; gap:10px; color:var(--primary); font-weight:700;">
                <span style="width:28px; height:28px; background:var(--primary); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.85rem;">2</span>
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

        <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:28px; align-items:start;">
            
            <!-- Left: Payment Form & Gateway -->
            <div>
                <div class="card" style="margin-bottom:24px;">
                    <div class="card-header">
                        <h3 class="card-title">Select Payment Method</h3>
                    </div>
                    <div class="card-body">
                        
                        <!-- Environment Disclaimer -->
                        <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:var(--radius-sm); padding:12px 16px; font-size:0.84rem; color:#1e40af; margin-bottom:20px; display:flex; gap:10px;">
                            <?= render_svg_icon('shield-check', '', 20) ?>
                            <div>
                                <strong>Academic Demo Payment Gateway:</strong> This environment simulates live transaction settlement for educational verification. No real money will be charged.
                            </div>
                        </div>

                        <form method="POST" action="payment.php?booking_id=<?= $booking['id'] ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="process_payment">
                            <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

                            <!-- Payment Tabs/Options -->
                            <div class="form-group">
                                <label class="form-label form-label-required">Payment Channel</label>
                                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; margin-bottom:18px;">
                                    <label style="border:1px solid var(--border-strong); border-radius:var(--radius-sm); padding:12px; text-align:center; cursor:pointer; background:#fff; display:block;">
                                        <input type="radio" name="payment_method" value="card" checked style="margin-bottom:6px;">
                                        <div style="font-weight:700; font-size:0.85rem;">Credit Card</div>
                                        <div style="font-size:0.72rem; color:var(--text-muted);">Visa, MC, Amex</div>
                                    </label>
                                    <label style="border:1px solid var(--border-strong); border-radius:var(--radius-sm); padding:12px; text-align:center; cursor:pointer; background:#fff; display:block;">
                                        <input type="radio" name="payment_method" value="upi" style="margin-bottom:6px;">
                                        <div style="font-weight:700; font-size:0.85rem;">UPI FastPay</div>
                                        <div style="font-size:0.72rem; color:var(--text-muted);">Instant VPA</div>
                                    </label>
                                    <label style="border:1px solid var(--border-strong); border-radius:var(--radius-sm); padding:12px; text-align:center; cursor:pointer; background:#fff; display:block;">
                                        <input type="radio" name="payment_method" value="netbanking" style="margin-bottom:6px;">
                                        <div style="font-weight:700; font-size:0.85rem;">Net Banking</div>
                                        <div style="font-size:0.72rem; color:var(--text-muted);">Campus Banks</div>
                                    </label>
                                </div>
                            </div>

                            <!-- Card Inputs Demo -->
                            <div id="cardFields">
                                <div class="form-group">
                                    <label class="form-label">Name on Card</label>
                                    <input type="text" class="form-control" value="<?= e($currentUser['name']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Card Number</label>
                                    <input type="text" class="form-control num-tabular" placeholder="4242 •••• •••• 4242" value="4242 8819 0293 4242" required>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Expiration</label>
                                        <input type="text" class="form-control num-tabular" placeholder="MM/YY" value="12/28" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Security CVC</label>
                                        <input type="password" class="form-control num-tabular" placeholder="•••" value="882" maxlength="4" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Simulation Gateway Outcome Switcher -->
                            <div style="background:#f8fafc; border:1px solid var(--border-subtle); border-radius:var(--radius-sm); padding:14px; margin-bottom:20px;">
                                <label class="form-label" style="font-size:0.82rem; color:var(--text-muted);">Simulation Testing Outcome:</label>
                                <select name="simulated_outcome" class="form-select" style="font-size:0.86rem;">
                                    <option value="success" selected>✓ Authorize Payment (Successful Settlement)</option>
                                    <option value="fail">✗ Simulate Declined / Insufficient Balance</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg btn-block" style="padding:12px;">
                                <?= render_svg_icon('shield-check', '', 18) ?> Authorize & Pay <?= format_currency($booking['total_amount']) ?>
                            </button>
                        </form>

                    </div>
                </div>
            </div>

            <!-- Right: Booking Review Summary -->
            <div style="position:sticky; top:90px;">
                <div class="card" style="border-top:4px solid var(--primary);">
                    <div class="card-header">
                        <h3 class="card-title">Booking Summary</h3>
                    </div>
                    <div class="card-body">
                        
                        <div style="font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; margin-bottom:4px;">
                            Reference
                        </div>
                        <div style="font-size:1.1rem; font-weight:800; color:var(--primary); font-family:monospace; margin-bottom:14px;">
                            <?= e($booking['booking_reference']) ?>
                        </div>

                        <div style="font-weight:700; color:var(--text-primary); margin-bottom:4px;">
                            <?= e($booking['event_title']) ?>
                        </div>
                        <div style="font-size:0.82rem; color:var(--text-secondary); margin-bottom:16px;">
                            <?= format_date($booking['event_date']) ?> · <?= e($booking['venue']) ?>
                        </div>

                        <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.88rem;">
                            <span style="color:var(--text-secondary);">Pass Quantity:</span>
                            <span style="font-weight:700;"><?= (int)$booking['quantity'] ?> Pass(es)</span>
                        </div>

                        <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.88rem;">
                            <span style="color:var(--text-secondary);">Unit Price:</span>
                            <span><?= format_currency($booking['unit_price']) ?></span>
                        </div>

                        <div style="height:1px; background:var(--border-subtle); margin:14px 0;"></div>

                        <div style="display:flex; justify-content:space-between; align-items:baseline;">
                            <span style="font-size:1.05rem; font-weight:800; color:var(--text-primary);">Total Due:</span>
                            <span style="font-size:1.5rem; font-weight:800; color:var(--primary-900);">
                                <?= format_currency($booking['total_amount']) ?>
                            </span>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>
</main>

<?php require_once APP_ROOT . '/includes/footer.php'; ?>
