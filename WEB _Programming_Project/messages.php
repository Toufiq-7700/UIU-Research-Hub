<?php
require_once 'database-functions.php';

// Check auth
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$csrfToken = ensureCsrfToken();
$conversations = getUserConversations($userId);
$activeConversationId = $_GET['conversation_id'] ?? null;
$messages = [];
$activeChatUser = null;

// Handle "Compose" / Start new chat
if (isset($_GET['compose']) && is_numeric($_GET['compose'])) {
    $targetUserId = $_GET['compose'];
    if ($targetUserId != $userId) {
        $convId = getOrCreateConversation($userId, $targetUserId);
        header("Location: messages.php?conversation_id=" . $convId);
        exit;
    }
}

// Handle sending message
// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['conversation_id'])) {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
        $convId = (int)($_POST['conversation_id'] ?? 0);
        if ($convId > 0) {
            header("Location: messages.php?conversation_id=" . $convId . "&message_send_error=csrf");
            exit;
        }
        header("Location: messages.php?message_send_error=csrf");
        exit;
    }

    $msgText = trim($_POST['message']);
    $convId = $_POST['conversation_id'];
    
    if (!empty($msgText)) {
        // Find the receiver_id for this conversation
        $receiverId = null;
        foreach ($conversations as $c) {
            if ($c['conversation_id'] == $convId) {
                $receiverId = $c['other_user_id'];
                break;
            }
        }
        
        if ($receiverId) {
            sendMessage($convId, $userId, $receiverId, $msgText);
            // Refresh to show new message
            header("Location: messages.php?conversation_id=" . $convId);
            exit;
        }
    }
}

// Load active conversation
if ($activeConversationId) {
    // Security check: ensure user is participant
    // Using a simple check via database or filtering the already fetched conversations
    $isValid = false;
    foreach ($conversations as $c) {
        if ($c['conversation_id'] == $activeConversationId) {
            $isValid = true;
            $activeChatUser = $c['other_user_name'];
            break;
        }
    }
    
    if ($isValid) {
        $messages = getConversationMessages($activeConversationId);
        // Mark as read logic could go here
    } else {
        $activeConversationId = null; // Invalid conversation
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body style="height: 100vh; display: flex; flex-direction: column; overflow: hidden; background-color: #f4f7f6;">

    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Chat Interface -->
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="sidebar-header">
                <h3>Messages</h3>
            </div>
            <div class="chat-list" id="chatList" style="overflow-y: auto;">
                <?php if (empty($conversations)): ?>
                    <p style="padding: 20px; color: #666; text-align: center;">No conversations yet.</p>
                <?php else: ?>
                    <?php foreach ($conversations as $conv): ?>
                        <div class="chat-item <?php echo ($activeConversationId == $conv['conversation_id']) ? 'active' : ''; ?>" 
                             onclick="window.location.href='messages.php?conversation_id=<?php echo $conv['conversation_id']; ?>'">
                            <div class="chat-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="chat-info">
                                <h4><?php echo htmlspecialchars($conv['other_user_name']); ?></h4>
                                <p><?php echo htmlspecialchars(substr($conv['last_message_text'] ?? 'Start chatting', 0, 30)); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($activeConversationId): ?>
                <?php if (isset($_GET['message_deleted'])): ?>
                    <div style="margin: 10px 20px 0; background-color: #d4edda; color: #155724; padding: 10px; border-radius: 8px;">
                        Message deleted.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['message_delete_error']) || isset($_GET['message_send_error'])): ?>
                    <div style="margin: 10px 20px 0; background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px;">
                        <?php
                        $err = (string)($_GET['message_delete_error'] ?? ($_GET['message_send_error'] ?? ''));
                        $msg = 'Action failed.';
                        if ($err === 'csrf') $msg = 'Security check failed. Please refresh and try again.';
                        elseif ($err === 'forbidden') $msg = 'Not allowed.';
                        elseif ($err === 'invalid') $msg = 'Invalid request.';
                        echo htmlspecialchars($msg);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="chat-header">
                    <div class="chat-header-info">
                        <i class="fas fa-user-circle fa-2x"></i>
                        <h3><?php echo htmlspecialchars($activeChatUser); ?></h3>
                    </div>
                </div>

                <div class="messages-box" id="messagesBox">
                    <?php if (empty($messages)): ?>
                        <div style="text-align: center; margin-top: 50px; color: #999;">
                            <p>No messages yet. Say hello!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach (array_reverse($messages) as $msg): ?>
                            <?php 
                                $isMe = ($msg['sender_id'] == $userId);
                                $wrapperClass = $isMe ? 'sent' : 'received';
                            ?>
                            <div class="message-wrapper <?php echo $wrapperClass; ?>">
                                <div class="message-bubble">
                                    <p><?php echo htmlspecialchars($msg['message_text']); ?></p>
                                </div>
                                <div class="message-meta">
                                    <span class="timestamp"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                                    <?php if ($isMe && (string)($msg['message_text'] ?? '') !== '[deleted]'): ?>
                                        <div class="message-actions">
                                            <form method="POST" action="message-delete.php" onsubmit="return confirm('Delete this message?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="conversation_id" value="<?php echo (int)$activeConversationId; ?>">
                                                <input type="hidden" name="message_id" value="<?php echo (int)$msg['message_id']; ?>">
                                                <button type="submit" class="btn-delete-msg" title="Delete message">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="chat-input-area">
                    <form method="POST" action="messages.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="conversation_id" value="<?php echo $activeConversationId; ?>">
                        <input type="text" name="message" id="messageInput" placeholder="Type a message..." autocomplete="off" required>
                        <button type="submit" class="btn" id="sendBtn"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #999;">
                    <i class="fas fa-comments fa-4x" style="margin-bottom: 20px; color: #ddd;"></i>
                    <h3>Select a chat to start messaging</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
