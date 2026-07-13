<?php
require_once __DIR__ . '/includes/db.php';

$seller_id = (int) ($_GET['id'] ?? $_POST['seller_id'] ?? 0);

// ---- Review actions (submit / delete) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    if ($action === 'add_review') {
        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $listing_ref = (int) ($_POST['listing_id'] ?? 0);

        // Only buyers with a Completed order with this seller may review
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND seller_id = ? AND status = 'Completed'");
        $stmt->bind_param('ii', $user_id, $seller_id);
        $stmt->execute();
        $has_completed_order = (int) $stmt->get_result()->fetch_row()[0] > 0;
        $stmt->close();

        if ($user_id === $seller_id) {
            set_flash('You cannot review yourself.', 'error');
        } elseif (!$has_completed_order) {
            set_flash('You can only review a seller after completing a transaction with them.', 'error');
        } elseif ($rating < 1 || $rating > 5) {
            set_flash('Please choose a star rating between 1 and 5.', 'error');
        } else {
            // Optional listing reference must belong to this seller
            $listing_value = null;
            if ($listing_ref > 0) {
                $stmt = $conn->prepare('SELECT listing_id FROM listings WHERE listing_id = ? AND user_id = ?');
                $stmt->bind_param('ii', $listing_ref, $seller_id);
                $stmt->execute();
                if ($stmt->get_result()->fetch_row()) {
                    $listing_value = $listing_ref;
                }
                $stmt->close();
            }
            $comment_value = $comment === '' ? null : $comment;
            $stmt = $conn->prepare('INSERT INTO reviews (seller_id, reviewer_id, listing_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('iiiis', $seller_id, $user_id, $listing_value, $rating, $comment_value);
            $stmt->execute();
            $stmt->close();
            set_flash('Thanks — your review has been posted.');
        }
    } elseif ($action === 'delete_review') {
        $review_id = (int) ($_POST['review_id'] ?? 0);
        // Users may delete their own review; admins may delete any
        if (is_admin()) {
            $stmt = $conn->prepare('DELETE FROM reviews WHERE review_id = ?');
            $stmt->bind_param('i', $review_id);
        } else {
            $stmt = $conn->prepare('DELETE FROM reviews WHERE review_id = ? AND reviewer_id = ?');
            $stmt->bind_param('ii', $review_id, $user_id);
        }
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            set_flash('Review deleted.');
        }
        $stmt->close();
    }

    header('Location: seller.php?id=' . $seller_id);
    exit;
}

// ---- Load the seller ----
$stmt = $conn->prepare('SELECT user_id, username, profile_image, created_at FROM users WHERE user_id = ?');
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$seller) {
    http_response_code(404);
    $page_title = 'Seller Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container"><div class="empty-state">
            <p><strong>This user does not exist.</strong></p>
            <a href="browse.php" class="btn btn-primary">Browse Listings</a>
          </div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ---- Seller statistics ----
$stmt = $conn->prepare(
    "SELECT
        (SELECT COUNT(*) FROM listings WHERE user_id = ?)                      AS total_listings,
        (SELECT COUNT(*) FROM listings WHERE user_id = ? AND status = 'Sold')  AS items_sold"
);
$stmt->bind_param('ii', $seller_id, $seller_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$rating = seller_rating($conn, $seller_id);

// ---- Active listings by this seller ----
$stmt = $conn->prepare(
    "SELECT listing_id, title, price, original_price, is_discounted, item_condition, image, status, created_at
     FROM listings
     WHERE user_id = ? AND status <> 'Sold'
     ORDER BY created_at DESC"
);
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$active_listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---- All reviews of this seller ----
$stmt = $conn->prepare(
    'SELECT r.review_id, r.reviewer_id, r.rating, r.comment, r.created_at,
            u.username AS reviewer_name, u.profile_image AS reviewer_image,
            l.title AS listing_title, l.listing_id
     FROM reviews r
     JOIN users u ON r.reviewer_id = u.user_id
     LEFT JOIN listings l ON r.listing_id = l.listing_id
     WHERE r.seller_id = ?
     ORDER BY r.created_at DESC'
);
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---- Can the viewer submit a review? (Completed order required) ----
$can_review = false;
$reviewable_listings = [];
if (is_logged_in() && $_SESSION['user_id'] !== $seller_id) {
    $stmt = $conn->prepare(
        "SELECT DISTINCT l.listing_id, l.title
         FROM orders o
         JOIN listings l ON o.listing_id = l.listing_id
         WHERE o.buyer_id = ? AND o.seller_id = ? AND o.status = 'Completed'"
    );
    $stmt->bind_param('ii', $_SESSION['user_id'], $seller_id);
    $stmt->execute();
    $reviewable_listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $can_review = count($reviewable_listings) > 0;
}

$page_title = $seller['username'] . "'s Profile";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Seller header -->
    <section class="seller-profile-head card">
        <img src="<?= e(user_avatar($seller['profile_image'])) ?>"
             alt="<?= e($seller['username']) ?>'s profile picture" class="avatar avatar-xl">
        <div>
            <h1><?= e($seller['username']) ?></h1>
            <p class="muted">Member since <?= format_date($seller['created_at']) ?></p>
            <?php if ($rating['count'] > 0): ?>
                <p class="stars"><?= render_stars($rating['avg']) ?>
                    <span class="muted"><?= number_format($rating['avg'], 1) ?> from <?= $rating['count'] ?> review<?= $rating['count'] === 1 ? '' : 's' ?></span></p>
            <?php else: ?>
                <p class="muted">No reviews yet</p>
            <?php endif; ?>
        </div>
        <div class="seller-profile-stats">
            <div class="stat">
                <span class="stat-number"><?= (int) $stats['total_listings'] ?></span>
                <span class="stat-label">Listings</span>
            </div>
            <div class="stat">
                <span class="stat-number"><?= (int) $stats['items_sold'] ?></span>
                <span class="stat-label">Items Sold</span>
            </div>
            <div class="stat">
                <span class="stat-number"><?= $rating['count'] > 0 ? number_format($rating['avg'], 1) : '—' ?></span>
                <span class="stat-label">Avg Rating</span>
            </div>
        </div>
    </section>

    <!-- Active listings -->
    <section class="section">
        <h2 class="section-title">Active Listings (<?= count($active_listings) ?>)</h2>
        <?php if (!$active_listings): ?>
            <p class="empty-state">This seller has no active listings right now.</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($active_listings as $item): ?>
                    <a class="item-card" href="item.php?id=<?= (int) $item['listing_id'] ?>">
                        <div class="item-image">
                            <img src="<?= e(listing_image($item['image'])) ?>" alt="<?= e($item['title']) ?>">
                            <span class="badge <?= condition_class($item['item_condition']) ?>"><?= e($item['item_condition']) ?></span>
                            <?php if ($item['status'] === 'Reserved'): ?>
                                <span class="badge status-reserved">Reserved</span>
                            <?php elseif (has_discount($item)): ?>
                                <span class="discount-badge"><?= discount_pct($item) ?>% OFF</span>
                            <?php endif; ?>
                        </div>
                        <div class="item-body">
                            <h3 class="item-title"><?= e($item['title']) ?></h3>
                            <p class="item-price"><?= price_html($item) ?></p>
                            <p class="item-meta"><span><?= format_date($item['created_at']) ?></span></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Reviews -->
    <section class="reviews-section">
        <h2>Seller Reviews (<?= count($reviews) ?>)</h2>

        <?php if ($can_review): ?>
            <form method="post" action="seller.php" class="review-form card" id="reviewForm">
                <input type="hidden" name="action" value="add_review">
                <input type="hidden" name="seller_id" value="<?= $seller_id ?>">

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
                    <label for="listing_id">Which item was your transaction for?</label>
                    <select id="listing_id" name="listing_id">
                        <option value="0">— Prefer not to say —</option>
                        <?php foreach ($reviewable_listings as $l): ?>
                            <option value="<?= (int) $l['listing_id'] ?>"><?= e($l['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="comment">Comment (optional)</label>
                    <textarea id="comment" name="comment" rows="3" maxlength="1000"
                              placeholder="How was your experience with this seller?"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Post Review</button>
            </form>
        <?php elseif (is_logged_in() && $_SESSION['user_id'] !== $seller_id): ?>
            <p class="muted">You can review this seller after completing a transaction with them.</p>
        <?php elseif (!is_logged_in()): ?>
            <p class="muted"><a href="login.php">Log in</a> to review this seller (a completed transaction is required).</p>
        <?php endif; ?>

        <?php if (!$reviews): ?>
            <p class="empty-state">No reviews for this seller yet.</p>
        <?php else: ?>
            <ul class="review-list">
                <?php foreach ($reviews as $review): ?>
                    <li class="review card">
                        <div class="review-head">
                            <img src="<?= e(user_avatar($review['reviewer_image'])) ?>" alt="" class="avatar">
                            <strong><?= e($review['reviewer_name']) ?></strong>
                            <span class="stars"><?= render_stars((float) $review['rating']) ?></span>
                            <span class="muted"><?= format_date($review['created_at']) ?></span>
                        </div>
                        <?php if ($review['listing_title']): ?>
                            <p class="muted review-item-ref">Item:
                                <a href="item.php?id=<?= (int) $review['listing_id'] ?>"><?= e($review['listing_title']) ?></a></p>
                        <?php endif; ?>
                        <?php if ($review['comment']): ?>
                            <p><?= nl2br(e($review['comment'])) ?></p>
                        <?php endif; ?>
                        <?php if (is_logged_in() && ((int) $review['reviewer_id'] === $_SESSION['user_id'] || is_admin())): ?>
                            <form method="post" action="seller.php" onsubmit="return confirm('Delete this review?');">
                                <input type="hidden" name="action" value="delete_review">
                                <input type="hidden" name="seller_id" value="<?= $seller_id ?>">
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
