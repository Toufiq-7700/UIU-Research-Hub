<?php
require_once __DIR__ . '/../database-functions.php';

echo "<h1>Database Migration (v3)</h1>";

try {
    global $db;

    $changes = [];

    if (!dbColumnExists('users', 'skills')) {
        $stmt = $db->executeQuery("ALTER TABLE users ADD COLUMN skills TEXT AFTER bio");
        if ($stmt === false) {
            throw new Exception("Failed to add users.skills column");
        }
        $changes[] = "Added <code>users.skills</code>";
    }

    if (empty($changes)) {
        echo "<h2 style='color: orange;'>No Changes Needed</h2>";
        echo "<p>All required columns already exist.</p>";
    } else {
        echo "<h2 style='color: green;'>Migration Successful!</h2>";
        echo "<ul>";
        foreach ($changes as $c) {
            echo "<li>$c</li>";
        }
        echo "</ul>";
    }

    echo "<p><a href='../dashboard.php'>Go to Dashboard</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
