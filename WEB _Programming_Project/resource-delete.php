<?php
require_once 'database-functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: resources.php");
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    header("Location: resources.php?resource_delete_error=csrf");
    exit;
}

$resourceId = (int)($_POST['resource_id'] ?? 0);
$redirect = (string)($_POST['redirect'] ?? '');

$redirectUrl = 'resources.php';
if ($redirect === 'dashboard') {
    $redirectUrl = 'dashboard.php';
}

if ($resourceId <= 0) {
    header("Location: " . $redirectUrl . "?resource_delete_error=invalid");
    exit;
}

$resource = getResourceById($resourceId);
if (!$resource) {
    header("Location: " . $redirectUrl . "?resource_delete_error=not_found");
    exit;
}

if ((int)$resource['uploaded_by'] !== (int)$_SESSION['user_id']) {
    header("Location: " . $redirectUrl . "?resource_delete_error=forbidden");
    exit;
}

$filePath = (string)($resource['file_path'] ?? '');

if (!deleteResource($resourceId, $_SESSION['user_id'])) {
    header("Location: " . $redirectUrl . "?resource_delete_error=failed");
    exit;
}

// Best-effort file cleanup (only if inside uploads/)
if ($filePath !== '') {
    $uploadsBase = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads');
    $fileReal = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath), DIRECTORY_SEPARATOR));

    if ($uploadsBase && $fileReal && str_starts_with($fileReal, $uploadsBase) && is_file($fileReal)) {
        @unlink($fileReal);
    }
}

header("Location: " . $redirectUrl . "?resource_deleted=1");
exit;
?>
