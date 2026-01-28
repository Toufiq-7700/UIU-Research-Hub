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
        header("Location: team-profile.php?team_id={$teamId}&leader_change_error=csrf");
        exit;
    }
    header("Location: dashboard.php?leader_change_error=csrf");
    exit;
}

$teamId = (int)($_POST['team_id'] ?? 0);
$newLeaderUserId = (int)($_POST['new_leader_user_id'] ?? 0);

if ($teamId <= 0 || $newLeaderUserId <= 0) {
    header("Location: team-finder.php?leader_change_error=invalid");
    exit;
}

$result = transferTeamLeadership($teamId, (int)$_SESSION['user_id'], $newLeaderUserId);
if ($result['success']) {
    header("Location: team-profile.php?team_id={$teamId}&leader_changed=1");
    exit;
}

$code = (string)($result['code'] ?? 'failed');
header("Location: team-profile.php?team_id={$teamId}&leader_change_error=" . urlencode($code));
exit;
?>

