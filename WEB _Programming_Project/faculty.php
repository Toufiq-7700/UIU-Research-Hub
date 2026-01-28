<?php
require_once 'database-functions.php';
$facultyMembers = getFaculty();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>

    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Page Header -->
    <section class="section" style="background-color: #fff; padding: 40px 0;">
        <div class="container">
            <div style="text-align: center;">
                <h1>Our Faculty</h1>
                <p>Connect with professors and supervisors for your research.</p>
            </div>
        </div>
    </section>

    <!-- Faculty Grid -->
    <section class="section">
        <div class="container">
            <?php if (empty($facultyMembers)): ?>
                <div style="text-align: center; padding: 40px; background: #fff; border-radius: 8px;">
                    <i class="fas fa-chalkboard-teacher" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No faculty members found</h3>
                    <p>Check back later for updates.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach ($facultyMembers as $faculty): ?>
                        <div class="card" style="text-align: center;">
                            <div
                                style="width: 100px; height: 100px; background-color: #e0e0e0; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #666; overflow: hidden;">
                                <?php if (!empty($faculty['profile_picture'])): ?>
                                    <img src="<?php echo htmlspecialchars($faculty['profile_picture']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-user-tie"></i>
                                <?php endif; ?>
                            </div>
                            <h3><?php echo htmlspecialchars($faculty['full_name']); ?></h3>
                            <p style="color: var(--primary-color); font-weight: 500;"><?php echo htmlspecialchars($faculty['department'] ?? 'Faculty'); ?></p>
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">
                                <?php echo htmlspecialchars($faculty['bio'] ?? 'Research Interests'); ?>
                            </p>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn" style="margin-top: 15px;" onclick="window.location.href='messages.php?compose=<?php echo $faculty['user_id']; ?>'">
                                    <i class="fas fa-comment"></i> Message
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn" style="margin-top: 15px;"><i class="fas fa-lock"></i> Login to Message</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>

</html>
