<?php
require_once 'database-functions.php';

$accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
$isJson = str_contains($accept, 'application/json') || ((string)($_POST['ajax'] ?? '') === '1');

function jsonResponse($statusCode, $payload) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if ($isJson) {
        jsonResponse(401, ['success' => false, 'message' => 'Not authenticated']);
    }
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJson) {
        jsonResponse(405, ['success' => false, 'message' => 'Method not allowed']);
    }
    header("Location: team-finder.php");
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    $teamId = (int)($_POST['team_id'] ?? 0);
    if ($teamId > 0) {
        if ($isJson) {
            jsonResponse(403, ['success' => false, 'message' => 'Invalid CSRF token', 'code' => 'csrf']);
        }
        header("Location: team-profile.php?team_id={$teamId}&manage_error=csrf");
        exit;
    }
    if ($isJson) {
        jsonResponse(403, ['success' => false, 'message' => 'Invalid CSRF token', 'code' => 'csrf']);
    }
    header("Location: dashboard.php?manage_error=csrf");
    exit;
}

$teamId = (int)($_POST['team_id'] ?? 0);
$requestId = (int)($_POST['request_id'] ?? 0);
$action = (string)($_POST['action'] ?? '');

if ($teamId <= 0 || $requestId <= 0) {
    if ($isJson) {
        jsonResponse(400, ['success' => false, 'message' => 'Invalid request', 'code' => 'invalid']);
    }
    header("Location: team-finder.php?manage_error=invalid");
    exit;
}

$actionNorm = '';
if ($action === 'accept') $actionNorm = 'Accept';
if ($action === 'reject') $actionNorm = 'Reject';
if ($actionNorm === '') {
    if ($isJson) {
        jsonResponse(400, ['success' => false, 'message' => 'Invalid action', 'code' => 'invalid']);
    }
    header("Location: team-profile.php?team_id={$teamId}&manage_error=invalid");
    exit;
}

$result = handleJoinRequest($requestId, (int)$_SESSION['user_id'], $actionNorm);
if ($result['success']) {
    if ($isJson) {
        jsonResponse(200, ['success' => true, 'message' => $result['message'] ?? 'Updated', 'action' => strtolower($actionNorm)]);
    }
    header("Location: team-profile.php?team_id={$teamId}&manage_success=" . strtolower($actionNorm));
    exit;
}

$code = (string)($result['code'] ?? '');
if ($code === '') {
    $code = 'failed';
}
if ($isJson) {
    jsonResponse(400, ['success' => false, 'message' => $result['message'] ?? 'Failed', 'code' => $code]);
}
header("Location: team-profile.php?team_id={$teamId}&manage_error=" . urlencode($code));
exit;
?>
