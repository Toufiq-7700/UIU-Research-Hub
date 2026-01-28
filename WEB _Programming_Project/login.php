<?php
// Include database functions
require_once 'database-functions.php';

// Handle login
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        $result = authenticateUser($email, $password);
        
        if ($result['success']) {
            $success_message = 'Login successful! Redirecting...';
            // JavaScript redirect for better UX (or could use header)
            echo '<script>
                localStorage.setItem("userLoggedIn", "true"); // Keeping for compatibility with existing JS for now
                setTimeout(function() {
                    window.location.href = "team-finder.php";
                }, 1000);
            </script>';
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UIU Research Hub</title>
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
    <header>
        <div class="container">
            <nav>
                <a href="index.php" class="logo">UIU Research Hub</a>
                <div class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </div>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="team-finder.php">Teams</a></li>
                    <li><a href="faculty.php">Faculty</a></li>
                    <li><a href="resources.php">Resources</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="logout.php" class="btn" style="background-color: #e74c3c;">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Login Section -->
    <section class="section" style="min-height: 80vh; display: flex; align-items: center;">
        <div class="container">
            <div
                style="max-width: 400px; margin: 0 auto; background-color: #fff; padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow); text-align: center;">
                <h2 style="margin-bottom: 30px; color: var(--primary-color);">Welcome Back</h2>

                <?php if (!empty($success_message)): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars((string)$success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo htmlspecialchars((string)$error_message); ?>
                    </div>
                <?php endif; ?>

                <form id="loginForm" method="POST" action="login.php">
                    <div class="form-group" style="text-align: left;">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group" style="text-align: left;">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password"
                            required>
                    </div>

                    <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Login</button>
                </form>

                <p style="margin-top: 20px; font-size: 0.9rem;">
                    Don't have an account? <a href="signup.php"
                        style="color: var(--primary-color); font-weight: 600;">Create
                        Account</a>
                </p>
                <p style="margin-top: 10px; font-size: 0.9rem;">
                    <a href="#" style="color: #666;">Forgot Password?</a>
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <h3>UIU Research Hub</h3>
                    <p>&copy; 2025 UIU Research Hub. All rights reserved.</p>
                </div>
                <div class="footer-links">
                    <a href="about.php">About</a>
                    <a href="terms.php">Terms</a>
                    <a href="contact.php">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>

</html>
