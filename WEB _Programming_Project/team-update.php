<?php
require_once 'database-functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: team-finder.php");
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    $teamId = (int)($_POST['team_id'] ?? 0);
    if ($teamId > 0) {
        header("Location: team-profile.php?team_id={$teamId}&team_update_error=csrf");
        exit;
    }
    header("Location: dashboard.php?team_update_error=csrf");
    exit;
}

$teamId = (int)($_POST['team_id'] ?? 0);
$teamName = (string)($_POST['team_name'] ?? '');
$description = (string)($_POST['description'] ?? '');
$categoryId = (int)($_POST['category_id'] ?? 0);
$maxMembers = (int)($_POST['max_members'] ?? 5);
$status = (string)($_POST['status'] ?? 'Recruiting');

if ($teamId <= 0) {
    header("Location: team-finder.php?team_update_error=invalid");
    exit;
}

$result = updateTeam($teamId, (int)$_SESSION['user_id'], $teamName, $description, $categoryId, $maxMembers, $status);
if ($result['success']) {
    header("Location: team-profile.php?team_id={$teamId}&team_updated=1");
    exit;
}

$code = (string)($result['code'] ?? 'failed');
header("Location: team-profile.php?team_id={$teamId}&team_update_error=" . urlencode($code));
exit;
?>

