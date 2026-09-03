<?php
require_once __DIR__ . '/includes/db.php';

$errors = [];
$name = '';
$email = '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // ---- Server-side validation (authoritative) ----
    if ($name === '' || mb_strlen($name) > 100) {
        $errors['name'] = 'Please enter your name (max 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($subject === '' || mb_strlen($subject) > 200) {
        $errors['subject'] = 'Please enter a subject (max 200 characters).';
    }
    if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
        $errors['message'] = 'Message must be between 10 and 5000 characters.';
    }

    if (!$errors) {
        $stmt = $conn->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $email, $subject, $message);
        $stmt->execute();
        $stmt->close();

        set_flash('Thanks for reaching out! We received your message and will reply by email.');
        header('Location: contact.php');
        exit;
    }
}

$page_title = 'Contact Us';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="page-title">Contact Us</h1>
    <p class="muted">Questions, feedback or issues with a listing? Drop us a message.</p>

    <div class="contact-layout">
        <!-- Contact form -->
        <section class="card">
            <h2>Send a Message</h2>

            <form method="post" action="contact.php" id="contactForm" novalidate>
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" required maxlength="100" value="<?= e($name) ?>"
                           class="<?= isset($errors['name']) ? 'invalid' : '' ?>">
                    <?php if (isset($errors['name'])): ?>
                        <p class="field-error"><?= e($errors['name']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required maxlength="100" value="<?= e($email) ?>"
                           class="<?= isset($errors['email']) ? 'invalid' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                        <p class="field-error"><?= e($errors['email']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" required maxlength="200" value="<?= e($subject) ?>"
                           class="<?= isset($errors['subject']) ? 'invalid' : '' ?>">
                    <?php if (isset($errors['subject'])): ?>
                        <p class="field-error"><?= e($errors['subject']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="6" required minlength="10" maxlength="5000"
                              class="<?= isset($errors['message']) ? 'invalid' : '' ?>"><?= e($message) ?></textarea>
                    <?php if (isset($errors['message'])): ?>
                        <p class="field-error"><?= e($errors['message']) ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>
        </section>

        <!-- Contact details + map -->
        <aside>
            <section class="card contact-details">
                <h2>Get in Touch</h2>
                <ul>
                    <li><strong>Email:</strong> <a href="mailto:support@campustrade.test">support@campustrade.test</a></li>
                    <li><strong>Phone:</strong> +603-9086 0288</li>
                    <li><strong>Office Hours:</strong> Mon&ndash;Fri, 9:00 AM &ndash; 5:00 PM</li>
                    <li><strong>Address:</strong> Universiti Tunku Abdul Rahman, Sungai Long Campus, Jalan Sungai Long, 43000 Kajang, Selangor</li>
                </ul>
                <div class="social-links">
                    <a href="https://www.facebook.com" target="_blank" rel="noopener">Facebook</a>
                    <a href="https://www.instagram.com" target="_blank" rel="noopener">Instagram</a>
                    <a href="https://www.twitter.com" target="_blank" rel="noopener">Twitter</a>
                </div>
            </section>

            <section class="card map-card">
                <h2>Find Us on Campus</h2>
                <div class="map-wrap">
                    <iframe
                        src="https://www.google.com/maps?q=Universiti%20Tunku%20Abdul%20Rahman%20Sungai%20Long%20Campus&output=embed"
                        width="100%" height="300" style="border:0;" allowfullscreen loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="UTAR Sungai Long Campus map"></iframe>
                </div>
            </section>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
