<?php
/**
 * Polling endpoint for the chatbox.
 * Returns new messages (JSON) in a conversation after a given message_id,
 * and marks incoming messages as read.
 * Called by js/main.js via setInterval every few seconds.
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$conversation_id = (int) ($_GET['c'] ?? 0);
$after = (int) ($_GET['after'] ?? 0);

// Participant check: users may only read their own conversations
$stmt = $conn->prepare('SELECT conversation_id FROM conversations WHERE conversation_id = ? AND (buyer_id = ? OR seller_id = ?)');
$stmt->bind_param('iii', $conversation_id, $user_id, $user_id);
$stmt->execute();
$allowed = (bool) $stmt->get_result()->fetch_row();
$stmt->close();

if (!$allowed) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

// Fetch messages newer than the client's last-seen id
$stmt = $conn->prepare(
    'SELECT message_id, sender_id, message_text, created_at
     FROM messages
     WHERE conversation_id = ? AND message_id > ?
     ORDER BY message_id ASC'
);
$stmt->bind_param('ii', $conversation_id, $after);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark the incoming ones as read
$stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id <> ? AND is_read = 0');
$stmt->bind_param('ii', $conversation_id, $user_id);
$stmt->execute();
$stmt->close();

$messages = [];
foreach ($rows as $row) {
    $messages[] = [
        'id'   => (int) $row['message_id'],
        'mine' => (int) $row['sender_id'] === $user_id,
        'text' => $row['message_text'], // inserted with textContent on the client (XSS-safe)
        'time' => date('j M, g:i a', strtotime($row['created_at'])),
    ];
}

echo json_encode(['messages' => $messages]);
