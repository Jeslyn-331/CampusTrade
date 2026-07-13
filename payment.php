<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$user_id = $_SESSION['user_id'];
$listing_id = (int) ($_GET['listing_id'] ?? $_POST['listing_id'] ?? 0);

// ---- Load the listing and its seller ----
$stmt = $conn->prepare(
    'SELECT l.*, u.username AS seller_name, u.qr_image AS seller_qr
     FROM listings l
     JOIN users u ON l.user_id = u.user_id
     WHERE l.listing_id = ?'
);
$stmt->bind_param('i', $listing_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---- Guards ----
if (!$item) {
    set_flash('That listing no longer exists.', 'error');
    header('Location: browse.php');
    exit;
}
if ((int) $item['user_id'] === $user_id) {
    set_flash('You cannot buy your own listing.', 'error');
    header('Location: item.php?id=' . $listing_id);
    exit;
}
if ($item['status'] !== 'Available') {
    set_flash('Sorry, this item is no longer available (' . $item['status'] . ').', 'error');
    header('Location: item.php?id=' . $listing_id);
    exit;
}

$errors = [];
$payment_method = $_POST['payment_method'] ?? '';
$bank_name = $_POST['bank_name'] ?? '';
$meetup_details = trim($_POST['meetup_details'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $proof_image = null;

    if (!in_array($payment_method, ['FPX', 'QR', 'Cash'], true)) {
        $errors[] = 'Please choose a payment method.';
    } elseif ($payment_method === 'FPX') {
        if (!in_array($bank_name, FPX_BANKS, true)) {
            $errors[] = 'Please select your bank for FPX payment.';
        }
    } elseif ($payment_method === 'QR') {
        if (!$item['seller_qr']) {
            $errors[] = 'This seller has not uploaded a TNG QR code yet. Please choose another payment method or chat with the seller.';
        } else {
            try {
                $proof_image = handle_profile_image_upload($_FILES['proof_image'] ?? [], 'proof_');
                if ($proof_image === null) {
                    $errors[] = 'Please upload your payment screenshot as proof (JPEG/PNG).';
                }
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
            }
        }
    } elseif ($payment_method === 'Cash') {
        if (mb_strlen($meetup_details) < 5 || mb_strlen($meetup_details) > 1000) {
            $errors[] = 'Please describe the proposed meetup location and time (5-1000 characters).';
        }
    }

    if (!$errors) {
        $amount = (float) $item['price'];
        $seller_id = (int) $item['user_id'];
        $bank_value   = $payment_method === 'FPX'  ? $bank_name : null;
        $meetup_value = $payment_method === 'Cash' ? $meetup_details : null;

        $stmt = $conn->prepare(
            'INSERT INTO orders (listing_id, buyer_id, seller_id, payment_method, bank_name, proof_image, meetup_details, amount)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('iiissssd', $listing_id, $user_id, $seller_id, $payment_method, $bank_value, $proof_image, $meetup_value, $amount);
        $stmt->execute();
        $stmt->close();

        // Reserve the item until the seller confirms / either party completes
        $stmt = $conn->prepare("UPDATE listings SET status = 'Reserved' WHERE listing_id = ?");
        $stmt->bind_param('i', $listing_id);
        $stmt->execute();
        $stmt->close();

        set_flash('Order placed! The item is now reserved for you. Track its status in your dashboard.');
        header('Location: dashboard.php');
        exit;
    }
}

$page_title = 'Payment';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="page-title">Complete Your Purchase</h1>
    <p class="muted">This is a university project — no real money is transferred. FPX and QR payments are simulated.</p>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="payment-layout">
        <!-- Order summary -->
        <aside class="card order-summary">
            <h2>Order Summary</h2>
            <img src="<?= e(listing_image($item['image'])) ?>" alt="<?= e($item['title']) ?>" class="order-thumb">
            <h3><?= e($item['title']) ?></h3>
            <p class="muted">Sold by <?= e($item['seller_name']) ?> &middot; <?= e($item['item_condition']) ?></p>
            <p class="item-detail-price"><?= format_price((float) $item['price']) ?></p>
        </aside>

        <!-- Payment form -->
        <section class="card">
            <h2>Choose a Payment Method</h2>

            <form method="post" action="payment.php?listing_id=<?= $listing_id ?>" enctype="multipart/form-data" id="paymentForm">
                <input type="hidden" name="listing_id" value="<?= $listing_id ?>">

                <div class="pay-methods">
                    <label class="pay-method">
                        <input type="radio" name="payment_method" value="FPX" <?= $payment_method === 'FPX' ? 'checked' : '' ?> required>
                        <span><strong>FPX</strong><br><small>Online banking</small></span>
                    </label>
                    <label class="pay-method">
                        <input type="radio" name="payment_method" value="QR" <?= $payment_method === 'QR' ? 'checked' : '' ?>>
                        <span><strong>QR</strong><br><small>TNG eWallet</small></span>
                    </label>
                    <label class="pay-method">
                        <input type="radio" name="payment_method" value="Cash" <?= $payment_method === 'Cash' ? 'checked' : '' ?>>
                        <span><strong>Cash</strong><br><small>Meet in person</small></span>
                    </label>
                </div>

                <!-- FPX panel -->
                <div class="pay-panel" id="panel-FPX" hidden>
                    <div class="form-group">
                        <label for="bank_name">Select Your Bank *</label>
                        <select id="bank_name" name="bank_name">
                            <option value="">— Select bank —</option>
                            <?php foreach (FPX_BANKS as $bank): ?>
                                <option value="<?= e($bank) ?>" <?= $bank_name === $bank ? 'selected' : '' ?>><?= e($bank) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Simulated only — you will not be redirected to a real bank.</small>
                    </div>
                </div>

                <!-- QR panel -->
                <div class="pay-panel" id="panel-QR" hidden>
                    <?php if ($item['seller_qr'] && is_file(UPLOAD_DIR . $item['seller_qr'])): ?>
                        <p><strong>Scan the seller's TNG eWallet QR code:</strong></p>
                        <img src="uploads/<?= e(rawurlencode($item['seller_qr'])) ?>" alt="Seller's TNG QR code" class="qr-display">
                        <div class="form-group">
                            <label for="proof_image">Upload Payment Screenshot (JPEG/PNG) *</label>
                            <input type="file" id="proof_image" name="proof_image" accept="image/jpeg,image/png">
                            <small>No real payment needed for this assignment — any screenshot image works.</small>
                        </div>
                    <?php else: ?>
                        <p class="alert alert-error">This seller has not uploaded a TNG QR code. Please pick FPX or Cash instead.</p>
                    <?php endif; ?>
                </div>

                <!-- Cash panel -->
                <div class="pay-panel" id="panel-Cash" hidden>
                    <div class="form-group">
                        <label for="meetup_details">Proposed Meetup Location &amp; Time *</label>
                        <textarea id="meetup_details" name="meetup_details" rows="3" maxlength="1000"
                                  placeholder="e.g. Block D cafeteria, Friday 2pm"><?= e($meetup_details) ?></textarea>
                        <small>Tip: agree on the details in the chat first, then confirm here.</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-accent">Place Order — <?= format_price((float) $item['price']) ?></button>
                <a href="item.php?id=<?= $listing_id ?>" class="btn btn-outline">Cancel</a>
            </form>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
