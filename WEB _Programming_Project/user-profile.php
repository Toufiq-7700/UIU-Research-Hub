<?php
require_once 'database-functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    header("Location: students.php");
    exit;
}

$u = getUserWithRole($userId);
if (!$u) {
    header("Location: students.php?user_not_found=1");
    exit;
}

// This page is intended for student browsing; if the target isn't a Student, keep it private by redirecting.
$roleName = (string)($u['role_name'] ?? '');
if (strcasecmp($roleName, 'Student') !== 0) {
    header("Location: students.php?user_not_found=1");
    exit;
}

$viewerId = (int)$_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string)($u['full_name'] ?? 'Student')); ?> - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="section" style="padding: 40px 0;">
        <div class="container">
            <a href="students.php" class="btn btn-outline" style="text-decoration:none; margin-bottom: 20px; display:inline-block;">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>

            <div class="card" style="max-width: 850px; margin: 0 auto;">
                <div style="display:flex; gap: 20px; flex-wrap: wrap; align-items: center;">
                    <div style="width: 120px; height: 120px; background-color: var(--primary-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; color: var(--primary-color); overflow: hidden;">
                        <?php if (!empty($u['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars((string)$u['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-graduate"></i>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1; min-width: 240px;">
                        <h2 style="margin: 0 0 8px 0;"><?php echo htmlspecialchars((string)$u['full_name']); ?></h2>
                        <div style="display:flex; gap: 10px; flex-wrap: wrap; align-items:center;">
                            <span style="display: inline-block; padding: 4px 12px; background-color: var(--primary-light); color: var(--primary-color); border-radius: 999px; font-size: 0.85rem; font-weight: 600;">
                                <?php echo htmlspecialchars($roleName); ?>
                            </span>
                            <?php if (!empty($u['department'])): ?>
                                <span style="color:#666; font-weight: 600;"><i class="fas fa-building"></i> <?php echo htmlspecialchars((string)$u['department']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($u['email'])): ?>
                            <div style="margin-top: 10px; color:#666;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars((string)$u['email']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div style="display:flex; gap: 10px; flex-wrap: wrap;">
                        <?php if ($viewerId !== (int)$u['user_id']): ?>
                            <a class="btn" href="messages.php?compose=<?php echo (int)$u['user_id']; ?>" style="text-decoration:none;">
                                <i class="fas fa-comment"></i> Message
                            </a>
                        <?php else: ?>
                            <span class="btn btn-outline" style="opacity: 0.7; cursor: default;">This is you</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top: 25px;">
                    <h3 style="margin: 0 0 10px 0; color: var(--primary-color);">Bio</h3>
                    <?php
                    $bio = trim((string)($u['bio'] ?? ''));
                    ?>
                    <p style="margin: 0; color:#555; line-height: 1.7;">
                        <?php echo htmlspecialchars($bio !== '' ? $bio : 'No bio provided.'); ?>
                    </p>
                </div>

                <div style="margin-top: 25px;">
                    <h3 style="margin: 0 0 10px 0; color: var(--primary-color);">Skills</h3>
                    <?php
                    $skills = trim((string)($u['skills'] ?? ''));
                    ?>
                    <p style="margin: 0; color:#555; line-height: 1.7;">
                        <?php echo htmlspecialchars($skills !== '' ? $skills : 'No skills listed.'); ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>

