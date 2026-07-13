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
            'image/gif'  => 'gif',
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

/** Upload rules for profile pictures and QR codes: JPEG/PNG only, max 10 MB. */
function handle_profile_image_upload(array $file, string $prefix = 'avatar_'): ?string
{
    return handle_image_upload(
        $file,
        ['image/jpeg' => 'jpg', 'image/png' => 'png'],
        PROFILE_MAX_UPLOAD_BYTES,
        $prefix
    );
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
