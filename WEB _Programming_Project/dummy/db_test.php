<?php
require_once __DIR__ . '/../db-connect.php';

echo "<h1>Database Connection Check</h1>";

try {
    $db = getDatabase();
    $conn = $db->getConnection();

    if ($conn->ping()) {
        echo "<h2 style='color: green;'>&#x2705; Connected Successfully!</h2>";
        echo "<p><strong>Host:</strong> " . htmlspecialchars((string)DB_HOST) . "</p>";
        echo "<p><strong>Database:</strong> " . htmlspecialchars((string)DB_NAME) . "</p>";
        echo "<p><strong>User:</strong> " . htmlspecialchars((string)DB_USER) . "</p>";

        $result = $db->fetchRow("SELECT VERSION() as version");
        echo "<p><strong>MySQL Version:</strong> " . htmlspecialchars((string)($result['version'] ?? '')) . "</p>";

        $tableCount = $db->fetchValue(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?",
            [DB_NAME]
        );
        echo "<p><strong>Tables Found:</strong> " . htmlspecialchars((string)$tableCount) . "</p>";
    } else {
        echo "<h2 style='color: red;'>&#x274C; Connection Failed</h2>";
    }
} catch (Exception $e) {
    echo "<h2 style='color: red;'>&#x274C; Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
