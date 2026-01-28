<?php
require_once 'database_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error_message = '';
$success_message = '';
$categories = getCategories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamName = trim($_POST['team_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';
    $maxMembers = $_POST['max_members'] ?? 5;
    
    if (empty($teamName) || empty($description) || empty($categoryId)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        $result = createTeam($teamName, $description, $categoryId, $_SESSION['user_id'], $maxMembers);
        
        if ($result['success']) {
            $success_message = 'Team created successfully! Redirecting...';
            echo '<script>setTimeout(function() { window.location.href = "team-profile.php?team_id=' . $result['team_id'] . '"; }, 1000);</script>';
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
    <title>Create Team - UIU Research Hub</title>
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

    <section class="section" style="min-height: 80vh; display: flex; align-items: center;">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto; background-color: #fff; padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow);">
                <h2 style="margin-bottom: 30px; text-align: center; color: var(--primary-color);">Create New Team</h2>

                <?php if (!empty($success_message)): ?>
                    <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="create-team.php">
                    <div class="form-group">
                        <label for="team_name">Team Name</label>
                        <input type="text" id="team_name" name="team_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="max_members">Maximum Members</label>
                        <select id="max_members" name="max_members" class="form-control">
                            <option value="2">2 Members</option>
                            <option value="3">3 Members</option>
                            <option value="4">4 Members</option>
                            <option value="5" selected>5 Members</option>
                            <option value="6">6 Members</option>
                            <option value="8">8 Members</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="5" required></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Create Team</button>
                    <a href="team-finder.php" class="btn btn-outline" style="width: 100%; margin-top: 10px; display: block; text-align: center; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>
</html>
