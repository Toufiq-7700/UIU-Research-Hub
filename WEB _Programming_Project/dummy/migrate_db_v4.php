<?php
require_once __DIR__ . '/../database-functions.php';

echo "<h1>Database Migration (v4)</h1>";

try {
    global $db;

    // Create join_requests table if missing
    $exists = $db->fetchValue(
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'join_requests'"
    );

    if ((int)$exists === 0) {
        $sql = "
            CREATE TABLE join_requests (
                request_id INT AUTO_INCREMENT PRIMARY KEY,
                team_id INT NOT NULL,
                user_id INT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Pending',
                requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                responded_at TIMESTAMP NULL DEFAULT NULL,
                responded_by INT NULL,
                CONSTRAINT fk_join_requests_team FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
                CONSTRAINT fk_join_requests_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                CONSTRAINT fk_join_requests_responded_by FOREIGN KEY (responded_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
                UNIQUE KEY uniq_join_request (team_id, user_id),
                INDEX idx_join_requests_team_status (team_id, status),
                INDEX idx_join_requests_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        $stmt = $db->executeQuery($sql);
        if ($stmt === false) {
            throw new Exception("Failed to create join_requests table");
        }

        echo "<h2 style='color: green;'>Migration Successful!</h2>";
        echo "<p>Created <code>join_requests</code> table.</p>";
    } else {
        echo "<h2 style='color: orange;'>No Changes Needed</h2>";
        echo "<p><code>join_requests</code> table already exists.</p>";
    }

    echo "<p><a href='../team-finder.php'>Go to Team Finder</a></p>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
