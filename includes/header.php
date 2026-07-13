<?php
/**
 * CampusTrade — shared page header and navigation.
 * Pages set $page_title before including this file.
 * Requires includes/db.php to have been included first ($conn available).
 */

$wishlist_count = 0;
if (is_logged_in()) {
    $stmt = $conn->prepare('SELECT COUNT(*) FROM wishlist WHERE user_id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $wishlist_count = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="navbar">
    <div class="navbar-inner">
        <a href="index.php" class="logo" aria-label="CampusTrade home">
            <span class="logo-mark">CT</span>
            <span class="logo-text">Campus<span class="logo-accent">Trade</span></span>
        </a>

        <button class="hamburger" id="hamburger" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="nav-links" id="navLinks">
            <a href="index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Home</a>

            <div class="dropdown">
                <a href="browse.php" class="dropdown-toggle <?= $current_page === 'browse.php' ? 'active' : '' ?>">
                    Browse <span class="caret">&#9662;</span>
                </a>
                <div class="dropdown-menu">
                    <a href="browse.php">All Items</a>
                    <?php foreach (CATEGORIES as $cat): ?>
                        <a href="browse.php?category=<?= urlencode($cat) ?>"><?= e($cat) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="contact.php" class="<?= $current_page === 'contact.php' ? 'active' : '' ?>">Contact</a>

            <form action="browse.php" method="get" class="nav-search" role="search">
                <input type="text" name="search" placeholder="Search items..."
                       value="<?= e($_GET['search'] ?? '') ?>" aria-label="Search items">
                <button type="submit" aria-label="Search">&#128269;</button>
            </form>

            <?php if (is_logged_in()): ?>
                <a href="wishlist.php" class="wishlist-link <?= $current_page === 'wishlist.php' ? 'active' : '' ?>">
                    &#9825; Wishlist
                    <?php if ($wishlist_count > 0): ?>
                        <span class="wishlist-badge"><?= $wishlist_count ?></span>
                    <?php endif; ?>
                </a>
                <div class="dropdown">
                    <a href="dashboard.php" class="dropdown-toggle user-toggle">
                        &#128100; <?= e($_SESSION['username']) ?> <span class="caret">&#9662;</span>
                    </a>
                    <div class="dropdown-menu">
                        <a href="dashboard.php">My Dashboard</a>
                        <a href="create_listing.php">Sell an Item</a>
                        <a href="profile.php">My Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="<?= $current_page === 'login.php' ? 'active' : '' ?>">Login</a>
                <a href="register.php" class="btn btn-primary btn-nav">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="page">
<?php if ($flash = get_flash()): ?>
    <div class="container">
        <div class="alert alert-<?= e($flash[1]) ?>"><?= e($flash[0]) ?></div>
    </div>
<?php endif; ?>
