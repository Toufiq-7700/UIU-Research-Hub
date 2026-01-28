<?php
require_once 'database-functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: categories.php");
    exit;
}

$csrf = (string) ($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
    header("Location: categories.php?category_error=csrf");
    exit;
}

$name = (string) ($_POST['category_name'] ?? '');
$description = (string) ($_POST['description'] ?? '');

$result = createCategory((int) $_SESSION['user_id'], $name, $description);
if ($result['success']) {
    header("Location: categories.php?category_added=1");
    exit;
}

$code = (string) ($result['code'] ?? 'failed');
header("Location: categories.php?category_error=" . urlencode($code));
exit;
?>