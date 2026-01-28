<?php
require_once 'database-functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: messages.php");
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    $convId = (int)($_POST['conversation_id'] ?? 0);
    if ($convId > 0) {
        header("Location: messages.php?conversation_id={$convId}&message_delete_error=csrf");
        exit;
    }
    header("Location: messages.php?message_delete_error=csrf");
    exit;
}

$messageId = (int)($_POST['message_id'] ?? 0);
$convId = (int)($_POST['conversation_id'] ?? 0);

if ($messageId <= 0 || $convId <= 0) {
    header("Location: messages.php?message_delete_error=invalid");
    exit;
}

// Basic participant check: user must be part of the conversation
$userId = (int)$_SESSION['user_id'];
$isParticipant = $db->fetchValue(
    "SELECT COUNT(*) FROM conversations WHERE conversation_id = ? AND (participant1_id = ? OR participant2_id = ?)",
    [$convId, $userId, $userId],
    'iii'
);
if ((int)$isParticipant <= 0) {
    header("Location: messages.php?message_delete_error=forbidden");
    exit;
}

if (!deleteMessage($messageId, $userId)) {
    header("Location: messages.php?conversation_id={$convId}&message_delete_error=failed");
    exit;
}

header("Location: messages.php?conversation_id={$convId}&message_deleted=1");
exit;
?>

