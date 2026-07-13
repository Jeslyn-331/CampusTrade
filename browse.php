<?php
require_once __DIR__ . '/includes/db.php';

// ---- Read filter inputs ----
// Category can arrive as a single value (nav links) or an array (sidebar checkboxes).
$raw_category = $_GET['category'] ?? [];
$selected_categories = is_array($raw_category) ? $raw_category : [$raw_category];
$selected_categories = array_values(array_intersect($selected_categories, CATEGORIES));

$raw_condition = $_GET['condition'] ?? [];
$selected_conditions = is_array($raw_condition) ? $raw_condition : [$raw_condition];
$selected_conditions = array_values(array_intersect($selected_conditions, CONDITIONS));

$search    = trim($_GET['search'] ?? '');
$min_price = ($_GET['min_price'] ?? '') !== '' ? max(0, (float) $_GET['min_price']) : null;
$max_price = ($_GET['max_price'] ?? '') !== '' ? max(0, (float) $_GET['max_price']) : null;
$on_sale   = ($_GET['on_sale'] ?? '') === '1';

// "Find Similar Items" mode (from sold wishlist items): sort by price similarity
$near = ($_GET['near'] ?? '') !== '' ? max(0.0, (float) $_GET['near']) : null;

$sort_options = [
    'newest'     => ['label' => 'Newest First',       'sql' => 'l.created_at DESC'],
    'price_asc'  => ['label' => 'Price: Low to High', 'sql' => 'l.price ASC'],
    'price_desc' => ['label' => 'Price: High to Low', 'sql' => 'l.price DESC'],
    'discount'   => ['label' => 'Biggest Discount',   'sql' => 'COALESCE((l.original_price - l.price) / l.original_price, 0) DESC, l.created_at DESC'],
];
$sort = isset($sort_options[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'newest';

// Similarity ordering (closest price first) unless the user explicitly picked a sort.
// $near is a sanitised float, so embedding it in the ORDER BY is safe.
$order_sql = $sort_options[$sort]['sql'];
if ($near !== null && !isset($_GET['sort'])) {
    $order_sql = 'ABS(l.price - ' . $near . ') ASC';
}

$page = max(1, (int) ($_GET['page'] ?? 1));

// ---- Build the WHERE clause with bound parameters ----
$where  = ["l.status <> 'Sold'"];
$params = [];
$types  = '';

if ($selected_categories) {
    $where[] = 'l.category IN (' . implode(',', array_fill(0, count($selected_categories), '?')) . ')';
    foreach ($selected_categories as $c) { $params[] = $c; $types .= 's'; }
}
if ($selected_conditions) {
    $where[] = 'l.item_condition IN (' . implode(',', array_fill(0, count($selected_conditions), '?')) . ')';
    foreach ($selected_conditions as $c) { $params[] = $c; $types .= 's'; }
}
if ($search !== '') {
    $where[] = '(l.title LIKE ? OR l.description LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like;
    $types .= 'ss';
}
if ($min_price !== null) {
    $where[] = 'l.price >= ?';
    $params[] = $min_price; $types .= 'd';
}
if ($max_price !== null) {
    $where[] = 'l.price <= ?';
    $params[] = $max_price; $types .= 'd';
}
if ($on_sale) {
    $where[] = 'l.is_discounted = 1';
}
$where_sql = implode(' AND ', $where);

// ---- Count matching rows for pagination ----
$stmt = $conn->prepare("SELECT COUNT(*) FROM listings l WHERE $where_sql");
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_items = (int) $stmt->get_result()->fetch_row()[0];
$stmt->close();

$total_pages = max(1, (int) ceil($total_items / ITEMS_PER_PAGE));
$page = min($page, $total_pages);
$offset = ($page - 1) * ITEMS_PER_PAGE;

// ---- Fetch the current page of listings ----
$sql = "SELECT l.listing_id, l.title, l.price, l.original_price, l.is_discounted, l.item_condition, l.image, l.status, l.created_at, u.username, u.profile_image
        FROM listings l
        JOIN users u ON l.user_id = u.user_id
        WHERE $where_sql
        ORDER BY $order_sql
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$limit = ITEMS_PER_PAGE;
$page_params = $params;
$page_params[] = $limit;
$page_params[] = $offset;
$page_types = $types . 'ii';
$stmt->bind_param($page_types, ...$page_params);
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'Browse Listings';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="page-title">Browse Listings</h1>
    <?php if ($search !== ''): ?>
        <p class="muted">Search results for &ldquo;<strong><?= e($search) ?></strong>&rdquo; — <?= $total_items ?> item(s) found.</p>
    <?php endif; ?>
    <?php if ($near !== null): ?>
        <div class="alert alert-success">
            Showing available items similar to your sold wishlist item — same category,
            price within &plusmn;30% of <?= format_price($near) ?>, closest price first.
        </div>
    <?php endif; ?>

    <div class="browse-layout">
        <!-- Filter sidebar -->
        <aside class="filter-sidebar">
            <button type="button" class="btn btn-outline filter-toggle" id="filterToggle">Filters &#9662;</button>
            <form method="get" action="browse.php" class="filter-form" id="filterForm">
                <?php if ($search !== ''): ?>
                    <input type="hidden" name="search" value="<?= e($search) ?>">
                <?php endif; ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">

                <fieldset>
                    <legend>Category</legend>
                    <?php foreach (CATEGORIES as $cat): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="category[]" value="<?= e($cat) ?>"
                                <?= in_array($cat, $selected_categories, true) ? 'checked' : '' ?>>
                            <?= e($cat) ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset>
                    <legend>Condition</legend>
                    <?php foreach (CONDITIONS as $cond): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="condition[]" value="<?= e($cond) ?>"
                                <?= in_array($cond, $selected_conditions, true) ? 'checked' : '' ?>>
                            <?= e($cond) ?>
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset>
                    <legend>Deals</legend>
                    <label class="checkbox-label">
                        <input type="checkbox" name="on_sale" value="1" <?= $on_sale ? 'checked' : '' ?>>
                        On Sale (discounted only)
                    </label>
                </fieldset>

                <fieldset>
                    <legend>Price Range (RM)</legend>
                    <div class="price-inputs">
                        <input type="number" name="min_price" min="0" step="0.01" placeholder="Min"
                               value="<?= $min_price !== null ? e((string) $min_price) : '' ?>" aria-label="Minimum price">
                        <span>&ndash;</span>
                        <input type="number" name="max_price" min="0" step="0.01" placeholder="Max"
                               value="<?= $max_price !== null ? e((string) $max_price) : '' ?>" aria-label="Maximum price">
                    </div>
                </fieldset>

                <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                <a href="browse.php" class="btn btn-outline btn-block">Clear All</a>
            </form>
        </aside>

        <!-- Results -->
        <section class="browse-results">
            <div class="browse-toolbar">
                <span class="muted"><?= $total_items ?> item(s)</span>
                <form method="get" action="browse.php" class="sort-form">
                    <?php // Preserve current filters when changing sort
                    foreach ($selected_categories as $c): ?>
                        <input type="hidden" name="category[]" value="<?= e($c) ?>">
                    <?php endforeach;
                    foreach ($selected_conditions as $c): ?>
                        <input type="hidden" name="condition[]" value="<?= e($c) ?>">
                    <?php endforeach;
                    if ($search !== ''): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif;
                    if ($min_price !== null): ?><input type="hidden" name="min_price" value="<?= e((string) $min_price) ?>"><?php endif;
                    if ($max_price !== null): ?><input type="hidden" name="max_price" value="<?= e((string) $max_price) ?>"><?php endif;
                    if ($on_sale): ?><input type="hidden" name="on_sale" value="1"><?php endif;
                    if ($near !== null): ?><input type="hidden" name="near" value="<?= e((string) $near) ?>"><?php endif; ?>
                    <label for="sort">Sort by:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()">
                        <?php foreach ($sort_options as $key => $opt): ?>
                            <option value="<?= e($key) ?>" <?= $key === $sort ? 'selected' : '' ?>><?= e($opt['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if (!$listings): ?>
                <?php if ($near !== null): ?>
                    <div class="empty-state">
                        <p><strong>No similar items available right now. We'll keep looking!</strong></p>
                        <p>In the meantime, you can browse everything in this category.</p>
                        <?php if ($selected_categories): ?>
                            <a href="browse.php?category=<?= urlencode($selected_categories[0]) ?>" class="btn btn-primary">
                                Browse All <?= e($selected_categories[0]) ?>
                            </a>
                        <?php else: ?>
                            <a href="browse.php" class="btn btn-primary">View All Listings</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p><strong>No items match your search.</strong></p>
                        <p>Try removing some filters or using different keywords.</p>
                        <a href="browse.php" class="btn btn-primary">View All Listings</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($listings as $item): ?>
                        <a class="item-card <?= $item['status'] === 'Reserved' ? 'is-reserved' : '' ?>"
                           href="item.php?id=<?= (int) $item['listing_id'] ?>">
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

                <?php if ($total_pages > 1): ?>
                    <nav class="pagination" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="browse.php?<?= e(build_query(['page' => $page - 1])) ?>">&laquo; Prev</a>
                        <?php endif; ?>
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <?php if ($p === $page): ?>
                                <span class="current"><?= $p ?></span>
                            <?php else: ?>
                                <a href="browse.php?<?= e(build_query(['page' => $p])) ?>"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="browse.php?<?= e(build_query(['page' => $page + 1])) ?>">Next &raquo;</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
