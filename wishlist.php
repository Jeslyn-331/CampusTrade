<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare(
    'SELECT w.wishlist_id, w.added_at,
            l.listing_id, l.title, l.price, l.original_price, l.is_discounted, l.category,
            l.item_condition, l.image, l.status, u.username, u.profile_image
     FROM wishlist w
     JOIN listings l ON w.listing_id = l.listing_id
     JOIN users u ON l.user_id = u.user_id
     WHERE w.user_id = ?
     ORDER BY w.added_at DESC'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'My Wishlist';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="page-title">My Wishlist</h1>

    <?php if (!$items): ?>
        <div class="empty-state">
            <p><strong>Your wishlist is empty.</strong></p>
            <p>Browse the marketplace and save items you're interested in.</p>
            <a href="browse.php" class="btn btn-primary">Browse Listings</a>
        </div>
    <?php else: ?>
        <p class="muted"><?= count($items) ?> saved item(s).</p>

        <div class="card-grid">
            <?php foreach ($items as $item): ?>
                <div class="item-card wishlist-card <?= $item['status'] === 'Sold' ? 'is-sold' : '' ?>">
                    <a href="item.php?id=<?= (int) $item['listing_id'] ?>" class="item-image">
                        <img src="<?= e(listing_image($item['image'])) ?>" alt="<?= e($item['title']) ?>">
                        <span class="badge <?= condition_class($item['item_condition']) ?>"><?= e($item['item_condition']) ?></span>
                        <?php if ($item['status'] === 'Sold'): ?>
                            <span class="sold-overlay">SOLD</span>
                        <?php elseif ($item['status'] === 'Reserved'): ?>
                            <span class="badge status-reserved">Reserved</span>
                        <?php elseif (has_discount($item)): ?>
                            <span class="discount-badge"><?= discount_pct($item) ?>% OFF</span>
                        <?php endif; ?>
                    </a>
                    <div class="item-body">
                        <h3 class="item-title">
                            <a href="item.php?id=<?= (int) $item['listing_id'] ?>"><?= e($item['title']) ?></a>
                        </h3>
                        <p class="item-price"><?= price_html($item) ?></p>
                        <p class="item-meta">
                            <span class="seller-chip">
                                <img src="<?= e(user_avatar($item['profile_image'])) ?>" alt="" class="avatar avatar-xs">
                                <?= e($item['username']) ?>
                            </span>
                            <span>Saved <?= format_date($item['added_at']) ?></span>
                        </p>
                        <?php if ($item['status'] === 'Sold'): ?>
                            <p class="sold-note">This item has been sold since you saved it.</p>
                        <?php endif; ?>
                        <div class="wishlist-actions">
                            <?php if ($item['status'] === 'Sold'): ?>
                                <a href="<?= e(similar_items_url($item)) ?>" class="btn btn-small btn-accent">Find Similar Items</a>
                            <?php else: ?>
                                <a href="item.php?id=<?= (int) $item['listing_id'] ?>" class="btn btn-small btn-primary">View Item</a>
                            <?php endif; ?>
                            <form method="post" action="wishlist_action.php">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="listing_id" value="<?= (int) $item['listing_id'] ?>">
                                <input type="hidden" name="redirect" value="wishlist.php">
                                <button type="submit" class="btn btn-small btn-outline">Remove</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
