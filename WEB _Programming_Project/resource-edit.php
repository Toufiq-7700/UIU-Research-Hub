<?php
require_once 'database-functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$resourceId = (int)($_GET['resource_id'] ?? ($_POST['resource_id'] ?? 0));
$redirect = (string)($_GET['redirect'] ?? ($_POST['redirect'] ?? 'resources'));

$redirectUrl = 'resources.php';
if ($redirect === 'dashboard') {
    $redirectUrl = 'dashboard.php';
}

if ($resourceId <= 0) {
    header("Location: {$redirectUrl}?resource_edit_error=invalid");
    exit;
}

$resource = getResourceById($resourceId);
if (!$resource) {
    header("Location: {$redirectUrl}?resource_edit_error=not_found");
    exit;
}

if ((int)$resource['uploaded_by'] !== (int)$_SESSION['user_id']) {
    header("Location: {$redirectUrl}?resource_edit_error=forbidden");
    exit;
}

$categories = getCategories();
$csrfToken = ensureCsrfToken();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if ($csrf === '' || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
        $error = 'Security check failed. Please try again.';
    } else {
        $title = (string)($_POST['title'] ?? '');
        $description = (string)($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $type = (string)($_POST['type'] ?? '');

        $result = updateResource($resourceId, (int)$_SESSION['user_id'], $title, $description, $categoryId, $type);
        if ($result['success']) {
            header("Location: {$redirectUrl}?resource_updated=1");
            exit;
        }
        $error = (string)($result['message'] ?? 'Failed to update resource');
    }

    $resource = getResourceById($resourceId) ?: $resource;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resource - UIU Research Hub</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="section">
        <div class="container">
            <div style="max-width: 650px; margin: 0 auto; background-color: #fff; padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow);">
                <div style="display:flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h2 style="margin: 0; color: var(--primary-color);"><i class="fas fa-pen"></i> Edit Resource</h2>
                    <a href="<?php echo htmlspecialchars($redirectUrl); ?>" class="btn btn-outline" style="text-decoration:none;">Back</a>
                </div>

                <?php if ($error !== ''): ?>
                    <div style="margin-top: 15px; background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 15px; color:#666; font-size: 0.9rem;">
                    File cannot be replaced here. This page edits metadata only.
                </div>

                <form method="POST" action="resource-edit.php" style="margin-top: 20px;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="resource_id" value="<?php echo (int)$resourceId; ?>">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input id="title" name="title" class="form-control" type="text" required maxlength="255"
                               value="<?php echo htmlspecialchars((string)($resource['title'] ?? '')); ?>">
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control">
                            <option value="0">General</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['category_id']; ?>" <?php echo ((int)($resource['category_id'] ?? 0) === (int)$cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="type">Type *</label>
                        <select id="type" name="type" class="form-control" required>
                            <?php
                            $curType = (string)($resource['resource_type'] ?? '');
                            $types = ['Paper', 'Dataset', 'Tool', 'Tutorial'];
                            foreach ($types as $t):
                            ?>
                                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo ($curType === $t) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="5"><?php echo htmlspecialchars((string)($resource['description'] ?? '')); ?></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Save Changes</button>
                </form>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
</body>
</html>

