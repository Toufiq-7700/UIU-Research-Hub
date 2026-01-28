<?php
require_once 'database-functions.php';
$categories = getCategories();

$isFaculty = false;
$csrfToken = '';
$userName = '';
if (isset($_SESSION['user_id'])) {
    $roleName = (string) getUserRoleName((int) $_SESSION['user_id']);
    $isFaculty = strcasecmp($roleName, 'Faculty') === 0;
    if ($isFaculty) {
        $csrfToken = ensureCsrfToken();
        $u = getUserWithRole((int) $_SESSION['user_id']);
        $userName = (string) ($u['full_name'] ?? '');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - UIU Research Hub</title>
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
    <section class="section" style="background-color: #fff; padding: 40px 0; text-align: center;">
        <div class="container">
            <h1>Research Categories</h1>
            <p>Explore diverse fields of study and find your passion.</p>
        </div>
    </section>

    <!-- Categories Grid -->
    <section class="section">
        <div class="container">
            <?php if (isset($_GET['category_added'])): ?>
                <div
                    style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    Category added successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['category_updated'])): ?>
                <div
                    style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    Category updated successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['category_deleted'])): ?>
                <div
                    style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    Category deleted successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['category_error'])): ?>
                <div
                    style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    <?php
                    $err = (string) ($_GET['category_error'] ?? '');
                    $msg = 'Failed to add category.';
                    if ($err === 'csrf')
                        $msg = 'Security check failed. Please refresh and try again.';
                    elseif ($err === 'duplicate')
                        $msg = 'Category name already exists.';
                    elseif ($err === 'name_required')
                        $msg = 'Category name is required.';
                    elseif ($err === 'desc_length')
                        $msg = 'Description must be 250 characters or less.';
                    elseif ($err === 'missing_columns')
                        $msg = 'Category management is not available yet. Run dummy/migrate_db_v5.php.';
                    elseif ($err === 'forbidden')
                        $msg = 'Only faculty members can add categories.';
                    echo htmlspecialchars($msg);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['category_manage_error'])): ?>
                <div
                    style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: var(--radius); margin-bottom: 20px;">
                    <?php
                    $err = (string) ($_GET['category_manage_error'] ?? '');
                    $msg = 'Failed to manage category.';
                    if ($err === 'csrf')
                        $msg = 'Security check failed. Please refresh and try again.';
                    elseif ($err === 'forbidden')
                        $msg = 'Only faculty members can manage categories.';
                    elseif ($err === 'invalid')
                        $msg = 'Invalid category.';
                    elseif ($err === 'not_found')
                        $msg = 'Category not found.';
                    elseif ($err === 'duplicate')
                        $msg = 'Category name already exists.';
                    elseif ($err === 'in_use')
                        $msg = 'Category is in use by teams/resources and cannot be deleted.';
                    echo htmlspecialchars($msg);
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($isFaculty): ?>
                <div class="card" style="margin-bottom: 30px;">
                    <div
                        style="display:flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem;"><i class="fas fa-folder-plus"></i> Category Management
                            </h2>
                            <p style="margin: 8px 0 0; color: #666;">Add new research categories. Visible to everyone after
                                submission.</p>
                        </div>
                        <div style="color:#666; font-size: 0.9rem;">
                            Created by:
                            <strong><?php echo htmlspecialchars($userName !== '' ? $userName : 'Faculty'); ?></strong>
                        </div>
                    </div>

                    <?php if (!dbColumnExists('categories', 'created_by') || !dbColumnExists('categories', 'created_at')): ?>
                        <div
                            style="margin-top: 15px; background-color: #fff3cd; color: #856404; padding: 12px; border-radius: var(--radius);">
                            Category management requires a DB update. Run <code>dummy/migrate_db_v5.php</code>.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="category-create.php" style="margin-top: 20px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <div class="form-group">
                                <label for="category_name">Category Name *</label>
                                <input id="category_name" name="category_name" class="form-control" type="text" required
                                    maxlength="100" placeholder="e.g., Human-Computer Interaction">
                            </div>
                            <div class="form-group">
                                <label for="description">Description (optional, max 250 chars)</label>
                                <textarea id="description" name="description" class="form-control" rows="3" maxlength="250"
                                    placeholder="Short description..."></textarea>
                            </div>
                            <div style="display:flex; gap: 10px; flex-wrap: wrap;">
                                <button type="submit" class="btn">Add Category</button>
                                <button type="reset" class="btn btn-outline">Clear</button>
                            </div>
                        </form>

                        <div style="margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                            <h3 style="margin: 0 0 12px; font-size: 1.2rem;"><i class="fas fa-list"></i> Existing Categories
                            </h3>
                            <?php if (empty($categories)): ?>
                                <p style="color:#666; margin:0;">No categories found.</p>
                            <?php else: ?>
                                <div style="display:grid; gap: 10px;">
                                    <?php foreach ($categories as $cat): ?>
                                        <div
                                            style="display:flex; justify-content: space-between; align-items: center; gap: 10px; padding: 12px; background: #f9f9f9; border-radius: 10px;">
                                            <div style="display:flex; align-items: center; gap: 10px;">
                                                <i class="<?php echo htmlspecialchars($cat['icon_class'] ?? 'fas fa-layer-group'); ?>"
                                                    style="color: var(--primary-color);"></i>
                                                <div>
                                                    <div style="font-weight: 700;">
                                                        <?php echo htmlspecialchars($cat['category_name']); ?></div>
                                                    <?php if (!empty($cat['created_by_name'])): ?>
                                                        <div style="color:#666; font-size: 0.85rem;">Created by
                                                            <?php echo htmlspecialchars($cat['created_by_name']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <a href="category-edit.php?category_id=<?php echo (int) $cat['category_id']; ?>"
                                                class="btn btn-outline"
                                                style="padding: 6px 12px; font-size: 0.9rem; text-decoration:none;">
                                                Edit
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-3">
                <?php foreach ($categories as $cat): ?>
                    <div class="card" style="text-align: center;">
                        <i class="<?php echo htmlspecialchars($cat['icon_class'] ?? 'fas fa-layer-group'); ?>"
                            style="font-size: 3rem; color: var(--primary-color); margin-bottom: 20px;"></i>
                        <h3><?php echo htmlspecialchars($cat['category_name']); ?></h3>
                        <p><?php echo htmlspecialchars($cat['description'] ?? 'Explore opportunities in ' . $cat['category_name']); ?>
                        </p>
                        <!-- Updated Link -->
                        <a href="category-view.php?category=<?php echo urlencode($cat['category_id']); ?>"
                            class="btn btn-outline" style="margin-top: 15px;">Explore</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>

</html>