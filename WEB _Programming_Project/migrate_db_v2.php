<?php
require_once 'database_functions.php';

echo "<h1>Database Migration (v2)</h1>";

try {
    global $db;
    
    // Add columns if they don't exist
    $query = "
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS skills TEXT AFTER bio,
        ADD COLUMN IF NOT EXISTS experience TEXT AFTER skills
    ";
    
    $stmt = $db->executeQuery($query);
    
    if ($stmt !== false) {
        echo "<h2 style='color: green;'>Migration Successful!</h2>";
        echo "<p>Added 'skills' and 'experience' columns to 'users' table.</p>";
    } else {
        echo "<h2 style='color: orange;'>Migration Checked</h2>";
        echo "<p>Columns might already exist or query failed silently. Check database manually if issues persist.</p>";
    }
    
    echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
