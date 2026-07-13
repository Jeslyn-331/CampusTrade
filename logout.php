<?php
require_once __DIR__ . '/includes/config.php';

// Destroy the session and its cookie
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

// Start a fresh session just to carry the flash message
session_start();
session_regenerate_id(true);
$_SESSION['flash'] = 'You have been logged out.';
$_SESSION['flash_type'] = 'success';

header('Location: index.php');
exit;
