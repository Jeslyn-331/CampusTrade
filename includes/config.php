<?php
/**
 * CampusTrade — global configuration.
 * Included on every page via includes/db.php.
 */

// ---- Database credentials (XAMPP defaults) ----
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'campustrade');

// ---- Site settings ----
define('SITE_NAME', 'CampusTrade');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_BYTES', 2 * 1024 * 1024);          // 2 MB — item photos
define('PROFILE_MAX_UPLOAD_BYTES', 10 * 1024 * 1024); // 10 MB — profile pictures / QR codes
define('ITEMS_PER_PAGE', 12);

// Fixed category list (stored as VARCHAR in listings.category)
const CATEGORIES = ['Textbooks', 'Electronics', 'Furniture', 'Stationery', 'Clothing', 'Others'];
const CONDITIONS = ['New', 'Like New', 'Good', 'Fair'];

// Malaysian banks offered in the simulated FPX payment flow
const FPX_BANKS = ['Maybank', 'CIMB Bank', 'Public Bank', 'RHB Bank', 'Hong Leong Bank', 'Bank Islam', 'AmBank', 'Bank Rakyat'];

// ---- Session bootstrap (secure cookie settings) ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // expires when browser closes
        'httponly' => true,       // not readable by JavaScript
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---- Session timeout: log out after 30 minutes of inactivity ----
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash'] = 'Your session expired. Please log in again.';
    } else {
        $_SESSION['last_activity'] = time();
    }
}
