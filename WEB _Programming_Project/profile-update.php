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
    header("Location: dashboard.php");
    exit;
}

$csrf = (string)($_POST['csrf_token'] ?? '');
if ($csrf === '' || !isset($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
    if ($isJson) {
        jsonResponse(403, ['success' => false, 'message' => 'Invalid CSRF token']);
    }
    header("Location: dashboard.php?profile_error=csrf");
    exit;
}

$redirect = trim((string)($_POST['redirect'] ?? 'dashboard.php'));
if ($redirect === '') {
    $redirect = 'dashboard.php';
}

$skills = (string)($_POST['skills'] ?? '');
$bio = (string)($_POST['bio'] ?? '');

$profilePicPath = null;

// Optional profile picture upload
if (isset($_FILES['profile_picture']) && ($_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $maxBytes = 5 * 1024 * 1024; // 5MB
    $sizeBytes = (int)($_FILES['profile_picture']['size'] ?? 0);
    if ($sizeBytes > 0 && $sizeBytes <= $maxBytes) {
        $tmpName = (string)($_FILES['profile_picture']['tmp_name'] ?? '');
        if ($tmpName !== '' && is_uploaded_file($tmpName)) {
            $imgInfo = @getimagesize($tmpName);
            if ($imgInfo !== false) {
                $uploadDir = 'uploads/profiles/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $originalName = (string)($_FILES['profile_picture']['name'] ?? '');
                $fileName = basename($originalName);
                $fileName = preg_replace('/[^a-zA-Z0-9-_\\.]/', '_', $fileName);
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if ($ext === '' || !in_array($ext, $allowed, true)) {
                    if ($isJson) {
                        jsonResponse(400, ['success' => false, 'message' => 'Invalid image type']);
                    }
                    header("Location: " . $redirect . "?profile_error=picture_type");
                    exit;
                }

                try {
                    $randomPart = bin2hex(random_bytes(8));
                } catch (Throwable $e) {
                    $randomPart = uniqid('', true);
                }

                $targetFile = $uploadDir . time() . '_' . $randomPart . '_' . $fileName;
                if (move_uploaded_file($tmpName, $targetFile)) {
                    $profilePicPath = $targetFile;
                } else {
                    if ($isJson) {
                        jsonResponse(500, ['success' => false, 'message' => 'Failed to upload image']);
                    }
                    header("Location: " . $redirect . "?profile_error=picture_upload");
                    exit;
                }
            } else {
                if ($isJson) {
                    jsonResponse(400, ['success' => false, 'message' => 'Invalid image']);
                }
                header("Location: " . $redirect . "?profile_error=picture_invalid");
                exit;
            }
        }
    } else {
        if ($isJson) {
            jsonResponse(400, ['success' => false, 'message' => 'Image too large']);
        }
        header("Location: " . $redirect . "?profile_error=picture_size");
        exit;
    }
}

// Validate text fields (allow empty to clear)
if (mb_strlen($skills) > 3000) {
    if ($isJson) {
        jsonResponse(400, ['success' => false, 'message' => 'Skills too long']);
    }
    header("Location: " . $redirect . "?profile_error=skills_length");
    exit;
}
if (mb_strlen($bio) > 5000) {
    if ($isJson) {
        jsonResponse(400, ['success' => false, 'message' => 'Bio too long']);
    }
    header("Location: " . $redirect . "?profile_error=bio_length");
    exit;
}

if (!dbColumnExists('users', 'skills')) {
    if ($isJson) {
        jsonResponse(500, ['success' => false, 'message' => 'Database missing required column: users.skills (run dummy/migrate_db_v3.php)']);
    }
    header("Location: " . $redirect . "?profile_error=missing_column");
    exit;
}

$ok = updateUserProfile((int)$_SESSION['user_id'], $skills, $bio, $profilePicPath);
if (!$ok) {
    if ($profilePicPath) {
        @unlink($profilePicPath);
    }
    if ($isJson) {
        jsonResponse(500, ['success' => false, 'message' => 'Database update failed']);
    }
    header("Location: " . $redirect . "?profile_error=db");
    exit;
}

if ($isJson) {
    $user = getUserWithRole((int)$_SESSION['user_id']);
    jsonResponse(200, [
        'success' => true,
        'message' => 'Profile updated',
        'data' => [
            'skills' => $user['skills'] ?? '',
            'bio' => $user['bio'] ?? '',
            'profile_picture' => $user['profile_picture'] ?? ''
        ]
    ]);
}

header("Location: " . $redirect . "?success=1");
exit;
?>
