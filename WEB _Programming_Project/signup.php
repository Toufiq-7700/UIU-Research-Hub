<?php
// Database connection
require_once 'db-connect.php';

// Handle signup form submission
$signup_response = '';
$signup_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $full_name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (empty($role)) {
        $errors[] = 'Please select your role';
    }
    
    // Check if email already exists
    if (empty($errors)) {
        if ($db->exists('users', 'email = ?', [$email])) {
            $errors[] = 'Email already registered';
        }
    }
    
    // If no errors, register user
    if (empty($errors)) {
        try {
            // Hash password
            $password_hash = hashPassword($password);
            
            // Determine role_id (1=Member/Student, 2=Faculty)
            $role_id = ($role === 'faculty') ? 2 : 1;
            
            // Insert user into database
            $userId = $db->insert('users', [
                'full_name' => $full_name,
                'email' => $email,
                'password_hash' => $password_hash,
                'role_id' => $role_id,
                'is_active' => true
            ]);
            
            if ($userId !== false) {
                $signup_response = 'Registration successful! Redirecting to login...';
                
                // JavaScript redirect after 2 seconds
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "login.php";
                    }, 2000);
                </script>';
            } else {
                $signup_error = 'Registration failed. Please try again.';
            }
        } catch (Exception $e) {
            $signup_error = 'Registration failed. Please try again.';
        }
    } else {
        $signup_error = implode('<br>', array_map(
            fn($msg) => htmlspecialchars((string)$msg),
            $errors
        ));
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - UIU Research Hub</title>
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
                    <li><a href="login.php" class="btn">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Sign Up Section -->
    <section class="section" style="min-height: 80vh; display: flex; align-items: center;">
        <div class="container">
            <div
                style="max-width: 900px; margin: 0 auto; background-color: #fff; padding: 60px; border-radius: var(--radius); box-shadow: var(--shadow); text-align: center;">
                <h2 style="margin-bottom: 40px; color: var(--primary-color); font-size: 2.5rem;">Create Account</h2>

                <!-- Success Message -->
                <?php if (!empty($signup_response)): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars((string)$signup_response); ?>
                </div>
                <?php endif; ?>

                <!-- Error Messages -->
                <?php if (!empty($signup_error)): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $signup_error; ?>
                </div>
                <?php endif; ?>

                <form id="signup-form" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div class="form-group" style="text-align: left;">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required style="padding: 12px; font-size: 1rem;">
                    </div>

                    <div class="form-group" style="text-align: left;">
                        <label for="role">I am a</label>
                        <select id="role" name="role" class="form-control" required style="cursor: pointer; padding: 12px; font-size: 1rem;">
                            <option value="">Select your role</option>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty Member</option>
                        </select>
                    </div>

                    <div class="form-group" style="text-align: left; grid-column: 1 / -1;">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required style="padding: 12px; font-size: 1rem;">
                    </div>

                    <div class="form-group" style="text-align: left;">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create a password"
                            required style="padding: 12px; font-size: 1rem;">
                    </div>

                    <div class="form-group" style="text-align: left;">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" class="form-control"
                            placeholder="Confirm your password" required style="padding: 12px; font-size: 1rem;">
                    </div>

                    <button type="submit" class="btn" style="width: 100%; margin-top: 10px; grid-column: 1 / -1; padding: 15px; font-size: 1.1rem;">Sign Up</button>
                </form>

                <p style="margin-top: 20px; font-size: 0.9rem;">
                    Already have an account? <a href="login.php"
                        style="color: var(--primary-color); font-weight: 600;">Login</a>
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
