<?php
require_once __DIR__ . '/includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$listing_id = (int) ($_POST['listing_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Fetch first so we can remove the image file; admins may delete any listing
if (is_admin()) {
    $stmt = $conn->prepare('SELECT image FROM listings WHERE listing_id = ?');
    $stmt->bind_param('i', $listing_id);
} else {
    $stmt = $conn->prepare('SELECT image FROM listings WHERE listing_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $listing_id, $user_id);
}
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$listing) {
    set_flash('Listing not found, or you do not have permission to delete it.', 'error');
    header('Location: dashboard.php');
    exit;
}

// Delete row (wishlist entries and reviews cascade via foreign keys)
if (is_admin()) {
    $stmt = $conn->prepare('DELETE FROM listings WHERE listing_id = ?');
    $stmt->bind_param('i', $listing_id);
} else {
    $stmt = $conn->prepare('DELETE FROM listings WHERE listing_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $listing_id, $user_id);
}
$stmt->execute();
$stmt->close();

delete_image_file($listing['image']);

set_flash('Listing deleted.');
header('Location: dashboard.php');
exit;
