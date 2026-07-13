<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$user_id = $_SESSION['user_id'];
$errors = [];

// Load current user record
$stmt = $conn->prepare('SELECT username, email, phone, profile_image, created_at FROM users WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---- Update profile details ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $username = trim($_POST['username'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');

    if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3-50 characters using letters, numbers or underscores only.';
    }
    if ($phone !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $phone)) {
        $errors[] = 'Phone number format is invalid.';
    }

    // Username taken by someone else?
    if (!$errors) {
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ? AND user_id <> ?');
        $stmt->bind_param('si', $username, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->fetch_row()) {
            $errors[] = 'That username is already taken.';
        }
        $stmt->close();
    }

    // Optional profile image upload
    $new_image = null;
    if (!$errors) {
        try {
            $new_image = handle_image_upload($_FILES['profile_image'] ?? []);
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $phone_value = $phone === '' ? null : $phone;
        if ($new_image !== null) {
            if ($user['profile_image'] !== 'default.png') {
                delete_image_file($user['profile_image']);
            }
            $stmt = $conn->prepare('UPDATE users SET username = ?, phone = ?, profile_image = ? WHERE user_id = ?');
            $stmt->bind_param('sssi', $username, $phone_value, $new_image, $user_id);
        } else {
            $stmt = $conn->prepare('UPDATE users SET username = ?, phone = ? WHERE user_id = ?');
            $stmt->bind_param('ssi', $username, $phone_value, $user_id);
        }
        $stmt->execute();
        $stmt->close();

        $_SESSION['username'] = $username;
        set_flash('Profile updated successfully.');
        header('Location: profile.php');
        exit;
    }
}

// ---- Change password ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($current, $row['password'])) {
        $errors[] = 'Your current password is incorrect.';
    }
    if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
        $errors[] = 'New password must be at least 8 characters and contain both letters and numbers.';
    }
    if ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    }

    if (!$errors) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
        $stmt->bind_param('si', $hash, $user_id);
        $stmt->execute();
        $stmt->close();

        set_flash('Password changed successfully.');
        header('Location: profile.php');
        exit;
    }
}

$page_title = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="page-title">My Profile</h1>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="profile-grid">
        <section class="card">
            <h2>Profile Details</h2>
            <p class="muted">Member since <?= format_date($user['created_at']) ?> &middot; <?= e($user['email']) ?></p>

            <form method="post" action="profile.php" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required minlength="3" maxlength="50"
                           pattern="[A-Za-z0-9_]+" value="<?= e($user['username']) ?>">
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" maxlength="20"
                           value="<?= e($user['phone']) ?>" placeholder="e.g. 012-3456789">
                    <small>Shown to logged-in buyers when they click "Contact Seller".</small>
                </div>

                <div class="form-group">
                    <label for="profile_image">Profile Picture (optional, max 2 MB)</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </section>

        <section class="card">
            <h2>Change Password</h2>

            <form method="post" action="profile.php" id="passwordForm" novalidate>
                <input type="hidden" name="action" value="change_password">

                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                    <small>At least 8 characters with letters and numbers.</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </section>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
