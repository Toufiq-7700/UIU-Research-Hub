<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UIU Research Hub - Connect, Collaborate, Create</title>
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

    <!-- Hero Section -->
    <section class="hero section"
        style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); text-align: center; padding: 100px 0;">
        <div class="container">
            <h1 style="font-size: 3rem; margin-bottom: 20px; color: var(--primary-color);">UIU Research Hub</h1>
            <p style="font-size: 1.2rem; margin-bottom: 40px; color: #555;">Connect, Collaborate, and Create Research
                Together.</p>
            <div style="display: flex; gap: 20px; justify-content: center;">
                <a href="categories.php" class="btn">Get Started</a>
                <a href="resources.php" class="btn btn-outline">Explore Resources</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 50px; font-size: 2.2rem; color: var(--secondary-color);">Why Join UIU Research Hub?</h2>
            <div class="grid grid-4" style="gap: 30px;">
                <a href="faculty.php" class="card" style="text-align: center; color: inherit; border-top: 5px solid #2980b9;">
                    <i class="fas fa-handshake" style="font-size: 3rem; color: #2980b9; margin-bottom: 25px;"></i>
                    <h3 style="font-size: 1.2rem;">Connect with Faculty</h3>
                    <p style="font-size: 0.9rem;">Directly message professors and find supervisors for your thesis or research projects.</p>
                </a>
                <a href="team-finder.php" class="card" style="text-align: center; color: inherit; border-top: 5px solid var(--primary-color);">
                    <i class="fas fa-users" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 25px;"></i>
                    <h3 style="font-size: 1.2rem;">Team Finder</h3>
                    <p style="font-size: 0.9rem;">Find the perfect partners for your research projects or join existing teams.</p>
                </a>
                <a href="categories.php" class="card" style="text-align: center; color: inherit; border-top: 5px solid var(--accent-color);">
                    <i class="fas fa-layer-group" style="font-size: 3rem; color: var(--accent-color); margin-bottom: 25px;"></i>
                    <h3 style="font-size: 1.2rem;">Research Areas</h3>
                    <p style="font-size: 0.9rem;">Explore diverse research fields from AI to Robotics and find your niche.</p>
                </a>
                <a href="resources.php" class="card" style="text-align: center; color: inherit; border-top: 5px solid #9b59b6;">
                    <i class="fas fa-book-open" style="font-size: 3rem; color: #9b59b6; margin-bottom: 25px;"></i>
                    <h3 style="font-size: 1.2rem;">Resource Sharing</h3>
                    <p style="font-size: 0.9rem;">Access and share valuable papers, datasets, and tools with the community.</p>
                </a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="section" style="background-color: #fff;">
        <div class="container">
            <div class="grid grid-2" style="align-items: center;">
                <div>
                    <h2>About UIU Research Hub</h2>
                        students and faculty. We believe that great research happens when brilliant minds come together.
                    </p>
                    <p>Whether you are looking for a teammate, need access to cutting-edge resources, or want to
                        showcase your work, UIU Research Hub is your go-to destination.</p>
                    <a href="about.php" class="btn btn-secondary" style="margin-top: 20px;">Learn More</a>
                </div>
                <div style="text-align: center;">
                    <img src="uiu.png" alt="UIU Research Hub"
                        style="border-radius: var(--radius); box-shadow: var(--shadow);">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>

</html>
