<?php
require_once __DIR__ . '/includes/db.php';

$listing_id = (int) ($_GET['id'] ?? 0);

// ---- Review actions (submit / delete) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';
    $listing_id = (int) ($_POST['listing_id'] ?? $listing_id);

    if ($action === 'add_review') {
        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            set_flash('Please choose a star rating between 1 and 5.', 'error');
        } else {
            $comment_value = $comment === '' ? null : $comment;
            $stmt = $conn->prepare('INSERT INTO reviews (listing_id, user_id, rating, comment) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('iiis', $listing_id, $user_id, $rating, $comment_value);
            $stmt->execute();
            $stmt->close();
            set_flash('Thanks — your review has been posted.');
        }
    } elseif ($action === 'delete_review') {
        $review_id = (int) ($_POST['review_id'] ?? 0);
        // Ownership check: users may only delete their own review (admins may delete any)
        if (is_admin()) {
            $stmt = $conn->prepare('DELETE FROM reviews WHERE review_id = ?');
            $stmt->bind_param('i', $review_id);
        } else {
            $stmt = $conn->prepare('DELETE FROM reviews WHERE review_id = ? AND user_id = ?');
            $stmt->bind_param('ii', $review_id, $user_id);
        }
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            set_flash('Review deleted.');
        }
        $stmt->close();
    }

    header('Location: item.php?id=' . $listing_id);
    exit;
}

// ---- Load the listing with its seller ----
$stmt = $conn->prepare(
    'SELECT l.*, u.username, u.email AS seller_email, u.phone AS seller_phone, u.created_at AS seller_joined,
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

$is_owner = is_logged_in() && $_SESSION['user_id'] === (int) $item['user_id'];

// Is this item already in the viewer's wishlist?
$in_wishlist = false;
if (is_logged_in()) {
    $stmt = $conn->prepare('SELECT wishlist_id FROM wishlist WHERE user_id = ? AND listing_id = ?');
    $stmt->bind_param('ii', $_SESSION['user_id'], $listing_id);
    $stmt->execute();
    $in_wishlist = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
}

// ---- Reviews (newest first) with average rating ----
$stmt = $conn->prepare(
    'SELECT r.review_id, r.user_id, r.rating, r.comment, r.created_at, u.username
     FROM reviews r
     JOIN users u ON r.user_id = u.user_id
     WHERE r.listing_id = ?
     ORDER BY r.created_at DESC'
);
$stmt->bind_param('i', $listing_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$avg_rating = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;

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
                <?php if ($reviews): ?>
                    <li><strong>Rating:</strong> <span class="stars"><?= render_stars($avg_rating) ?></span>
                        (<?= number_format($avg_rating, 1) ?> from <?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?>)</li>
                <?php endif; ?>
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
                    <form method="post" action="wishlist_action.php">
                        <input type="hidden" name="listing_id" value="<?= $listing_id ?>">
                        <input type="hidden" name="action" value="<?= $in_wishlist ? 'remove' : 'add' ?>">
                        <input type="hidden" name="redirect" value="item.php?id=<?= $listing_id ?>">
                        <button type="submit" class="btn <?= $in_wishlist ? 'btn-outline' : 'btn-primary' ?>">
                            <?= $in_wishlist ? '&#10005; Remove from Wishlist' : '&#9825; Add to Wishlist' ?>
                        </button>
                    </form>
                    <button type="button" class="btn btn-accent" id="contactSellerBtn">Contact Seller</button>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Log in to add to wishlist or contact the seller</a>
                <?php endif; ?>
            </div>

            <?php if (is_logged_in() && !$is_owner): ?>
                <div class="contact-reveal" id="contactReveal" hidden>
                    <h3>Seller Contact Details</h3>
                    <p><strong>Email:</strong> <a href="mailto:<?= e($item['seller_email']) ?>"><?= e($item['seller_email']) ?></a></p>
                    <?php if ($item['seller_phone']): ?>
                        <p><strong>Phone:</strong> <?= e($item['seller_phone']) ?></p>
                    <?php endif; ?>
                    <p class="muted">Meet on campus in a public place for the exchange.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Seller profile -->
        <aside class="seller-card">
            <h3>About the Seller</h3>
            <p class="seller-name">&#128100; <?= e($item['username']) ?></p>
            <p class="muted">Member since <?= format_date($item['seller_joined']) ?></p>
            <p class="muted"><?= (int) $item['seller_listing_count'] ?> listing(s) posted</p>
        </aside>
    </div>

    <!-- Reviews section -->
    <section class="reviews-section">
        <h2>Reviews (<?= count($reviews) ?>)</h2>

        <?php if (is_logged_in() && !$is_owner): ?>
            <form method="post" action="item.php" class="review-form card" id="reviewForm">
                <input type="hidden" name="action" value="add_review">
                <input type="hidden" name="listing_id" value="<?= $listing_id ?>">

                <div class="form-group">
                    <label>Your Rating *</label>
                    <div class="star-input" id="starInput">
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                            <input type="radio" name="rating" id="star<?= $s ?>" value="<?= $s ?>" required>
                            <label for="star<?= $s ?>" title="<?= $s ?> star<?= $s === 1 ? '' : 's' ?>">&#9733;</label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="comment">Comment (optional)</label>
                    <textarea id="comment" name="comment" rows="3" maxlength="1000"
                              placeholder="Share your experience with this item or seller..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Post Review</button>
            </form>
        <?php elseif (!is_logged_in()): ?>
            <p class="muted"><a href="login.php">Log in</a> to leave a review.</p>
        <?php endif; ?>

        <?php if (!$reviews): ?>
            <p class="empty-state">No reviews yet for this item.</p>
        <?php else: ?>
            <ul class="review-list">
                <?php foreach ($reviews as $review): ?>
                    <li class="review card">
                        <div class="review-head">
                            <strong><?= e($review['username']) ?></strong>
                            <span class="stars"><?= render_stars((float) $review['rating']) ?></span>
                            <span class="muted"><?= format_date($review['created_at']) ?></span>
                        </div>
                        <?php if ($review['comment']): ?>
                            <p><?= nl2br(e($review['comment'])) ?></p>
                        <?php endif; ?>
                        <?php if (is_logged_in() && ((int) $review['user_id'] === $_SESSION['user_id'] || is_admin())): ?>
                            <form method="post" action="item.php" onsubmit="return confirm('Delete this review?');">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="listing_id" value="<?= $listing_id ?>">
                                <input type="hidden" name="review_id" value="<?= (int) $review['review_id'] ?>">
                                <button type="submit" class="btn-link danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
