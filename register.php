<?php
require_once __DIR__ . '/includes/db.php';

// Already logged in? Go to dashboard.
if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$username = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ---- Server-side validation (the authoritative check) ----
    if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3-50 characters using letters, numbers or underscores only.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $errors[] = 'Phone number format is invalid.';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must be at least 8 characters and contain both letters and numbers.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Duplicate username/email check
    if (!$errors) {
        $stmt = $conn->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (strcasecmp($row['username'], $username) === 0) {
                $errors[] = 'That username is already taken.';
            }
            if (strcasecmp($row['email'], $email) === 0) {
                $errors[] = 'An account with that email already exists.';
            }
        }
        $stmt->close();
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $phone_value = $phone === '' ? null : $phone;
        $stmt = $conn->prepare('INSERT INTO users (username, email, password, phone) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $username, $email, $hash, $phone_value);
        $stmt->execute();
        $stmt->close();

        set_flash('Account created successfully! Please log in.');
        header('Location: login.php');
        exit;
    }
}

$page_title = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-card">
        <h1>Create Your Account</h1>
        <p class="auth-subtitle">Join your campus marketplace — it only takes a minute.</p>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php" id="registerForm" novalidate>
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" value="<?= e($username) ?>"
                       required minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+">
                <small>3-50 characters. Letters, numbers and underscores only.</small>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="phone">Phone (optional)</label>
                <input type="tel" id="phone" name="phone" value="<?= e($phone) ?>" maxlength="20"
                       placeholder="e.g. 012-3456789">
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <div class="password-wrap">
                    <input type="password" id="password" name="password" required minlength="8">
                    <button type="button" class="toggle-password" aria-label="Show password"></button>
                </div>
                <small>At least 8 characters with letters and numbers.</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <div class="password-wrap">
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    <button type="button" class="toggle-password" aria-label="Show password"></button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Log in here</a>.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
