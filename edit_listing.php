<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$listing_id = (int) ($_GET['id'] ?? $_POST['listing_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Ownership check: only the seller may edit their listing
$stmt = $conn->prepare('SELECT * FROM listings WHERE listing_id = ? AND user_id = ?');
$stmt->bind_param('ii', $listing_id, $user_id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$listing) {
    set_flash('Listing not found, or you do not have permission to edit it.', 'error');
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$title          = $listing['title'];
$description    = $listing['description'];
$price          = $listing['price'];
$category       = $listing['category'];
$item_condition = $listing['item_condition'];
$status         = $listing['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $price          = trim($_POST['price'] ?? '');
    $category       = $_POST['category'] ?? '';
    $item_condition = $_POST['item_condition'] ?? '';
    $status         = $_POST['status'] ?? '';

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
    if (!in_array($status, ['Available', 'Sold', 'Reserved'], true)) {
        $errors[] = 'Please choose a valid status.';
    }

    $new_image = null;
    if (!$errors) {
        try {
            $new_image = handle_image_upload($_FILES['image'] ?? []);
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $price_value = (float) $price;
        if ($new_image !== null) {
            delete_image_file($listing['image']);
            $stmt = $conn->prepare(
                'UPDATE listings SET title = ?, description = ?, price = ?, category = ?, item_condition = ?, status = ?, image = ?
                 WHERE listing_id = ? AND user_id = ?'
            );
            $stmt->bind_param('ssdssssii', $title, $description, $price_value, $category, $item_condition, $status, $new_image, $listing_id, $user_id);
        } else {
            $stmt = $conn->prepare(
                'UPDATE listings SET title = ?, description = ?, price = ?, category = ?, item_condition = ?, status = ?
                 WHERE listing_id = ? AND user_id = ?'
            );
            $stmt->bind_param('ssdsssii', $title, $description, $price_value, $category, $item_condition, $status, $listing_id, $user_id);
        }
        $stmt->execute();
        $stmt->close();

        set_flash('Listing updated successfully.');
        header('Location: item.php?id=' . $listing_id);
        exit;
    }
}

$page_title = 'Edit Listing';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="form-card">
        <h1>Edit Listing</h1>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="edit_listing.php?id=<?= $listing_id ?>" enctype="multipart/form-data" id="listingForm" novalidate>
            <input type="hidden" name="listing_id" value="<?= $listing_id ?>">

            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required maxlength="100" value="<?= e($title) ?>">
            </div>

            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="5" required minlength="10" maxlength="5000"><?= e($description) ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Price (RM) *</label>
                    <input type="number" id="price" name="price" required min="0.01" step="0.01" value="<?= e((string) $price) ?>">
                </div>

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <?php foreach (CATEGORIES as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="item_condition">Condition *</label>
                    <select id="item_condition" name="item_condition" required>
                        <?php foreach (CONDITIONS as $cond): ?>
                            <option value="<?= e($cond) ?>" <?= $item_condition === $cond ? 'selected' : '' ?>><?= e($cond) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <?php foreach (['Available', 'Reserved', 'Sold'] as $st): ?>
                            <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="image">Replace Photo (optional, max 2 MB)</label>
                <?php if ($listing['image']): ?>
                    <div class="current-image">
                        <img src="<?= e(listing_image($listing['image'])) ?>" alt="Current photo">
                        <small>Current photo — uploading a new one replaces it.</small>
                    </div>
                <?php endif; ?>
                <input type="file" id="image" name="image" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="item.php?id=<?= $listing_id ?>" class="btn btn-outline">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
