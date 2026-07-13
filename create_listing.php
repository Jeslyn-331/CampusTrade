<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$errors = [];
$title = '';
$description = '';
$price = '';
$category = '';
$item_condition = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $price          = trim($_POST['price'] ?? '');
    $category       = $_POST['category'] ?? '';
    $item_condition = $_POST['item_condition'] ?? '';

    // ---- Server-side validation ----
    if ($title === '' || mb_strlen($title) > 100) {
        $errors[] = 'Title is required (max 100 characters).';
    }
    if ($description === '' || mb_strlen($description) < 10) {
        $errors[] = 'Description is required (at least 10 characters).';
    }
    if (!is_numeric($price) || (float) $price <= 0 || (float) $price > 99999999.99) {
        $errors[] = 'Please enter a valid price greater than 0.';
    }
    if (!in_array($category, CATEGORIES, true)) {
        $errors[] = 'Please choose a valid category.';
    }
    if (!in_array($item_condition, CONDITIONS, true)) {
        $errors[] = 'Please choose a valid condition.';
    }

    $image = null;
    if (!$errors) {
        try {
            $image = handle_listing_image();
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $price_value = (float) $price;
        $stmt = $conn->prepare(
            'INSERT INTO listings (user_id, title, description, price, category, item_condition, image)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issdsss', $_SESSION['user_id'], $title, $description, $price_value, $category, $item_condition, $image);
        $stmt->execute();
        $new_id = $stmt->insert_id;
        $stmt->close();

        set_flash('Your item has been listed!');
        header('Location: item.php?id=' . $new_id);
        exit;
    }
}

$page_title = 'Sell an Item';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-card">
        <h1>Sell an Item</h1>
        <p class="muted">Fill in the details below — good photos and honest descriptions sell faster.</p>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="create_listing.php" enctype="multipart/form-data" id="listingForm" novalidate>
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required maxlength="100"
                       value="<?= e($title) ?>" placeholder="e.g. Calculus Textbook 8th Edition">
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="5" required minlength="10" maxlength="5000"
                          placeholder="Condition details, reason for selling, collection point..."><?= e($description) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Price (RM) *</label>
                    <input type="number" id="price" name="price" required min="0.01" step="0.01"
                           value="<?= e($price) ?>" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">— Select —</option>
                        <?php foreach (CATEGORIES as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="item_condition">Condition *</label>
                    <select id="item_condition" name="item_condition" required>
                        <option value="">— Select —</option>
                        <?php foreach (CONDITIONS as $cond): ?>
                            <option value="<?= e($cond) ?>" <?= $item_condition === $cond ? 'selected' : '' ?>><?= e($cond) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="image">Item Photo (JPEG/PNG, max 10 MB)</label>
                <?php require __DIR__ . '/includes/crop_tool.php'; ?>
                <small>After choosing a photo, the crop tool opens so you can frame it before publishing.</small>
            </div>

            <button type="submit" class="btn btn-primary">Publish Listing</button>
            <a href="dashboard.php" class="btn btn-outline">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
