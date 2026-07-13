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

// ---- Overview stats ----
$stmt = $conn->prepare(
    "SELECT
        (SELECT COUNT(*) FROM listings WHERE user_id = ?)                       AS my_listings,
        (SELECT COUNT(*) FROM listings WHERE user_id = ? AND status = 'Sold')   AS my_sold,
        (SELECT COUNT(*) FROM wishlist WHERE user_id = ?)                       AS my_wishlist"
);
$stmt->bind_param('iii', $user_id, $user_id, $user_id);
$stmt->execute();
$overview = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---- My listings ----
$stmt = $conn->prepare(
    'SELECT listing_id, title, price, category, item_condition, image, status, created_at
     FROM listings WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$my_listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'My Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="page-title">My Dashboard</h1>
    <p class="muted">Welcome back, <strong><?= e($_SESSION['username']) ?></strong>!</p>

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
            <span class="stat-number"><?= (int) $overview['my_wishlist'] ?></span>
            <span class="stat-label">Wishlist Items</span>
        </div>
        <a href="create_listing.php" class="stat card stat-action">
            <span class="stat-number">+</span>
            <span class="stat-label">Sell an Item</span>
        </a>
    </div>

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
                                <td><?= format_price((float) $item['price']) ?></td>
                                <td><?= e($item['category']) ?></td>
                                <td><span class="badge <?= status_class($item['status']) ?>"><?= e($item['status']) ?></span></td>
                                <td><?= format_date($item['created_at']) ?></td>
                                <td class="cell-actions">
                                    <a href="edit_listing.php?id=<?= (int) $item['listing_id'] ?>" class="btn btn-small btn-outline">Edit</a>

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
