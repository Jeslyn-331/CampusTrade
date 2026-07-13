<?php
require_once __DIR__ . '/includes/db.php';
require_login();

$user_id = $_SESSION['user_id'];

// ---- Entry point from an item page: find or create the conversation ----
if (isset($_GET['listing_id'])) {
    $listing_id = (int) $_GET['listing_id'];
    $stmt = $conn->prepare('SELECT user_id FROM listings WHERE listing_id = ?');
    $stmt->bind_param('i', $listing_id);
    $stmt->execute();
    $listing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$listing) {
        set_flash('That listing no longer exists.', 'error');
        header('Location: browse.php');
        exit;
    }
    if ((int) $listing['user_id'] === $user_id) {
        set_flash('You cannot chat with yourself about your own listing.', 'error');
        header('Location: item.php?id=' . $listing_id);
        exit;
    }

    $conversation_id = get_or_create_conversation($conn, $listing_id, $user_id, (int) $listing['user_id']);
    header('Location: chat.php?c=' . $conversation_id);
    exit;
}

// ---- Send a message (PRG pattern) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    $conversation_id = (int) ($_POST['conversation_id'] ?? 0);
    $text = trim($_POST['message_text'] ?? '');

    // Participant check
    $stmt = $conn->prepare('SELECT conversation_id FROM conversations WHERE conversation_id = ? AND (buyer_id = ? OR seller_id = ?)');
    $stmt->bind_param('iii', $conversation_id, $user_id, $user_id);
    $stmt->execute();
    $allowed = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();

    if ($allowed && $text !== '' && mb_strlen($text) <= 2000) {
        $stmt = $conn->prepare('INSERT INTO messages (conversation_id, sender_id, message_text) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $conversation_id, $user_id, $text);
        $stmt->execute();
        $stmt->close();

        // Touch updated_at so the conversation list sorts by recent activity
        $stmt = $conn->prepare('UPDATE conversations SET updated_at = NOW() WHERE conversation_id = ?');
        $stmt->bind_param('i', $conversation_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: chat.php?c=' . $conversation_id);
    exit;
}

// ---- Load my conversation list ----
$stmt = $conn->prepare(
    'SELECT c.conversation_id, c.listing_id, c.buyer_id, c.seller_id,
            l.title, l.image, l.price, l.status,
            u.username AS other_name, u.profile_image AS other_image,
            (SELECT m.message_text FROM messages m WHERE m.conversation_id = c.conversation_id
             ORDER BY m.message_id DESC LIMIT 1) AS last_message,
            (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.conversation_id
             ORDER BY m.message_id DESC LIMIT 1) AS last_time,
            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.conversation_id
             AND m.sender_id <> ? AND m.is_read = 0) AS unread
     FROM conversations c
     JOIN listings l ON c.listing_id = l.listing_id
     JOIN users u ON u.user_id = IF(c.buyer_id = ?, c.seller_id, c.buyer_id)
     WHERE c.buyer_id = ? OR c.seller_id = ?
     ORDER BY COALESCE(c.updated_at, c.created_at) DESC'
);
$stmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ---- Active conversation (default: most recent) ----
$active_id = (int) ($_GET['c'] ?? 0);
$active = null;
foreach ($conversations as $c) {
    if ((int) $c['conversation_id'] === $active_id) {
        $active = $c;
        break;
    }
}
if (!$active && $conversations) {
    $active = $conversations[0];
    $active_id = (int) $active['conversation_id'];
}

$messages = [];
if ($active) {
    // Mark incoming messages as read
    $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id <> ? AND is_read = 0');
    $stmt->bind_param('ii', $active_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Load messages chronologically
    $stmt = $conn->prepare(
        'SELECT m.message_id, m.sender_id, m.message_text, m.created_at, u.username, u.profile_image
         FROM messages m
         JOIN users u ON m.sender_id = u.user_id
         WHERE m.conversation_id = ?
         ORDER BY m.created_at ASC'
    );
    $stmt->bind_param('i', $active_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$last_message_id = $messages ? (int) end($messages)['message_id'] : 0;

$page_title = 'My Chats';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <h1 class="page-title">My Chats</h1>

    <?php if (!$conversations): ?>
        <div class="empty-state">
            <p><strong>No conversations yet.</strong></p>
            <p>Find an item you like and click "Chat with Seller" to start talking.</p>
            <a href="browse.php" class="btn btn-primary">Browse Listings</a>
        </div>
    <?php else: ?>
        <div class="chat-layout">
            <!-- Conversation list -->
            <aside class="chat-list">
                <?php foreach ($conversations as $c): ?>
                    <a href="chat.php?c=<?= (int) $c['conversation_id'] ?>"
                       class="chat-list-item <?= (int) $c['conversation_id'] === $active_id ? 'active' : '' ?>">
                        <img src="<?= e(user_avatar($c['other_image'])) ?>" alt="" class="avatar">
                        <span class="chat-list-body">
                            <span class="chat-list-top">
                                <strong><?= e($c['other_name']) ?></strong>
                                <?php if ((int) $c['unread'] > 0): ?>
                                    <span class="wishlist-badge"><?= (int) $c['unread'] ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="chat-list-item-title"><?= e($c['title']) ?></span>
                            <span class="chat-list-preview">
                                <?= $c['last_message'] !== null ? e(mb_strimwidth($c['last_message'], 0, 40, '…')) : 'No messages yet' ?>
                            </span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </aside>

            <!-- Active chat -->
            <?php if ($active): ?>
                <section class="chat-window">
                    <div class="chat-header">
                        <a href="item.php?id=<?= (int) $active['listing_id'] ?>" class="chat-header-item">
                            <img src="<?= e(listing_image($active['image'])) ?>" alt="" class="chat-header-thumb">
                            <span>
                                <strong><?= e($active['title']) ?></strong><br>
                                <span class="muted"><?= format_price((float) $active['price']) ?> &middot;
                                    <?= e($active['status']) ?> &middot; with <?= e($active['other_name']) ?></span>
                            </span>
                        </a>
                        <?php if ($active['status'] === 'Available' && (int) $active['buyer_id'] === $user_id): ?>
                            <a href="payment.php?listing_id=<?= (int) $active['listing_id'] ?>" class="btn btn-accent btn-small">Buy Now</a>
                        <?php endif; ?>
                    </div>

                    <div class="chat-thread" id="chatThread"
                         data-conversation="<?= $active_id ?>"
                         data-last="<?= $last_message_id ?>"
                         data-me="<?= $user_id ?>">
                        <?php if (!$messages): ?>
                            <p class="muted chat-empty">Say hello — ask about the item, negotiate the price, or arrange a meetup.</p>
                        <?php endif; ?>
                        <?php foreach ($messages as $m): ?>
                            <div class="chat-bubble-row <?= (int) $m['sender_id'] === $user_id ? 'mine' : 'theirs' ?>">
                                <div class="chat-bubble">
                                    <p><?= nl2br(e($m['message_text'])) ?></p>
                                    <span class="chat-time"><?= date('j M, g:i a', strtotime($m['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form method="post" action="chat.php" class="chat-send" autocomplete="off">
                        <input type="hidden" name="action" value="send">
                        <input type="hidden" name="conversation_id" value="<?= $active_id ?>">
                        <input type="text" name="message_text" maxlength="2000" required
                               placeholder="Type a message..." aria-label="Message">
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
