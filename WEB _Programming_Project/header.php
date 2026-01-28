<?php
// Ensure session is started (handled in db-connect.php, but good practice to check)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header>
    <div class="container">
        <nav>
            <a href="index.php" class="logo">UIU Research Hub</a>
            <div class="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </div>
            <ul class="nav-links">
                <li><a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                <li><a href="categories.php" class="<?php echo ($current_page == 'categories.php' || $current_page == 'category-view.php' || $current_page == 'category-edit.php') ? 'active' : ''; ?>">Categories</a></li>
                <li><a href="team-finder.php" class="<?php echo ($current_page == 'team-finder.php' || $current_page == 'team-profile.php') ? 'active' : ''; ?>">Teams</a></li>
                <li><a href="faculty.php" class="<?php echo ($current_page == 'faculty.php') ? 'active' : ''; ?>">Faculty</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="students.php" class="<?php echo ($current_page == 'students.php' || $current_page == 'user-profile.php') ? 'active' : ''; ?>">Students</a></li>
                <?php endif; ?>
                <li><a href="resources.php" class="<?php echo ($current_page == 'resources.php') ? 'active' : ''; ?>">Resources</a></li>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="messages.php" class="<?php echo ($current_page == 'messages.php') ? 'active' : ''; ?>"><i class="fas fa-comment-alt"></i> Messages</a></li>
                    <li><a href="logout.php" class="btn" style="background-color: #e74c3c;">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>
