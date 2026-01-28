<?php
require_once 'database-functions.php';

// Get available filter options
$categories = getCategories();
// Years could be dynamic too, but keeping static for now or can select distinct years

// Get filter params
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$year = $_GET['year'] ?? ''; // Not implemented in getResources yet, but good to have in UI
$type = $_GET['type'] ?? '';

$canUploadResource = false;
$csrfToken = '';
if (isset($_SESSION['user_id'])) {
    $canUploadResource = userCanUploadResource($_SESSION['user_id']);
    $csrfToken = ensureCsrfToken();
}

// Fetch resources
$resources = getResources($search, $category, $type);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - UIU Research Hub</title>
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
    <section class="section" style="background-color: #fff; padding: 40px 0;">
        <div class="container">
            <div
                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h1>Research Resources</h1>
                    <p>Access papers, datasets, tools, and tutorials.</p>
                </div>
                <?php if ($canUploadResource): ?>
                    <a href="resource-upload.php" class="btn"><i class="fas fa-upload"></i> Upload Resource</a>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <!-- Admin/others: upload disabled -->
                <?php else: ?>
                    <a href="login.php" class="btn"><i class="fas fa-lock"></i> Login to Upload</a>
                <?php endif; ?>
            </div>

            <?php if (($_GET['access_denied'] ?? '') === 'upload'): ?>
                <div style="margin-top: 20px; background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: var(--radius);">
                    Only students and faculty members can upload resources.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['resource_deleted'])): ?>
                <div style="margin-top: 20px; background-color: #d4edda; color: #155724; padding: 15px; border-radius: var(--radius);">
                    Resource deleted successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['resource_updated'])): ?>
                <div style="margin-top: 20px; background-color: #d4edda; color: #155724; padding: 15px; border-radius: var(--radius);">
                    Resource updated successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['resource_delete_error'])): ?>
                <div style="margin-top: 20px; background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: var(--radius);">
                    Failed to delete resource. Please try again.
                </div>
            <?php endif; ?>

            <!-- Filters and Search -->
            <div style="margin-top: 30px; background-color: #f9f9f9; padding: 20px; border-radius: var(--radius);">
                <form class="search-form" method="GET" action="resources.php" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" name="search" class="form-control" placeholder="Search resources..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div style="width: 150px;">
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="width: 120px;">
                        <select name="type" class="form-control">
                            <option value="">All Types</option>
                            <option value="Paper" <?php echo $type == 'Paper' ? 'selected' : ''; ?>>Paper</option>
                            <option value="Dataset" <?php echo $type == 'Dataset' ? 'selected' : ''; ?>>Dataset</option>
                            <option value="Tool" <?php echo $type == 'Tool' ? 'selected' : ''; ?>>Tool</option>
                            <option value="Tutorial" <?php echo $type == 'Tutorial' ? 'selected' : ''; ?>>Tutorial</option>
                        </select>
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-filter"></i> Filter</button>
                    <?php if (!empty($search) || !empty($category) || !empty($type)): ?>
                        <a href="resources.php" class="btn btn-outline" style="text-decoration:none; padding-top: 10px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </section>

    <!-- Resources Grid -->
    <section class="section">
        <div class="container">
            <?php if (empty($resources)): ?>
                <div style="text-align: center; padding: 40px; background: #fff; border-radius: 8px;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No resources found</h3>
                    <p>Try adjusting your search filters or upload a new resource!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-3">
                    <?php foreach ($resources as $resource): ?>
                        <div class="card">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                <?php
                                $icon = 'fas fa-file-alt';
                                $color = '#7f8c8d';
                                switch($resource['resource_type']) {
                                    case 'Paper': $icon = 'fas fa-file-pdf'; $color = '#e74c3c'; break;
                                    case 'Dataset': $icon = 'fas fa-database'; $color = '#2ecc71'; break;
                                    case 'Tool': $icon = 'fas fa-tools'; $color = '#34495e'; break;
                                    case 'Tutorial': $icon = 'fas fa-video'; $color = '#9b59b6'; break;
                                }
                                ?>
                                <i class="<?php echo $icon; ?>" style="font-size: 2rem; color: <?php echo $color; ?>;"></i>
                                <div>
                                    <h4 style="margin-bottom: 0;"><?php echo htmlspecialchars($resource['title']); ?></h4>
                                    <span style="font-size: 0.8rem; color: #666;">
                                        <?php echo htmlspecialchars($resource['resource_type']); ?>
                                        &bull; Uploaded by <?php echo htmlspecialchars($resource['uploader_name'] ?? 'Unknown'); ?>
                                        (<?php echo htmlspecialchars($resource['uploader_role'] ?? 'User'); ?>)
                                        &bull; <?php echo date('M d, Y', strtotime($resource['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            <p style="font-size: 0.9rem; color: #555; margin-bottom: 15px;">
                                <?php
                                $desc = trim((string)($resource['description'] ?? ''));
                                if ($desc === '') {
                                    echo 'No description provided.';
                                } else {
                                    echo htmlspecialchars(substr($desc, 0, 100)) . (strlen($desc) > 100 ? '...' : '');
                                }
                                ?>
                            </p>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.8rem; background-color: #f0f0f0; color: #333; padding: 3px 8px; border-radius: 10px;">
                                    <?php echo htmlspecialchars($resource['category_name'] ?? 'General'); ?>
                                </span>
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" target="_blank" class="btn btn-outline" style="padding: 5px 15px; font-size: 0.9rem;" download>Download</a>
                                        <?php if ((int)$resource['uploaded_by'] === (int)$_SESSION['user_id']): ?>
                                            <form method="POST" action="resource-delete.php" style="display: inline;" onsubmit="return confirm('Delete this resource? This cannot be undone.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="resource_id" value="<?php echo (int)$resource['resource_id']; ?>">
                                                <input type="hidden" name="redirect" value="resources">
                                                <button type="submit" class="btn btn-outline" style="padding: 5px 15px; font-size: 0.9rem; border-color: #e74c3c; color: #e74c3c;">Delete</button>
                                            </form>
                                            <a href="resource-edit.php?resource_id=<?php echo (int)$resource['resource_id']; ?>&redirect=resources" class="btn btn-outline" style="padding: 5px 15px; font-size: 0.9rem; text-decoration:none;">
                                                Edit
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="btn btn-outline" style="padding: 5px 15px; font-size: 0.9rem; opacity: 0.5; cursor: not-allowed;" title="Login to download">Download</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
</body>

</html>
