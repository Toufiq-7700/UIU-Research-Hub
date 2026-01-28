<?php
/**
 * UIU Research Hub - Sample Database Integration
 * This file demonstrates how to integrate the database with the existing project
 * 
 * Copy and adapt these patterns for your application
 */

// Include database connection
require_once 'db_connect.php';

// ============================================
// USER AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Register a new user
 * 
 * @param string $fullName User's full name
 * @param string $email User's email
 * @param string $password Plain password
 * @param int $roleId User role ID
 * @return array Success/error response
 */
function registerUser($fullName, $email, $password, $roleId = 1) {
    global $db;
    
    try {
        // Validate input
        if (empty($fullName) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if email already exists
        if ($db->exists('users', 'email = ?', [$email])) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        
        // Hash password
        $passwordHash = hashPassword($password);
        
        // Insert user
        $userId = $db->insert('users', [
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role_id' => $roleId,
            'is_active' => true
        ]);
        
        if ($userId === false) {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
        
        // Log activity
        logActivity($userId, null, 'USER_REGISTERED', 'User account created');
        
        return ['success' => true, 'message' => 'Registration successful', 'user_id' => $userId];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
    }
}

/**
 * Update user profile
 * 
 * @param int $userId
 * @param string $skills
 * @param string $experience
 * @param string $profilePicPath (optional)
 * @return boolean
 */
function updateUserProfile($userId, $skills, $experience, $profilePicPath = null) {
    global $db;
    
    $data = [
        'skills' => $skills,
        'experience' => $experience
    ];
    
    if ($profilePicPath) {
        $data['profile_picture'] = $profilePicPath;
    }
    
    return $db->update('users', $data, 'user_id = ?', [$userId]);
}

/**
 * Authenticate user
 * 
 * @param string $email User's email
 * @param string $password User's password
 * @return array Success/error response with user data
 */
function authenticateUser($email, $password) {
    global $db;
    
    try {
        // Get user by email
        $user = $db->fetchRow(
            "SELECT u.*, r.role_name FROM users u 
             JOIN roles r ON u.role_id = r.role_id 
             WHERE u.email = ? AND u.is_active = TRUE",
            [$email]
        );
        
        if ($user === null) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Verify password
        if (!verifyPassword($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Update last login
        $db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')],
            'user_id = ?',
            [$user['user_id']]
        );
        
        // Log activity
        logActivity($user['user_id'], null, 'USER_LOGIN', 'User logged in');
        
        // Remove sensitive data
        unset($user['password_hash']);
        
        // Store in session
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_email'] = $user['email'];
        
        return ['success' => true, 'message' => 'Login successful', 'user' => $user];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()];
    }
}

// ============================================
// TEAM MANAGEMENT FUNCTIONS
// ============================================

/**
 * Create a new team
 * 
 * @param string $teamName Team name
 * @param string $description Team description
 * @param int $categoryId Category ID
 * @param int $leaderId Team leader user ID
 * @param int $maxMembers Maximum members
 * @param int|null $eventId Event ID (optional)
 * @return array Success/error response
 */
function createTeam($teamName, $description, $categoryId, $leaderId, $maxMembers = 5, $eventId = null) {
    global $db;
    
    try {
        // Validate inputs
        if (empty($teamName) || empty($description)) {
            return ['success' => false, 'message' => 'Team name and description are required'];
        }
        
        // Check if user exists
        if (!$db->exists('users', 'user_id = ?', [$leaderId])) {
            return ['success' => false, 'message' => 'Team leader not found'];
        }
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            // Insert team
            $teamId = $db->insert('teams', [
                'team_name' => $teamName,
                'description' => $description,
                'category_id' => $categoryId,
                'event_id' => $eventId,
                'team_leader_id' => $leaderId,
                'max_members' => $maxMembers,
                'current_members' => 1,
                'status' => 'Recruiting'
            ]);
            
            // Add team leader as first member
            $db->insert('team_members', [
                'team_id' => $teamId,
                'user_id' => $leaderId,
                'member_role' => 'Team Leader',
                'status' => 'Active'
            ]);
            
            // Log activity
            logActivity($leaderId, $teamId, 'TEAM_CREATED', 'Team created: ' . $teamName);
            
            $db->commit();
            
            return ['success' => true, 'message' => 'Team created successfully', 'team_id' => $teamId];
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error creating team: ' . $e->getMessage()];
    }
}

/**
 * Get team full profile
 * 
 * @param int $teamId Team ID
 * @return array|null Team data or null if not found
 */
function getTeamProfile($teamId) {
    global $db;
    
    return $db->fetchRow(
        "SELECT * FROM team_full_profile WHERE team_id = ?",
        [$teamId]
    );
}

/**
 * Get team members
 * 
 * @param int $teamId Team ID
 * @return array Array of team members
 */
// ... existing code ...
function getTeamMembers($teamId) {
    global $db;
    
    return $db->fetchAll(
        "SELECT u.user_id, u.full_name, u.email, tm.member_role, tm.contribution_score
         FROM team_members tm
         JOIN users u ON tm.user_id = u.user_id
         WHERE tm.team_id = ? AND tm.status = 'Active'
         ORDER BY tm.member_role DESC",
        [$teamId]
    );
}

/**
 * Get all teams with optional search
 * 
 * @param string $search Search query
 * @param string $category Category filter
 * @return array Array of teams
 */
function getTeams($search = '', $category = '') {
    global $db;
    
    $query = "SELECT t.*, c.category_name, u.full_name as leader_name 
              FROM teams t
              LEFT JOIN categories c ON t.category_id = c.category_id
              JOIN users u ON t.team_leader_id = u.user_id
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $query .= " AND (t.team_name LIKE ? OR t.description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ss';
    }
    
    if (!empty($category)) {
        $query .= " AND c.category_name = ?";
        $params[] = $category;
        $types .= 's';
    }
    
    $query .= " ORDER BY t.created_at DESC";
    
    return $db->fetchAll($query, $params, $types);
}

/**
 * Get all categories
 * 
 * @return array Array of categories
 */
function getCategories() {
    global $db;
    return $db->fetchAll("SELECT * FROM categories ORDER BY category_name ASC");
}

/**
 * Get single category by ID
 * @param int $id
 * @return array|null
 */
function getCategory($id) {
    global $db;
    return $db->fetchRow("SELECT * FROM categories WHERE category_id = ?", [$id]);
}


/**
 * Get all faculty members
 * 
 * @return array Array of faculty users
 */
function getFaculty() {
    global $db;
    // Assuming role_id 2 is Faculty as per seed data
    return $db->fetchAll("SELECT * FROM users WHERE role_id = 2 ORDER BY full_name ASC");
}

/**
 * Get all resources with optional filters
 * 
 * @param string $search Search query
 * @param string $category Category filter
 * @param string $type Type filter
 * @return array Array of resources
 */
function getResources($search = '', $category = '', $type = '') {
    global $db;
    
    $query = "SELECT r.*, c.category_name, u.full_name as uploader_name 
              FROM resources r
              LEFT JOIN categories c ON r.category_id = c.category_id
              JOIN users u ON r.uploaded_by = u.user_id
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $query .= " AND (r.title LIKE ? OR r.description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'ss';
    }
    
    if (!empty($category)) {
        if (is_numeric($category)) {
            $query .= " AND r.category_id = ?";
            $types .= 'i';
        } else {
            $query .= " AND c.category_name = ?";
            $types .= 's';
        }
        $params[] = $category;
    }
    
    if (!empty($type)) {
        $query .= " AND r.resource_type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    return $db->fetchAll($query, $params, $types);
}

/**
 * Upload a resource
 * 
 * @param string $title
 * @param string $description
 * @param int $categoryId
 * @param string $type
 * @param string $filePath
 * @param int $userId
 * @return boolean|int
 */
function uploadResource($title, $description, $categoryId, $type, $filePath, $userId) {
    global $db;
    
    return $db->insert('resources', [
        'title' => $title,
        'description' => $description,
        'category_id' => $categoryId,
        'resource_type' => $type,
        'file_path' => $filePath,
        'uploaded_by' => $userId
    ]);
}

// ============================================
// MESSAGING FUNCTIONS
// ============================================

/**
 * Get all conversations for a user
 * 
 * @param int $userId
 * @return array Array of conversations
 */
function getUserConversations($userId) {
    global $db;
    
    // This query fetches conversations and details about the OTHER participant
    $query = "
        SELECT 
            c.conversation_id, 
            c.last_message_at,
            CASE 
                WHEN c.participant1_id = ? THEN u2.full_name
                ELSE u1.full_name
            END as other_user_name,
            CASE 
                WHEN c.participant1_id = ? THEN u2.user_id
                ELSE u1.user_id
            END as other_user_id,
            (SELECT message_text FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.created_at DESC LIMIT 1) as last_message_text
        FROM conversations c
        LEFT JOIN users u1 ON c.participant1_id = u1.user_id
        LEFT JOIN users u2 ON c.participant2_id = u2.user_id
        WHERE c.participant1_id = ? OR c.participant2_id = ?
        ORDER BY c.last_message_at DESC
    ";
    
    return $db->fetchAll($query, [$userId, $userId, $userId, $userId], 'iiii');
}
// MESSAGING FUNCTIONS
// ============================================

/**
 * Get or create conversation between two users
 * 
 * @param int $userId1 First user ID
 * @param int $userId2 Second user ID
 * @param string $type Conversation type (User-User, User-Team, etc.)
 * @return int|null Conversation ID
 */
function getOrCreateConversation($userId1, $userId2, $type = 'User-User') {
    global $db;
    
    // Check if conversation exists
    $existingConversation = $db->fetchRow(
        "SELECT conversation_id FROM conversations 
         WHERE ((participant1_id = ? AND participant2_id = ?) 
                OR (participant1_id = ? AND participant2_id = ?))
         AND conversation_type = ?",
        [$userId1, $userId2, $userId2, $userId1, $type]
    );
    
    if ($existingConversation !== null) {
        return $existingConversation['conversation_id'];
    }
    
    // Create new conversation
    $conversationId = $db->insert('conversations', [
        'conversation_type' => $type,
        'participant1_id' => $userId1,
        'participant1_type' => 'User',
        'participant2_id' => $userId2,
        'participant2_type' => 'User'
    ]);
    
    return $conversationId;
}

/**
 * Send a message
 * 
 * @param int $conversationId Conversation ID
 * @param int $senderId Sender user ID
 * @param int $receiverId Receiver user ID
 * @param string $messageText Message text
 * @param string $messageType Message type
 * @return int|false Message ID or false
 */
function sendMessage($conversationId, $senderId, $receiverId, $messageText, $messageType = 'Text') {
    global $db;
    
    try {
        // Validate input
        if (empty($messageText)) {
            return false;
        }
        
        // Insert message
        $messageId = $db->insert('messages', [
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message_text' => $messageText,
            'message_type' => $messageType,
            'is_read' => false
        ]);
        
        // Update conversation last message time
        $db->update('conversations',
            ['last_message_at' => date('Y-m-d H:i:s')],
            'conversation_id = ?',
            [$conversationId]
        );
        
        // Create notification
        createNotification(
            $receiverId,
            'NEW_MESSAGE',
            'New message from user',
            'You have received a new message',
            null,
            $senderId,
            "messages.php?conv=$conversationId"
        );
        
        return $messageId;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get conversation messages
 * 
 * @param int $conversationId Conversation ID
 * @param int $limit Number of messages to fetch
 * @return array Array of messages
 */
function getConversationMessages($conversationId, $limit = 50) {
    global $db;
    
    return $db->fetchAll(
        "SELECT m.*, u.full_name as sender_name 
         FROM messages m
         JOIN users u ON m.sender_id = u.user_id
         WHERE m.conversation_id = ?
         ORDER BY m.created_at DESC
         LIMIT ?",
        [$conversationId, $limit]
    );
}

/**
 * Mark message as read
 * 
 * @param int $messageId Message ID
 * @return bool Success status
 */
function markMessageAsRead($messageId) {
    global $db;
    
    $result = $db->update('messages',
        ['is_read' => true, 'read_at' => date('Y-m-d H:i:s')],
        'message_id = ?',
        [$messageId]
    );
    
    return $result !== false;
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

/**
 * Create a notification
 * 
 * @param int $userId User ID
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param int|null $teamId Related team (optional)
 * @param int|null $relatedUserId Related user (optional)
 * @param string|null $actionUrl Action URL (optional)
 * @return int|false Notification ID or false
 */
function createNotification($userId, $type, $title, $message, $teamId = null, $relatedUserId = null, $actionUrl = null) {
    global $db;
    
    return $db->insert('notifications', [
        'user_id' => $userId,
        'notification_type' => $type,
        'title' => $title,
        'message' => $message,
        'related_team_id' => $teamId,
        'related_user_id' => $relatedUserId,
        'action_url' => $actionUrl,
        'is_read' => false
    ]);
}

/**
 * Get unread notifications
 * 
 * @param int $userId User ID
 * @return array Array of unread notifications
 */
function getUnreadNotifications($userId) {
    global $db;
    
    return $db->fetchAll(
        "SELECT * FROM notifications 
         WHERE user_id = ? AND is_read = FALSE
         ORDER BY created_at DESC",
        [$userId]
    );
}

// ============================================
// ACTIVITY LOGGING
// ============================================

/**
 * Log user or team activity
 * 
 * @param int|null $userId User ID (optional)
 * @param int|null $teamId Team ID (optional)
 * @param string $activityType Activity type
 * @param string $description Activity description
 * @return int|false Log ID or false
 */
function logActivity($userId = null, $teamId = null, $activityType = '', $description = '') {
    global $db;
    
    return $db->insert('activity_logs', [
        'user_id' => $userId,
        'team_id' => $teamId,
        'activity_type' => $activityType,
        'activity_description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// ============================================
// EXAMPLE USAGE
// ============================================

/*
// Example 1: Register a user
$response = registerUser('John Doe', 'john@example.com', 'password123', 1);
if ($response['success']) {
    echo "User registered with ID: " . $response['user_id'];
} else {
    echo "Error: " . $response['message'];
}

// Example 2: Create a team
$response = createTeam('AI Team', 'Working on AI...', 1, 1, 5, 1);
if ($response['success']) {
    echo "Team created with ID: " . $response['team_id'];
}

// Example 3: Send a message
$conversationId = getOrCreateConversation(1, 2);
$messageId = sendMessage($conversationId, 1, 2, 'Hello there!', 'Text');

// Example 4: Get team members
$members = getTeamMembers(1);
foreach ($members as $member) {
    echo $member['full_name'] . ' - ' . $member['member_role'];
}
*/

?>
