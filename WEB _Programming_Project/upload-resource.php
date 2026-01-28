<?php
require_once 'database_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$categories = getCategories();
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = $_POST['category_id'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    // File upload logic
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if (empty($title) || empty($categoryId) || empty($type) || empty($description)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Please select a valid file to upload.';
    } else {
        $fileName = basename($_FILES['file']['name']);
        // Sanitize filename
        $fileName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $fileName);
        $targetPath = $uploadDir . time() . '_' . $fileName;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            // Save to DB
            $result = uploadResource($title, $description, $categoryId, $type, $targetPath, $_SESSION['user_id']);
            
            if ($result) {
                $success_message = 'Resource uploaded successfully!';
                echo '<script>setTimeout(function() { window.location.href = "resources.php"; }, 1500);</script>';
            } else {
                $error_message = 'Database error. Please try again.';
                unlink($targetPath); // Remove file if DB insert fails
            }
        } else {
            $error_message = 'Failed to upload file.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Resource - UIU Research Hub</title>
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

    <!-- Upload Section -->
    <section class="section">
        <div class="container">
            <div
                style="max-width: 600px; margin: 0 auto; background-color: #fff; padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow);">
                <h2 style="text-align: center; margin-bottom: 30px; color: var(--primary-color);">Upload Resource</h2>

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

                <form method="POST" action="upload-resource.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Resource Title</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="Enter resource title" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="type">Resource Type</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="Paper">Paper (PDF)</option>
                            <option value="Dataset">Dataset (CSV/ZIP)</option>
                            <option value="Tool">Tool (Code/Link)</option>
                            <option value="Tutorial">Tutorial (Video/Link)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="file">Upload File</label>
                        <input type="file" id="file" name="file" class="form-control" style="padding: 5px;" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="5" placeholder="Describe the resource..."
                            required></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Submit Resource</button>
                    <a href="resources.php" class="btn btn-outline" style="width: 100%; margin-top: 10px; display: block; text-align: center; text-decoration: none;">Cancel</a>
                </form>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>
</body>

</html>
