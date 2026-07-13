<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$user_id = $_SESSION['user_id'];

// ---- Mark a listing as Sold / Available again ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_status') {
    $listing_id = (int) ($_POST['listing_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    if (in_array($new_status, ['Available', 'Sold', 'Reserved'], true)) {
        // Ownership check built into the WHERE clause
        $stmt = $conn->prepare('UPDATE listings SET status = ? WHERE listing_id = ? AND user_id = ?');
        $stmt->bind_param('sii', $new_status, $listing_id, $user_id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            set_flash("Listing marked as $new_status.");
        }
        $stmt->close();
    }
    header('Location: dashboard.php');
    exit;
}

// ---- Complete / cancel an order ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'order_status') {
    $order_id = (int) ($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';

    $stmt = $conn->prepare('SELECT * FROM orders WHERE order_id = ? AND (buyer_id = ? OR seller_id = ?)');
    $stmt->bind_param('iii', $order_id, $user_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order && $order['status'] === 'Pending' && in_array($new_status, ['Completed', 'Cancelled'], true)) {
        $is_seller = (int) $order['seller_id'] === $user_id;
        $is_buyer  = (int) $order['buyer_id'] === $user_id;

        // Sellers may complete/cancel. Buyers may cancel any pending order,
        // and may complete Cash orders (either party confirms the meetup).
        $allowed = $is_seller
            || ($is_buyer && $new_status === 'Cancelled')
            || ($is_buyer && $new_status === 'Completed' && $order['payment_method'] === 'Cash');

        if ($allowed) {
            $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
            $stmt->bind_param('si', $new_status, $order_id);
            $stmt->execute();
            $stmt->close();

            // Sync the listing: Completed → Sold, Cancelled → back to Available
            $listing_status = $new_status === 'Completed' ? 'Sold' : 'Available';
            $stmt = $conn->prepare('UPDATE listings SET status = ? WHERE listing_id = ?');
            $stmt->bind_param('si', $listing_status, $order['listing_id']);
            $stmt->execute();
            $stmt->close();

            set_flash("Order marked as $new_status.");
        } else {
            set_flash('You are not allowed to update this order that way.', 'error');
        }
    }
    header('Location: dashboard.php');
    exit;
}

// ---- Overview stats (private to this user) ----
$stmt = $conn->prepare(
    "SELECT
        (SELECT COUNT(*) FROM listings WHERE user_id = ?)                                        AS my_listings,
        (SELECT COUNT(*) FROM orders  WHERE seller_id = ? AND status = 'Completed')              AS my_sold,
        (SELECT COALESCE(SUM(amount), 0) FROM orders WHERE seller_id = ? AND status = 'Completed') AS my_earnings,
        (SELECT COUNT(*) FROM wishlist WHERE user_id = ?)                                        AS my_wishlist"
);
$stmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$overview = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---- Incoming orders (I am the seller) ----
$stmt = $conn->prepare(
    'SELECT o.*, l.title, l.image, u.username AS buyer_name
     FROM orders o
     JOIN listings l ON o.listing_id = l.listing_id
     JOIN users u ON o.buyer_id = u.user_id
     WHERE o.seller_id = ?
     ORDER BY o.created_at DESC'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$incoming_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---- My purchases (I am the buyer) ----
$stmt = $conn->prepare(
    'SELECT o.*, l.title, l.image, u.username AS seller_name
     FROM orders o
     JOIN listings l ON o.listing_id = l.listing_id
     JOIN users u ON o.seller_id = u.user_id
     WHERE o.buyer_id = ?
     ORDER BY o.created_at DESC'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$my_purchases = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---- My listings ----
$stmt = $conn->prepare(
    'SELECT listing_id, title, price, original_price, is_discounted, category, item_condition, image, status, created_at
     FROM listings WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$my_listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/** Payment detail cell for an order row. */
function order_payment_details(array $o): string
{
    if ($o['payment_method'] === 'FPX') {
        return 'FPX — ' . e($o['bank_name'] ?? '');
    }
    if ($o['payment_method'] === 'QR') {
        $html = 'TNG QR';
        if ($o['proof_image'] && is_file(UPLOAD_DIR . $o['proof_image'])) {
            $html .= ' — <a href="uploads/' . e(rawurlencode($o['proof_image'])) . '" target="_blank">view proof</a>';
        }
        return $html;
    }
    return 'Cash — ' . e(mb_strimwidth($o['meetup_details'] ?? '', 0, 60, '…'));
}

$page_title = 'My Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="page-title">My Dashboard</h1>
    <p class="muted">Welcome back, <strong><?= e($_SESSION['username']) ?></strong>!
        These stats are private — only you can see them.</p>

    <div class="dashboard-stats">
        <div class="stat card">
            <span class="stat-number"><?= (int) $overview['my_listings'] ?></span>
            <span class="stat-label">My Listings</span>
        </div>
        <div class="stat card">
            <span class="stat-number"><?= (int) $overview['my_sold'] ?></span>
            <span class="stat-label">Items Sold</span>
        </div>
        <div class="stat card">
            <span class="stat-number"><?= format_price((float) $overview['my_earnings']) ?></span>
            <span class="stat-label">Total Earnings</span>
        </div>
        <div class="stat card">
            <span class="stat-number"><?= (int) $overview['my_wishlist'] ?></span>
            <span class="stat-label">Wishlist Items</span>
        </div>
        <a href="create_listing.php" class="stat card stat-action">
            <span class="stat-number">+</span>
            <span class="stat-label">Sell an Item</span>
        </a>
    </div>

    <!-- Incoming orders (seller view) -->
    <?php if ($incoming_orders): ?>
        <section class="section">
            <h2 class="section-title">Incoming Orders</h2>
            <div class="table-wrap">
                <table class="listing-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Buyer</th>
                            <th>Payment</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incoming_orders as $o): ?>
                            <tr>
                                <td class="cell-item">
                                    <img src="<?= e(listing_image($o['image'])) ?>" alt="" class="thumb">
                                    <a href="item.php?id=<?= (int) $o['listing_id'] ?>"><?= e($o['title']) ?></a>
                                </td>
                                <td><?= e($o['buyer_name']) ?></td>
                                <td><?= order_payment_details($o) ?></td>
                                <td><?= format_price((float) $o['amount']) ?></td>
                                <td><span class="badge order-<?= strtolower($o['status']) ?>"><?= e($o['status']) ?></span></td>
                                <td class="cell-actions">
                                    <?php if ($o['status'] === 'Pending'): ?>
                                        <form method="post" action="dashboard.php"
                                              onsubmit="return confirm('Confirm payment received and mark this order Completed? The listing will be marked Sold.');">
                                            <input type="hidden" name="action" value="order_status">
                                            <input type="hidden" name="order_id" value="<?= (int) $o['order_id'] ?>">
                                            <input type="hidden" name="status" value="Completed">
                                            <button type="submit" class="btn btn-small btn-primary">Confirm Payment</button>
                                        </form>
                                        <form method="post" action="dashboard.php"
                                              onsubmit="return confirm('Cancel this order? The listing becomes Available again.');">
                                            <input type="hidden" name="action" value="order_status">
                                            <input type="hidden" name="order_id" value="<?= (int) $o['order_id'] ?>">
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="btn btn-small btn-outline">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted"><?= format_date($o['updated_at'] ?? $o['created_at']) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <!-- Order history (buyer view) -->
    <?php if ($my_purchases): ?>
        <section class="section">
            <h2 class="section-title">My Purchases</h2>
            <div class="table-wrap">
                <table class="listing-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Seller</th>
                            <th>Payment</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_purchases as $o): ?>
                            <tr>
                                <td class="cell-item">
                                    <img src="<?= e(listing_image($o['image'])) ?>" alt="" class="thumb">
                                    <a href="item.php?id=<?= (int) $o['listing_id'] ?>"><?= e($o['title']) ?></a>
                                </td>
                                <td><a href="seller.php?id=<?= (int) $o['seller_id'] ?>"><?= e($o['seller_name']) ?></a></td>
                                <td><?= order_payment_details($o) ?></td>
                                <td><?= format_price((float) $o['amount']) ?></td>
                                <td><span class="badge order-<?= strtolower($o['status']) ?>"><?= e($o['status']) ?></span></td>
                                <td class="cell-actions">
                                    <?php if ($o['status'] === 'Pending'): ?>
                                        <?php if ($o['payment_method'] === 'Cash'): ?>
                                            <form method="post" action="dashboard.php"
                                                  onsubmit="return confirm('Confirm the meetup happened and mark this order Completed?');">
                                                <input type="hidden" name="action" value="order_status">
                                                <input type="hidden" name="order_id" value="<?= (int) $o['order_id'] ?>">
                                                <input type="hidden" name="status" value="Completed">
                                                <button type="submit" class="btn btn-small btn-primary">Mark Completed</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="dashboard.php"
                                              onsubmit="return confirm('Cancel this order?');">
                                            <input type="hidden" name="action" value="order_status">
                                            <input type="hidden" name="order_id" value="<?= (int) $o['order_id'] ?>">
                                            <input type="hidden" name="status" value="Cancelled">
                                            <button type="submit" class="btn btn-small btn-outline">Cancel</button>
                                        </form>
                                    <?php elseif ($o['status'] === 'Completed'): ?>
                                        <a href="seller.php?id=<?= (int) $o['seller_id'] ?>" class="btn btn-small btn-outline">Review Seller</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <!-- My listings -->
    <section class="section">
        <h2 class="section-title">My Listings</h2>

        <?php if (!$my_listings): ?>
            <div class="empty-state">
                <p><strong>You haven't listed anything yet.</strong></p>
                <p>Got textbooks or gadgets gathering dust? List them now.</p>
                <a href="create_listing.php" class="btn btn-primary">Create Your First Listing</a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="listing-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_listings as $item): ?>
                            <tr>
                                <td class="cell-item">
                                    <img src="<?= e(listing_image($item['image'])) ?>" alt="" class="thumb">
                                    <a href="item.php?id=<?= (int) $item['listing_id'] ?>"><?= e($item['title']) ?></a>
                                </td>
                                <td>
                                    <?= price_html($item) ?>
                                    <?php if (has_discount($item)): ?>
                                        <span class="discount-badge inline"><?= discount_pct($item) ?>% OFF</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($item['category']) ?></td>
                                <td><span class="badge <?= status_class($item['status']) ?>"><?= e($item['status']) ?></span></td>
                                <td><?= format_date($item['created_at']) ?></td>
                                <td class="cell-actions">
                                    <a href="edit_listing.php?id=<?= (int) $item['listing_id'] ?>" class="btn btn-small btn-outline">Edit</a>

                                    <?php if ($item['status'] !== 'Sold'): ?>
                                        <a href="edit_listing.php?id=<?= (int) $item['listing_id'] ?>#discount" class="btn btn-small btn-outline">
                                            <?= has_discount($item) ? 'Discount…' : 'Set Discount' ?>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($item['status'] !== 'Sold'): ?>
                                        <form method="post" action="dashboard.php">
                                            <input type="hidden" name="action" value="set_status">
                                            <input type="hidden" name="listing_id" value="<?= (int) $item['listing_id'] ?>">
                                            <input type="hidden" name="status" value="Sold">
                                            <button type="submit" class="btn btn-small btn-outline">Mark Sold</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="dashboard.php">
                                            <input type="hidden" name="action" value="set_status">
                                            <input type="hidden" name="listing_id" value="<?= (int) $item['listing_id'] ?>">
                                            <input type="hidden" name="status" value="Available">
                                            <button type="submit" class="btn btn-small btn-outline">Relist</button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="post" action="delete_listing.php"
                                          onsubmit="return confirm('Delete this listing permanently?');">
                                        <input type="hidden" name="listing_id" value="<?= (int) $item['listing_id'] ?>">
                                        <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
