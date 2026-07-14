<?php
/**
 * CampusTrade — shared helper functions.
 */

/** Escape user content for safe HTML output (XSS prevention). */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Is a user currently logged in? */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Is the current user an admin? */
function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['role'] ?? 'user') === 'admin';
}

/** Guard for protected pages: redirect guests to the login page. */
function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['flash'] = 'Please log in to access that page.';
        header('Location: login.php');
        exit;
    }
}

/** Set a one-time flash message shown on the next page load. */
function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = $message;
    $_SESSION['flash_type'] = $type;
}

/** Fetch and clear the flash message. Returns [message, type] or null. */
function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = [$_SESSION['flash'], $_SESSION['flash_type'] ?? 'success'];
    unset($_SESSION['flash'], $_SESSION['flash_type']);
    return $flash;
}

/** Format a price in Malaysian Ringgit. */
function format_price(float $price): string
{
    return 'RM ' . number_format($price, 2);
}

/** Human-friendly date, e.g. "12 Jul 2026". */
function format_date(string $datetime): string
{
    return date('j M Y', strtotime($datetime));
}

/** CSS modifier class for a condition badge. */
function condition_class(string $condition): string
{
    return 'badge-' . strtolower(str_replace(' ', '-', $condition));
}

/** CSS modifier class for a status badge. */
function status_class(string $status): string
{
    return 'status-' . strtolower($status);
}

/** Path to a listing image, falling back to the placeholder. */
function listing_image(?string $image): string
{
    if ($image !== null && $image !== '' && is_file(UPLOAD_DIR . $image)) {
        return 'uploads/' . rawurlencode($image);
    }
    return 'images/placeholder.svg';
}

/** Path to a user's profile picture, falling back to the default avatar. */
function user_avatar(?string $image): string
{
    if ($image !== null && $image !== '' && $image !== 'default.png' && is_file(UPLOAD_DIR . $image)) {
        return 'uploads/' . rawurlencode($image);
    }
    return 'images/avatar.svg';
}

/** Render star icons (★/☆) for a rating of 1–5. */
function render_stars(float $rating): string
{
    $rounded = (int) round($rating);
    return str_repeat('★', $rounded) . str_repeat('☆', 5 - $rounded);
}

/**
 * Validate and store an uploaded image.
 * Returns the stored filename, or null if no file was chosen.
 * Throws RuntimeException with a user-friendly message on failure.
 *
 * $allowed maps accepted MIME types to file extensions; $max_bytes
 * is the size limit; $prefix names the stored file (e.g. item_, avatar_).
 */
function handle_image_upload(
    array $file,
    ?array $allowed = null,
    int $max_bytes = MAX_UPLOAD_BYTES,
    string $prefix = 'item_'
): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }
    if ($file['size'] > $max_bytes) {
        throw new RuntimeException('Image is too large (max ' . round($max_bytes / 1048576) . ' MB).');
    }

    if ($allowed === null) {
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
    }

    // Real MIME check — do not trust the browser-supplied type.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        $names = strtoupper(implode(', ', array_unique(array_values($allowed))));
        throw new RuntimeException("Only $names images are allowed.");
    }

    // Rename with a unique id to prevent directory traversal / collisions.
    $filename = uniqid($prefix, true) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    return $filename;
}

/**
 * Save a cropped image submitted as a base64 data URL (from the
 * crop/resize tool on the create/edit listing pages).
 * Returns the stored filename. Throws RuntimeException on invalid data.
 */
function save_base64_image(string $data_url, string $prefix = 'item_'): string
{
    if (!preg_match('#^data:image/(jpeg|png|webp);base64,#', $data_url, $match)) {
        throw new RuntimeException('Invalid cropped image data.');
    }
    $binary = base64_decode(substr($data_url, strpos($data_url, ',') + 1), true);
    if ($binary === false) {
        throw new RuntimeException('Cropped image could not be decoded.');
    }
    if (strlen($binary) > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Image is too large (max ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB).');
    }
    // Verify the decoded bytes are really an image
    if (getimagesizefromstring($binary) === false) {
        throw new RuntimeException('Cropped data is not a valid image.');
    }

    $extensions = ['jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];
    $filename = uniqid($prefix, true) . '.' . $extensions[$match[1]];
    if (file_put_contents(UPLOAD_DIR . $filename, $binary) === false) {
        throw new RuntimeException('Could not save the cropped image.');
    }
    return $filename;
}

/**
 * Resolve the listing image for a form: prefers the cropped base64 data
 * (crop tool), falls back to the plain file upload (no-JS fallback).
 * Returns the stored filename or null when nothing was submitted.
 */
function handle_listing_image(): ?string
{
    $cropped = $_POST['cropped_image'] ?? '';
    if ($cropped !== '') {
        return save_base64_image($cropped);
    }
    return handle_image_upload($_FILES['image'] ?? []);
}

/** Upload rules for profile pictures, QR codes and payment proofs: JPEG/PNG/WebP, max 10 MB. */
function handle_profile_image_upload(array $file, string $prefix = 'avatar_'): ?string
{
    return handle_image_upload(
        $file,
        ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
        PROFILE_MAX_UPLOAD_BYTES,
        $prefix
    );
}

/** Whether a listing row (with is_discounted/original_price) has an active discount. */
function has_discount(array $listing): bool
{
    return !empty($listing['is_discounted']) && $listing['original_price'] !== null;
}

/** Discount percentage for a discounted listing, e.g. 20 for "20% OFF". */
function discount_pct(array $listing): int
{
    $original = (float) $listing['original_price'];
    if ($original <= 0) {
        return 0;
    }
    return (int) round(($original - (float) $listing['price']) / $original * 100);
}

/** Price markup for cards/details: strikethrough original + red price when discounted. */
function price_html(array $listing): string
{
    if (has_discount($listing)) {
        return '<span class="original-price">' . format_price((float) $listing['original_price']) . '</span> '
             . '<span class="discounted-price">' . format_price((float) $listing['price']) . '</span>';
    }
    return format_price((float) $listing['price']);
}

/**
 * Browse URL for "Find Similar Items": same category, price within ±30%
 * of the sold item, sorted by price similarity (near=).
 */
function similar_items_url(array $listing): string
{
    $price = (float) $listing['price'];
    return 'browse.php?' . http_build_query([
        'category'  => $listing['category'],
        'min_price' => number_format($price * 0.7, 2, '.', ''),
        'max_price' => number_format($price * 1.3, 2, '.', ''),
        'near'      => number_format($price, 2, '.', ''),
    ]);
}

/**
 * A seller's average rating and review count as ['avg' => float, 'count' => int].
 */
function seller_rating(mysqli $conn, int $seller_id): array
{
    $stmt = $conn->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE seller_id = ?');
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['avg' => (float) ($row['avg_rating'] ?? 0), 'count' => (int) $row['total']];
}

/**
 * Find or create the conversation for a listing + buyer.
 * Returns the conversation_id.
 */
function get_or_create_conversation(mysqli $conn, int $listing_id, int $buyer_id, int $seller_id): int
{
    $stmt = $conn->prepare('SELECT conversation_id FROM conversations WHERE listing_id = ? AND buyer_id = ?');
    $stmt->bind_param('ii', $listing_id, $buyer_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        return (int) $row['conversation_id'];
    }

    $stmt = $conn->prepare('INSERT INTO conversations (listing_id, buyer_id, seller_id) VALUES (?, ?, ?)');
    $stmt->bind_param('iii', $listing_id, $buyer_id, $seller_id);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

/** Delete a stored listing image file (ignores the placeholder / missing files). */
function delete_image_file(?string $image): void
{
    if ($image !== null && $image !== '' && is_file(UPLOAD_DIR . $image)) {
        unlink(UPLOAD_DIR . $image);
    }
}

/**
 * Build a query string for pagination/filter links, overriding the given keys.
 * Keeps existing GET parameters so filters survive page changes.
 */
function build_query(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return http_build_query($params);
}
