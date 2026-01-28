<?php
require_once 'database-functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (strcasecmp((string) getUserRoleName((int) $_SESSION['user_id']), 'Faculty') !== 0) {
    header("Location: categories.php?category_manage_error=forbidden");
    exit;
}

$categoryId = (int) ($_GET['category_id'] ?? ($_POST['category_id'] ?? 0));
if ($categoryId <= 0) {
    header("Location: categories.php?category_manage_error=invalid");
    exit;
}

$category = getCategory($categoryId);
if (!$category) {
    header("Location: categories.php?category_manage_error=not_found");
    exit;
}

$csrfToken = ensureCsrfToken();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    if ($csrf === '' || !hash_equals((string) $_SESSION['csrf_token'], $csrf)) {
        $error = 'Security check failed. Please try again.';
    } else {
        $name = (string) ($_POST['category_name'] ?? '');
        $description = (string) ($_POST['description'] ?? '');

        $result = updateCategory((int) $_SESSION['user_id'], $categoryId, $name, $description, (string) ($category['icon_class'] ?? ''));
        if ($result['success']) {
            header("Location: categories.php?category_updated=1");
            exit;
        }
        $error = (string) ($result['message'] ?? 'Failed to update category');
    }

    // Refresh category for form re-render
    $category = getCategory($categoryId) ?: $category;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>

<body>
    <?php include 'header.php'; ?>

    <section class="section" style="min-height: 80vh; display: flex; align-items: center;">
        <div class="container">
            <div
                style="max-width: 700px; margin: 0 auto; background-color: #fff; padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow);">
                <div
                    style="display:flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h2 style="margin: 0; color: var(--primary-color);"><i class="fas fa-edit"></i> Edit Category</h2>
                    <a href="categories.php" class="btn btn-outline" style="text-decoration:none;">Back</a>
                </div>

                <?php if ($error !== ''): ?>
                    <div
                        style="margin-top: 15px; background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="category-edit.php" style="margin-top: 20px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="category_id" value="<?php echo (int) $categoryId; ?>">

                    <div class="form-group">
                        <label for="category_name">Category Name *</label>
                        <input id="category_name" name="category_name" class="form-control" type="text" required
                            maxlength="100"
                            value="<?php echo htmlspecialchars((string) ($category['category_name'] ?? '')); ?>">
                    </div>


                    <div class="form-group">
                        <label for="description">Description (optional, max 250 chars)</label>
                        <textarea id="description" name="description" class="form-control" rows="4"
                            maxlength="250"><?php echo htmlspecialchars((string) ($category['description'] ?? '')); ?></textarea>
                    </div>

                    <button type="submit" class="btn">Save Changes</button>
                    <div style="margin-top: 12px; color:#666; font-size: 0.9rem;">
                        Delete will be blocked if this category is used by teams or resources.
                    </div>
                </form>

                <form method="POST" action="category-delete.php" style="margin-top: 12px;"
                    onsubmit="return confirm('Delete this category? This cannot be undone.');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="category_id" value="<?php echo (int) $categoryId; ?>">
                    <button type="submit" class="btn btn-outline" style="border-color:#e74c3c; color:#e74c3c;">Delete
                        Category</button>
                </form>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>

</html>