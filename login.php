<?php
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email = '';

// Confirmation banner after a secure password change (exact value only —
// arbitrary URL input is never echoed).
$password_changed = ($_GET['msg'] ?? '') === 'password_changed';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare('SELECT user_id, username, password, role FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Prevent session fixation
            session_regenerate_id(true);
            $_SESSION['user_id']       = (int) $user['user_id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['last_activity'] = time();

            set_flash('Welcome back, ' . $user['username'] . '!');
            header('Location: dashboard.php');
            exit;
        }
        // Same message for wrong email or wrong password (no account enumeration)
        $errors[] = 'Invalid email or password.';
    }
}

$page_title = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-card">
        <h1>Welcome Back</h1>
        <p class="auth-subtitle">Log in to manage your listings and wishlist.</p>

        <?php if ($password_changed): ?>
            <div class="alert alert-success">
                Your password was changed successfully. Please log in with your new password.
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" id="loginForm" novalidate>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="toggle-password" aria-label="Show password"></button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <p class="auth-switch">New to CampusTrade? <a href="register.php">Create an account</a>.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
