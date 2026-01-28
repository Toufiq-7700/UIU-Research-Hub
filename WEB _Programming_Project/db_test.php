<?php
require_once 'db_connect.php';

echo "<h1>Database Connection Check</h1>";

try {
    $db = getDatabase();
    $conn = $db->getConnection();
    
    if ($conn->ping()) {
        echo "<h2 style='color: green;'>✅ Connected Successfully!</h2>";
        echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
        echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
        echo "<p><strong>User:</strong> " . DB_USER . "</p>";
        
        // Test query
        $result = $db->fetchRow("SELECT VERSION() as version");
        echo "<p><strong>MySQL Version:</strong> " . $result['version'] . "</p>";
        
        $tableCount = $db->fetchValue("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?", [DB_NAME]);
        echo "<p><strong>Tables Found:</strong> $tableCount</p>";
    } else {
        echo "<h2 style='color: red;'>❌ Connection Failed</h2>";
    }
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
