<?php
require_once __DIR__ . '/../database-functions.php';

echo "<h1>Database Migration (v5)</h1>";

try {
    global $db;

    if (!dbColumnExists('categories', 'created_by')) {
        $stmt = $db->executeQuery("ALTER TABLE categories ADD COLUMN created_by INT NULL AFTER icon_class");
        if ($stmt === false) {
            throw new Exception("Failed to add categories.created_by column");
        }
        echo "<p style='color: green;'>Added <code>categories.created_by</code></p>";
    } else {
        echo "<p style='color: orange;'>" . htmlspecialchars("categories.created_by already exists") . "</p>";
    }

    if (!dbColumnExists('categories', 'created_at')) {
        $stmt = $db->executeQuery("ALTER TABLE categories ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER created_by");
        if ($stmt === false) {
            throw new Exception("Failed to add categories.created_at column");
        }
        echo "<p style='color: green;'>Added <code>categories.created_at</code></p>";
    } else {
        echo "<p style='color: orange;'>" . htmlspecialchars("categories.created_at already exists") . "</p>";
    }

    // Add FK + index best-effort (ignore failures if already present)
    $db->executeQuery("CREATE INDEX idx_categories_created_by ON categories (created_by)");
    $db->executeQuery("ALTER TABLE categories ADD CONSTRAINT fk_categories_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE");

    echo "<h2 style='color: green;'>Done</h2>";
    echo "<p><a href='../categories.php'>Go to Categories</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
