<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - UIU Research Hub</title>
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
                    <li><a href="resources.php">Resources</a></li>
                    <li><a href="login.php" class="btn">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Contact Section -->
    <section class="section">
        <div class="container">
            <div
                style="max-width: 600px; margin: 0 auto; background-color: #fff; padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow);">
                <h2 style="text-align: center; margin-bottom: 10px;">Contact Us</h2>
                <p style="text-align: center; margin-bottom: 30px; color: #666;">Have questions or suggestions? We'd
                    love to hear from you.</p>

                <form>
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" class="form-control" placeholder="Enter your name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" class="form-control" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" class="form-control" rows="5" placeholder="Write your message here..."
                            required></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Send Message</button>
                </form>
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
                    <a href="about.html">About</a>
                    <a href="terms.html">Terms</a>
                    <a href="contact.html">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>

</html>