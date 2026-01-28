<?php
/**
 * UIU Research Hub - Database Connection File
 * Secure MySQLi Connection with PDO Support
 * 
 * @author Development Team
 * @version 1.0
 * @lastModified January 2025
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// DATABASE CONFIGURATION
// ============================================

// Define database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'uiu_research_hub');
define('DB_PORT', 3306);

// Environment settings
define('ENVIRONMENT', 'development'); // Change to 'production' for live
define('DEBUG_MODE', ENVIRONMENT === 'development');

// ============================================
// MYSQLI CONNECTION CLASS
// ============================================

class DatabaseConnection {
    private $connection;
    private $lastError;
    private $lastQuery;
    
    /**
     * Constructor - Establish database connection
     */
    public function __construct() {
        $this->connect();
    }
    
    /**
     * Establish MySQLi Connection
     * 
     * @return void
     */
    private function connect() {
        try {
            // Create connection
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME,
                DB_PORT
            );
            
            // Check connection
            if ($this->connection->connect_error) {
                throw new Exception('Database connection failed: ' . $this->connection->connect_error);
            }
            
            // Set charset to UTF-8
            if (!$this->connection->set_charset("utf8mb4")) {
                throw new Exception('Error loading character set utf8mb4: ' . $this->connection->error);
            }
            
            // Set timezone
            $this->connection->query("SET time_zone = '+00:00'");
            
            // Enable error reporting
            $this->connection->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
            
            if (DEBUG_MODE) {
                error_log('[DB] Database connection established successfully');
            }
            
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
        }
    }
    
    /**
     * Get database connection
     * 
     * @return mysqli
     */
    public function getConnection() {
        if ($this->connection === null || !$this->connection->ping()) {
            $this->connect(); // Reconnect if connection lost
        }
        return $this->connection;
    }
    
    /**
     * Prepare and execute a query with parameters
     * 
     * @param string $query SQL query with placeholders (?)
     * @param array $params Parameter values
     * @param string $types Parameter types (s=string, i=integer, d=double, b=blob)
     * @return mixed Query result or false on failure
     */
    public function executeQuery($query, $params = [], $types = '') {
        try {
            $this->lastQuery = $query;
            $stmt = $this->connection->prepare($query);
            
            if ($stmt === false) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }
            
            // Bind parameters if provided
            if (!empty($params)) {
                // Auto-detect types if not provided
                if (empty($types)) {
                    $types = '';
                    foreach ($params as $param) {
                        if (is_int($param)) {
                            $types .= 'i';
                        } elseif (is_float($param)) {
                            $types .= 'd';
                        } else {
                            $types .= 's';
                        }
                    }
                }
                
                $stmt->bind_param($types, ...$params);
            }
            
            // Execute query
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            
            return $stmt;
            
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Fetch single row as associative array
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @param string $types Parameter types
     * @return array|null Single row or null
     */
    public function fetchRow($query, $params = [], $types = '') {
        $stmt = $this->executeQuery($query, $params, $types);
        
        if ($stmt === false) {
            return null;
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    }
    
    /**
     * Fetch all rows as associative array
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @param string $types Parameter types
     * @return array Array of rows
     */
    public function fetchAll($query, $params = [], $types = '') {
        $stmt = $this->executeQuery($query, $params, $types);
        
        if ($stmt === false) {
            return [];
        }
        
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $rows;
    }
    
    /**
     * Fetch single value
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @param string $types Parameter types
     * @return mixed Single value or null
     */
    public function fetchValue($query, $params = [], $types = '') {
        $row = $this->fetchRow($query, $params, $types);
        
        if ($row === null) {
            return null;
        }
        
        return reset($row); // Return first value
    }
    
    /**
     * Insert data into table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|false Last insert ID or false
     */
    public function insert($table, $data) {
        try {
            $columns = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($values), '?');
            
            $query = "INSERT INTO `$table` (" . implode(', ', array_map(function($col) {
                return "`$col`";
            }, $columns)) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->executeQuery($query, $values);
            
            if ($stmt === false) {
                return false;
            }
            
            $insertId = $this->connection->insert_id;
            $stmt->close();
            
            return $insertId;
            
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Update data in table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where WHERE clause (e.g., "id = ?")
     * @param array $whereParams WHERE clause parameters
     * @return int|false Number of affected rows or false
     */
    public function update($table, $data, $where, $whereParams = []) {
        try {
            $columns = array_keys($data);
            $values = array_values($data);
            $setClause = implode(', ', array_map(function($col) {
                return "`$col` = ?";
            }, $columns));
            
            $query = "UPDATE `$table` SET $setClause WHERE $where";
            
            $allParams = array_merge($values, $whereParams);
            $stmt = $this->executeQuery($query, $allParams);
            
            if ($stmt === false) {
                return false;
            }
            
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return $affectedRows;
            
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete data from table
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (e.g., "id = ?")
     * @param array $params WHERE clause parameters
     * @return int|false Number of affected rows or false
     */
    public function delete($table, $where, $params = []) {
        try {
            $query = "DELETE FROM `$table` WHERE $where";
            $stmt = $this->executeQuery($query, $params);
            
            if ($stmt === false) {
                return false;
            }
            
            $affectedRows = $stmt->affected_rows;
            $stmt->close();
            
            return $affectedRows;
            
        } catch (Exception $e) {
            $this->handleError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Count rows matching criteria
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters
     * @return int Row count
     */
    public function count($table, $where = '', $params = []) {
        $query = "SELECT COUNT(*) as count FROM `$table`";
        if (!empty($where)) {
            $query .= " WHERE $where";
        }
        
        $result = $this->fetchValue($query, $params);
        return (int)$result;
    }
    
    /**
     * Check if record exists
     * 
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters
     * @return bool True if exists, false otherwise
     */
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    /**
     * Start a transaction
     * 
     * @return bool
     */
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollback() {
        return $this->connection->rollback();
    }
    
    /**
     * Get last error message
     * 
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * Get last query executed
     * 
     * @return string
     */
    public function getLastQuery() {
        return $this->lastQuery;
    }
    
    /**
     * Handle database errors
     * 
     * @param string $message Error message
     * @return void
     */
    private function handleError($message) {
        $this->lastError = $message;
        
        if (DEBUG_MODE) {
            error_log('[DB ERROR] ' . $message . ' | Query: ' . $this->lastQuery);
        } else {
            // Log to file in production without exposing details
            error_log('[DB ERROR] ' . date('Y-m-d H:i:s') . ' - Database error occurred');
        }
        
        // Don't expose database details to users in production
        if (!DEBUG_MODE) {
            // Return generic error
            return;
        }
    }
    
    /**
     * Close database connection
     * 
     * @return void
     */
    public function closeConnection() {
        if ($this->connection !== null) {
            $this->connection->close();
        }
    }
    
    /**
     * Destructor - Close connection on object destruction
     */
    public function __destruct() {
        $this->closeConnection();
    }
}

// ============================================
// GLOBAL DATABASE INSTANCE
// ============================================

// Create a singleton instance of the database connection
$db = null;

/**
 * Get global database instance
 * 
 * @return DatabaseConnection
 */
function getDatabase() {
    global $db;
    if ($db === null) {
        $db = new DatabaseConnection();
    }
    return $db;
}

// Initialize database connection
$db = new DatabaseConnection();

// ============================================
// PDO CONNECTION ALTERNATIVE (Optional)
// ============================================

class PDOConnection {
    private $pdo;
    private $lastError;
    
    /**
     * Constructor - Establish PDO connection
     */
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            if (DEBUG_MODE) {
                error_log('[PDO] PDO connection established successfully');
            }
            
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            if (DEBUG_MODE) {
                error_log('[PDO ERROR] ' . $this->lastError);
            }
            die('Database connection failed');
        }
    }
    
    /**
     * Get PDO instance
     * 
     * @return PDO
     */
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * Fetch single row
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array|null
     */
    public function fetchRow($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }
    
    /**
     * Fetch all rows
     * 
     * @param string $query SQL query
     * @param array $params Parameters
     * @return array
     */
    public function fetchAll($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }
}

// ============================================
// ERROR HANDLING & SECURITY
// ============================================

/**
 * Sanitize user input
 * 
 * @param string $input User input
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    $db = getDatabase();
    return $db->getConnection()->real_escape_string(trim($input));
}

/**
 * Hash password securely
 * 
 * @param string $password Password to hash
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password hash
 * 
     * @param string $password Plain password
     * @param string $hash Password hash
     * @return bool True if valid, false otherwise
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Set error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/error.log');
}

// ============================================
// DATABASE CONNECTION READY
// ============================================

// Connection is now ready for use throughout the application
// Usage: $db->fetchAll("SELECT * FROM users WHERE role_id = ?", [1]);
?>
