<?php
require_once __DIR__ . '/includes/db.php';

$listing_id = (int) ($_GET['id'] ?? 0);

// ---- Load the listing with its seller ----
$stmt = $conn->prepare(
    'SELECT l.*, u.username, u.profile_image AS seller_image, u.created_at AS seller_joined,
            (SELECT COUNT(*) FROM listings WHERE user_id = u.user_id) AS seller_listing_count
     FROM listings l
     JOIN users u ON l.user_id = u.user_id
     WHERE l.listing_id = ?'
);
$stmt->bind_param('i', $listing_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    http_response_code(404);
    $page_title = 'Item Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty-state">
            <p><strong>Sorry, this listing no longer exists.</strong></p>
            <p>It may have been removed by the seller.</p>
            <a href="browse.php" class="btn btn-primary">Browse Other Items</a>
          </div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$seller_id = (int) $item['user_id'];
$is_owner = is_logged_in() && $_SESSION['user_id'] === $seller_id;

// Seller trust rating (reviews attach to the seller, not the listing)
$rating = seller_rating($conn, $seller_id);

// Is this item already in the viewer's wishlist?
$in_wishlist = false;
if (is_logged_in()) {
    $stmt = $conn->prepare('SELECT wishlist_id FROM wishlist WHERE user_id = ? AND listing_id = ?');
    $stmt->bind_param('ii', $_SESSION['user_id'], $listing_id);
    $stmt->execute();
    $in_wishlist = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
}

$page_title = $item['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <p class="breadcrumb">
        <a href="index.php">Home</a> &rsaquo;
        <a href="browse.php">Browse</a> &rsaquo;
        <a href="browse.php?category=<?= urlencode($item['category']) ?>"><?= e($item['category']) ?></a> &rsaquo;
        <span><?= e($item['title']) ?></span>
    </p>

    <div class="item-detail">
        <!-- Image -->
        <div class="item-detail-image">
            <img src="<?= e(listing_image($item['image'])) ?>" alt="<?= e($item['title']) ?>">
            <span class="badge <?= condition_class($item['item_condition']) ?>"><?= e($item['item_condition']) ?></span>
        </div>

        <!-- Info -->
        <div class="item-detail-info">
            <div class="item-detail-head">
                <h1><?= e($item['title']) ?></h1>
                <span class="badge <?= status_class($item['status']) ?>"><?= e($item['status']) ?></span>
            </div>

            <p class="item-detail-price"><?= format_price((float) $item['price']) ?></p>

            <ul class="item-detail-meta">
                <li><strong>Category:</strong> <a href="browse.php?category=<?= urlencode($item['category']) ?>"><?= e($item['category']) ?></a></li>
                <li><strong>Condition:</strong> <?= e($item['item_condition']) ?></li>
                <li><strong>Posted:</strong> <?= format_date($item['created_at']) ?></li>
                <?php if ($item['updated_at']): ?>
                    <li><strong>Updated:</strong> <?= format_date($item['updated_at']) ?></li>
                <?php endif; ?>
                <li><strong>Seller Rating:</strong>
                    <?php if ($rating['count'] > 0): ?>
                        <span class="stars"><?= render_stars($rating['avg']) ?></span>
                        (<?= number_format($rating['avg'], 1) ?> from <?= $rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?>)
                    <?php else: ?>
                        <span class="muted">No reviews yet</span>
                    <?php endif; ?>
                </li>
            </ul>

            <h2>Description</h2>
            <p class="item-description"><?= nl2br(e($item['description'])) ?></p>

            <!-- Action buttons -->
            <div class="item-actions">
                <?php if ($is_owner || is_admin()): ?>
                    <?php if ($is_owner): ?>
                        <a href="edit_listing.php?id=<?= $listing_id ?>" class="btn btn-primary">Edit Listing</a>
                    <?php endif; ?>
                    <form method="post" action="delete_listing.php"
                          onsubmit="return confirm('Delete this listing permanently?');">
                        <input type="hidden" name="listing_id" value="<?= $listing_id ?>">
                        <button type="submit" class="btn btn-danger">Delete Listing</button>
                    </form>
                <?php elseif (is_logged_in()): ?>
                    <?php if ($item['status'] === 'Available'): ?>
                        <a href="payment.php?listing_id=<?= $listing_id ?>" class="btn btn-accent">Buy Now</a>
                    <?php endif; ?>
                    <a href="chat.php?listing_id=<?= $listing_id ?>" class="btn btn-primary">&#128172; Chat with Seller</a>
                    <form method="post" action="wishlist_action.php">
                        <input type="hidden" name="listing_id" value="<?= $listing_id ?>">
                        <input type="hidden" name="action" value="<?= $in_wishlist ? 'remove' : 'add' ?>">
                        <input type="hidden" name="redirect" value="item.php?id=<?= $listing_id ?>">
                        <button type="submit" class="btn btn-outline">
                            <?= $in_wishlist ? '&#10005; Remove from Wishlist' : '&#9825; Add to Wishlist' ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Log in to buy, chat or save this item</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Seller profile -->
        <aside class="seller-card">
            <h3>About the Seller</h3>
            <a href="seller.php?id=<?= $seller_id ?>" class="seller-link">
                <img src="<?= e(user_avatar($item['seller_image'])) ?>" alt="<?= e($item['username']) ?>'s profile picture"
                     class="avatar avatar-lg">
                <span class="seller-name"><?= e($item['username']) ?></span>
            </a>
            <?php if ($rating['count'] > 0): ?>
                <p class="stars"><?= render_stars($rating['avg']) ?>
                    <span class="muted"><?= number_format($rating['avg'], 1) ?> (<?= $rating['count'] ?>)</span></p>
            <?php else: ?>
                <p class="muted">No reviews yet</p>
            <?php endif; ?>
            <p class="muted">Member since <?= format_date($item['seller_joined']) ?></p>
            <p class="muted"><?= (int) $item['seller_listing_count'] ?> listing(s) posted</p>
            <a href="seller.php?id=<?= $seller_id ?>" class="btn btn-outline btn-small">View All Seller Reviews</a>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
