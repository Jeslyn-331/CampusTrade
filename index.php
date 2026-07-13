<?php
require_once __DIR__ . '/includes/db.php';

// ---- Platform statistics: public-friendly metrics only ----
// (Items sold / earnings / user counts are private to each seller's dashboard.)
$stats = $conn->query(
    "SELECT COUNT(*) AS available_listings FROM listings WHERE status = 'Available'"
)->fetch_assoc();

// ---- Featured listings: 8 most recent available items ----
$featured = $conn->query(
    "SELECT l.listing_id, l.title, l.price, l.item_condition, l.image, l.created_at, u.username, u.profile_image
     FROM listings l
     JOIN users u ON l.user_id = u.user_id
     WHERE l.status = 'Available'
     ORDER BY l.created_at DESC
     LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

$page_title = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero banner -->
<section class="hero">
    <div class="container hero-content">
        <h1>Buy &amp; Sell Within Your Campus</h1>
        <p>Textbooks, electronics, furniture and more — traded student to student, right on campus.</p>
        <form action="browse.php" method="get" class="hero-search" role="search">
            <input type="text" name="search" placeholder="What are you looking for? e.g. calculus textbook"
                   aria-label="Search listings">
            <button type="submit" class="btn btn-accent">Search</button>
        </form>
    </div>
</section>

<!-- Platform statistics (public-friendly metrics only) -->
<section class="stats-bar">
    <div class="container stats-grid stats-grid-2">
        <div class="stat">
            <span class="stat-number"><?= (int) $stats['available_listings'] ?></span>
            <span class="stat-label">Items Available Now</span>
        </div>
        <div class="stat">
            <span class="stat-number"><?= count(CATEGORIES) ?></span>
            <span class="stat-label">Categories to Explore</span>
        </div>
    </div>
</section>

<!-- Category quick-links -->
<section class="container section">
    <h2 class="section-title">Shop by Category</h2>
    <div class="category-grid">
        <?php
        $category_icons = [
            'Textbooks'   => '&#128218;',
            'Electronics' => '&#128187;',
            'Furniture'   => '&#129681;',
            'Stationery'  => '&#9999;&#65039;',
            'Clothing'    => '&#128085;',
            'Others'      => '&#128230;',
        ];
        foreach (CATEGORIES as $cat): ?>
            <a class="category-card" href="browse.php?category=<?= urlencode($cat) ?>">
                <span class="category-icon"><?= $category_icons[$cat] ?></span>
                <span class="category-name"><?= e($cat) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Featured listings -->
<section class="container section">
    <div class="section-head">
        <h2 class="section-title">Latest Listings</h2>
        <a href="browse.php" class="see-all">See all &rarr;</a>
    </div>

    <?php if (!$featured): ?>
        <p class="empty-state">No listings yet — be the first to <a href="create_listing.php">sell an item</a>!</p>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($featured as $item): ?>
                <a class="item-card" href="item.php?id=<?= (int) $item['listing_id'] ?>">
                    <div class="item-image">
                        <img src="<?= e(listing_image($item['image'])) ?>" alt="<?= e($item['title']) ?>">
                        <span class="badge <?= condition_class($item['item_condition']) ?>"><?= e($item['item_condition']) ?></span>
                    </div>
                    <div class="item-body">
                        <h3 class="item-title"><?= e($item['title']) ?></h3>
                        <p class="item-price"><?= format_price((float) $item['price']) ?></p>
                        <p class="item-meta">
                            <span class="seller-chip">
                                <img src="<?= e(user_avatar($item['profile_image'])) ?>" alt="" class="avatar avatar-xs">
                                <?= e($item['username']) ?>
                            </span>
                            <span><?= format_date($item['created_at']) ?></span>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- Call to action -->
<section class="cta-strip">
    <div class="container">
        <h2>Got something you no longer need?</h2>
        <p>Turn your unused textbooks and gadgets into cash — list them in under a minute.</p>
        <a href="<?= is_logged_in() ? 'create_listing.php' : 'register.php' ?>" class="btn btn-accent">Start Selling</a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
