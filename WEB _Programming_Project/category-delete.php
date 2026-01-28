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

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    header("Location: categories.php?category_manage_error=csrf");
    exit;
}

$categoryId = (int)($_POST['category_id'] ?? 0);
if ($categoryId <= 0) {
    header("Location: categories.php?category_manage_error=invalid");
    exit;
}

$result = deleteCategory((int)$_SESSION['user_id'], $categoryId);
if ($result['success']) {
    header("Location: categories.php?category_deleted=1");
    exit;
}

$code = (string)($result['code'] ?? 'failed');
header("Location: categories.php?category_manage_error=" . urlencode($code));
exit;
?>

