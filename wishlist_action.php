<?php
/**
 * Handles adding/removing wishlist items (POST only), then redirects back.
 */
require_once __DIR__ . '/includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: wishlist.php');
    exit;
}

$user_id    = $_SESSION['user_id'];
$listing_id = (int) ($_POST['listing_id'] ?? 0);
$action     = $_POST['action'] ?? '';

// Only allow redirects back to local pages (no external URLs)
$redirect = $_POST['redirect'] ?? 'wishlist.php';
if (!preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&]*)?$/', $redirect)) {
    $redirect = 'wishlist.php';
}

if ($action === 'add') {
    // Verify listing exists and is not the user's own
    $stmt = $conn->prepare('SELECT user_id FROM listings WHERE listing_id = ?');
    $stmt->bind_param('i', $listing_id);
    $stmt->execute();
    $listing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$listing) {
        set_flash('That listing no longer exists.', 'error');
    } elseif ((int) $listing['user_id'] === $user_id) {
        set_flash('You cannot add your own listing to your wishlist.', 'error');
    } else {
        // INSERT IGNORE respects the UNIQUE (user_id, listing_id) constraint
        $stmt = $conn->prepare('INSERT IGNORE INTO wishlist (user_id, listing_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $user_id, $listing_id);
        $stmt->execute();
        $stmt->close();
        set_flash('Item added to your wishlist.');
    }
} elseif ($action === 'remove') {
    // Ownership check in the WHERE clause
    $stmt = $conn->prepare('DELETE FROM wishlist WHERE user_id = ? AND listing_id = ?');
    $stmt->bind_param('ii', $user_id, $listing_id);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        set_flash('Item removed from your wishlist.');
    }
    $stmt->close();
}

header('Location: ' . $redirect);
exit;
