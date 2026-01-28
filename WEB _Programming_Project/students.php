<?php
require_once 'database-functions.php';

$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    header("Location: login.php");
    exit;
}

$search = (string)($_GET['search'] ?? '');
$students = getStudents($search);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        #pageLoader {
            position: fixed;
            inset: 0;
            background: rgba(244, 247, 246, 0.92);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .spinner {
            width: 46px;
            height: 46px;
            border: 5px solid #dbe7f5;
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.9s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="pageLoader" aria-live="polite" aria-busy="true">
        <div style="text-align:center;">
            <div class="spinner" role="status" aria-label="Loading"></div>
            <div style="margin-top: 12px; color:#666; font-weight: 600;">Loading students...</div>
        </div>
    </div>

    <?php include 'header.php'; ?>

    <section class="section" style="background-color: #fff; padding: 40px 0;">
        <div class="container" style="text-align:center;">
            <h1>Students</h1>
            <p>Browse students and connect for collaboration.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div style="margin-bottom: 20px;">
                <form method="GET" action="students.php" style="display:flex; gap: 10px; flex-wrap: wrap; align-items: center; max-width: 700px;">
                    <input class="form-control" type="text" name="search" placeholder="Search by name, email, department..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1; min-width: 220px;">
                    <button class="btn" type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if (trim($search) !== ''): ?>
                        <a class="btn btn-outline" href="students.php" style="text-decoration:none;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (empty($students)): ?>
                <div style="text-align: center; padding: 40px; background: #fff; border-radius: 8px;">
                    <i class="fas fa-user-graduate" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No students found</h3>
                    <p>Try a different search.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach ($students as $s): ?>
                        <div class="card" style="text-align: center;">
                            <div style="width: 100px; height: 100px; background-color: #e0e0e0; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #666; overflow: hidden;">
                                <?php if (!empty($s['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($s['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-graduate"></i>
                                <?php endif; ?>
                            </div>
                            <h3>
                                <a href="user-profile.php?user_id=<?php echo (int)$s['user_id']; ?>" style="text-decoration:none; color: inherit;">
                                    <?php echo htmlspecialchars((string)$s['full_name']); ?>
                                </a>
                            </h3>
                            <p style="color: var(--primary-color); font-weight: 500;"><?php echo htmlspecialchars((string)($s['department'] ?? 'Student')); ?></p>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">
                                <?php
                                $bio = trim((string)($s['bio'] ?? ''));
                                echo htmlspecialchars($bio !== '' ? $bio : 'Research Interests');
                                ?>
                            </p>
                            <div style="display:flex; gap: 10px; justify-content:center; flex-wrap: wrap; margin-top: 15px;">
                                <a class="btn btn-outline" href="user-profile.php?user_id=<?php echo (int)$s['user_id']; ?>" style="text-decoration:none;">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <?php if ((int)$_SESSION['user_id'] !== (int)$s['user_id']): ?>
                                    <button class="btn" onclick="window.location.href='messages.php?compose=<?php echo (int)$s['user_id']; ?>'">
                                        <i class="fas fa-comment"></i> Message
                                    </button>
                                <?php else: ?>
                                    <span class="btn btn-outline" style="opacity: 0.6; cursor: default;">This is you</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            const loader = document.getElementById('pageLoader');
            if (loader) loader.style.display = 'none';
        });
    </script>
</body>
</html>
