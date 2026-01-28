<?php
/**
 * UIU Research Hub - Sample Database Integration
 * This file demonstrates how to integrate the database with the existing project
 * 
 * Copy and adapt these patterns for your application
 */

// Include database connection
require_once 'db-connect.php';

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
 * @param string $bio
 * @param string $profilePicPath (optional)
 * @return boolean
 */
function updateUserProfile($userId, $skills, $bio, $profilePicPath = null) {
    global $db;

    $skills = normalizeSkills($skills);
    $bio = normalizeBio($bio);

    $data = [
        'skills' => $skills,
        'bio' => $bio
    ];
    
    if ($profilePicPath) {
        $data['profile_picture'] = $profilePicPath;
    }
    
    return $db->update('users', $data, 'user_id = ?', [$userId]);
}

/**
 * Normalize skills input (comma-separated)
 *
 * @param string $skills
 * @return string
 */
function normalizeSkills($skills) {
    $skills = trim((string)$skills);
    if ($skills === '') {
        return '';
    }

    $parts = preg_split('/,/', $skills);
    if (!$parts) {
        return '';
    }

    $out = [];
    $seen = [];
    foreach ($parts as $part) {
        $tag = trim($part);
        if ($tag === '') {
            continue;
        }

        // Limit per-skill length
        if (mb_strlen($tag) > 50) {
            $tag = mb_substr($tag, 0, 50);
        }

        $key = mb_strtolower($tag);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $out[] = $tag;

        if (count($out) >= 30) {
            break;
        }
    }

    return implode(', ', $out);
}

/**
 * Normalize bio input
 *
 * @param string $bio
 * @return string
 */
function normalizeBio($bio) {
    $bio = trim((string)$bio);
    if ($bio === '') {
        return '';
    }

    // Limit bio length
    if (mb_strlen($bio) > 1000) {
        $bio = mb_substr($bio, 0, 1000);
    }

    return $bio;
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

        // Prevent session fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
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
// ROLE / ACCESS HELPERS
// ============================================

/**
 * Get user with role name (always from DB)
 *
 * @param int $userId
 * @return array|null
 */
function getUserWithRole($userId) {
    global $db;

    return $db->fetchRow(
        "SELECT u.*, r.role_name
         FROM users u
         LEFT JOIN roles r ON u.role_id = r.role_id
         WHERE u.user_id = ?",
        [$userId]
    );
}

/**
 * Get user's role name
 *
 * @param int $userId
 * @return string|null
 */
function getUserRoleName($userId) {
    $user = getUserWithRole($userId);
    if (!$user) {
        return null;
    }

    $roleName = trim((string)($user['role_name'] ?? ''));
    return $roleName === '' ? null : $roleName;
}

/**
 * Team creation is allowed only for Student role
 *
 * @param int $userId
 * @return bool
 */
function userCanCreateTeam($userId) {
    $roleName = getUserRoleName($userId);
    return $roleName !== null && strcasecmp($roleName, 'Student') === 0;
}

/**
 * Resource upload is allowed only for Student/Faculty roles
 *
 * @param int $userId
 * @return bool
 */
function userCanUploadResource($userId) {
    $roleName = getUserRoleName($userId);
    if ($roleName === null) {
        return false;
    }

    return strcasecmp($roleName, 'Student') === 0 || strcasecmp($roleName, 'Faculty') === 0;
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

        // Access control: only Students can create teams
        if (!userCanCreateTeam($leaderId)) {
            return ['success' => false, 'message' => 'Only students can create teams'];
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
 * Update team basic info (leader only)
 *
 * @param int $teamId
 * @param int $leaderUserId
 * @param string $teamName
 * @param string $description
 * @param int $categoryId
 * @param int $maxMembers
 * @param string $status Recruiting|Full|Inactive
 * @return array{success:bool,message:string,code?:string}
 */
function updateTeam($teamId, $leaderUserId, $teamName, $description, $categoryId, $maxMembers, $status) {
    global $db;

    $teamId = (int)$teamId;
    $leaderUserId = (int)$leaderUserId;
    $teamName = trim((string)$teamName);
    $description = trim((string)$description);
    $categoryId = (int)$categoryId;
    $maxMembers = (int)$maxMembers;
    $status = trim((string)$status);

    if ($teamId <= 0) {
        return ['success' => false, 'message' => 'Invalid team', 'code' => 'invalid'];
    }
    if ($teamName === '' || $description === '') {
        return ['success' => false, 'message' => 'Team name and description are required', 'code' => 'required'];
    }
    if ($maxMembers < 2 || $maxMembers > 50) {
        return ['success' => false, 'message' => 'Max members must be between 2 and 50', 'code' => 'max_members'];
    }

    $allowedStatus = ['Recruiting', 'Full', 'Inactive'];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'Recruiting';
    }

    try {
        $db->beginTransaction();

        $team = $db->fetchRow("SELECT * FROM teams WHERE team_id = ? FOR UPDATE", [$teamId], 'i');
        if (!$team) {
            $db->rollback();
            return ['success' => false, 'message' => 'Team not found', 'code' => 'not_found'];
        }
        if ((int)$team['team_leader_id'] !== $leaderUserId) {
            $db->rollback();
            return ['success' => false, 'message' => 'Only the team leader can update the team', 'code' => 'forbidden'];
        }

        $activeCount = (int)$db->fetchValue(
            "SELECT COUNT(*) FROM team_members WHERE team_id = ? AND status = 'Active'",
            [$teamId],
            'i'
        );
        if ($activeCount > $maxMembers) {
            $db->rollback();
            return ['success' => false, 'message' => 'Max members cannot be less than current active members', 'code' => 'too_small'];
        }

        // Keep status consistent with capacity unless explicitly set to Inactive
        if ($status !== 'Inactive') {
            $status = ($activeCount >= $maxMembers) ? 'Full' : 'Recruiting';
        }

        $ok = $db->update(
            'teams',
            [
                'team_name' => $teamName,
                'description' => $description,
                'category_id' => $categoryId > 0 ? $categoryId : null,
                'max_members' => $maxMembers,
                'status' => $status
            ],
            'team_id = ?',
            [$teamId]
        );

        if ($ok === false) {
            $db->rollback();
            return ['success' => false, 'message' => 'Failed to update team', 'code' => 'db'];
        }

        syncTeamCurrentMembers($teamId);
        $db->commit();

        return ['success' => true, 'message' => 'Team updated'];
    } catch (Throwable $e) {
        $db->rollback();
        return ['success' => false, 'message' => 'Failed to update team', 'code' => 'failed'];
    }
}

/**
 * Transfer team leadership to an active member (leader only)
 *
 * @param int $teamId
 * @param int $currentLeaderUserId
 * @param int $newLeaderUserId
 * @return array{success:bool,message:string,code?:string}
 */
function transferTeamLeadership($teamId, $currentLeaderUserId, $newLeaderUserId) {
    global $db;

    $teamId = (int)$teamId;
    $currentLeaderUserId = (int)$currentLeaderUserId;
    $newLeaderUserId = (int)$newLeaderUserId;

    if ($teamId <= 0 || $newLeaderUserId <= 0) {
        return ['success' => false, 'message' => 'Invalid request', 'code' => 'invalid'];
    }
    if ($newLeaderUserId === $currentLeaderUserId) {
        return ['success' => false, 'message' => 'New leader must be different', 'code' => 'same'];
    }

    try {
        $db->beginTransaction();

        $team = $db->fetchRow("SELECT * FROM teams WHERE team_id = ? FOR UPDATE", [$teamId], 'i');
        if (!$team) {
            $db->rollback();
            return ['success' => false, 'message' => 'Team not found', 'code' => 'not_found'];
        }
        if ((int)$team['team_leader_id'] !== $currentLeaderUserId) {
            $db->rollback();
            return ['success' => false, 'message' => 'Only the team leader can transfer leadership', 'code' => 'forbidden'];
        }

        $isMember = (int)$db->fetchValue(
            "SELECT COUNT(*) FROM team_members WHERE team_id = ? AND user_id = ? AND status = 'Active'",
            [$teamId, $newLeaderUserId],
            'ii'
        );
        if ($isMember <= 0) {
            $db->rollback();
            return ['success' => false, 'message' => 'New leader must be an active team member', 'code' => 'not_member'];
        }

        $ok = $db->update(
            'teams',
            ['team_leader_id' => $newLeaderUserId],
            'team_id = ?',
            [$teamId]
        );
        if ($ok === false) {
            $db->rollback();
            return ['success' => false, 'message' => 'Failed to transfer leadership', 'code' => 'db'];
        }

        // Update roles in team_members for display consistency
        $db->update(
            'team_members',
            ['member_role' => 'Member'],
            'team_id = ? AND user_id = ?',
            [$teamId, $currentLeaderUserId]
        );
        $db->update(
            'team_members',
            ['member_role' => 'Team Leader', 'status' => 'Active'],
            'team_id = ? AND user_id = ?',
            [$teamId, $newLeaderUserId]
        );

        $db->commit();
        return ['success' => true, 'message' => 'Leadership transferred'];
    } catch (Throwable $e) {
        $db->rollback();
        return ['success' => false, 'message' => 'Failed to transfer leadership', 'code' => 'failed'];
    }
}

/**
 * Delete team (leader only)
 *
 * @param int $teamId
 * @param int $leaderUserId
 * @return array{success:bool,message:string,code?:string}
 */
function deleteTeam($teamId, $leaderUserId) {
    global $db;

    $teamId = (int)$teamId;
    $leaderUserId = (int)$leaderUserId;

    if ($teamId <= 0) {
        return ['success' => false, 'message' => 'Invalid team', 'code' => 'invalid'];
    }

    try {
        $db->beginTransaction();

        $team = $db->fetchRow("SELECT * FROM teams WHERE team_id = ? FOR UPDATE", [$teamId], 'i');
        if (!$team) {
            $db->rollback();
            return ['success' => false, 'message' => 'Team not found', 'code' => 'not_found'];
        }
        if ((int)$team['team_leader_id'] !== $leaderUserId) {
            $db->rollback();
            return ['success' => false, 'message' => 'Only the team leader can delete the team', 'code' => 'forbidden'];
        }

        $deleted = $db->delete('teams', 'team_id = ?', [$teamId]);
        if ($deleted === false || (int)$deleted <= 0) {
            $db->rollback();
            return ['success' => false, 'message' => 'Failed to delete team', 'code' => 'db'];
        }

        logActivity($leaderUserId, $teamId, 'TEAM_DELETED', 'Team deleted: ' . (string)($team['team_name'] ?? ''));
        $db->commit();

        return ['success' => true, 'message' => 'Team deleted'];
    } catch (Throwable $e) {
        $db->rollback();
        return ['success' => false, 'message' => 'Failed to delete team', 'code' => 'failed'];
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
        "SELECT 
            t.*,
            c.category_name,
            e.event_name,
            u.user_id AS team_leader_id,
            u.full_name AS leader_name,
            u.email AS leader_email
         FROM teams t
         LEFT JOIN categories c ON t.category_id = c.category_id
         LEFT JOIN events e ON t.event_id = e.event_id
         JOIN users u ON t.team_leader_id = u.user_id
         WHERE t.team_id = ?",
        [$teamId],
        'i'
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

// ============================================
// TEAM JOIN REQUESTS
// ============================================

/**
 * Ensure CSRF token exists and return it
 *
 * @return string
 */
function ensureCsrfToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token = $_SESSION['csrf_token'] ?? null;
    if (!is_string($token) || $token === '') {
        $bytes = null;

        try {
            $bytes = random_bytes(32);
        } catch (Throwable $e) {
            // Fallback: should not happen on modern PHP builds.
            if (function_exists('openssl_random_pseudo_bytes')) {
                $bytes = openssl_random_pseudo_bytes(32);
            }
        }

        if (!is_string($bytes) || strlen($bytes) < 32) {
            // Last-resort fallback (non-cryptographic). Prefer fixing the PHP environment instead.
            $bytes = hash('sha256', (string)microtime(true) . (string)mt_rand() . (string)getmypid(), true);
        }

        $_SESSION['csrf_token'] = bin2hex($bytes);
    }

    return (string)$_SESSION['csrf_token'];
}

/**
 * Team join requests are allowed only for Student role
 *
 * @param int $userId
 * @return bool
 */
function userCanRequestToJoinTeam($userId) {
    return userCanCreateTeam($userId);
}

/**
 * Get active team IDs for a user
 *
 * @param int $userId
 * @return array<int>
 */
function getUserActiveTeamIds($userId) {
    global $db;

    $rows = $db->fetchAll(
        "SELECT team_id FROM team_members WHERE user_id = ? AND status = 'Active'",
        [(int)$userId],
        'i'
    );

    return array_map(fn($r) => (int)$r['team_id'], $rows);
}

/**
 * Get join request (if any) for a user/team
 *
 * @param int $teamId
 * @param int $userId
 * @return array|null
 */
function getJoinRequest($teamId, $userId) {
    global $db;

    return $db->fetchRow(
        "SELECT * FROM join_requests WHERE team_id = ? AND user_id = ?",
        [(int)$teamId, (int)$userId],
        'ii'
    );
}

/**
 * Get join request status map for user across teams
 *
 * @param int $userId
 * @param array<int> $teamIds
 * @return array<int,string> team_id => status
 */
function getJoinRequestStatusMapForUser($userId, $teamIds) {
    global $db;

    $teamIds = array_values(array_filter(array_map('intval', (array)$teamIds), fn($v) => $v > 0));
    if (empty($teamIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
    $params = array_merge([(int)$userId], $teamIds);
    $types = 'i' . str_repeat('i', count($teamIds));

    $rows = $db->fetchAll(
        "SELECT team_id, status
         FROM join_requests
         WHERE user_id = ? AND team_id IN ($placeholders)",
        $params,
        $types
    );

    $out = [];
    foreach ($rows as $r) {
        $out[(int)$r['team_id']] = (string)$r['status'];
    }

    return $out;
}

/**
 * Get join requests for a team (leader view)
 *
 * @param int $teamId
 * @param bool $onlyPending
 * @return array
 */
function getJoinRequestsForTeam($teamId, $onlyPending = true) {
    global $db;

    $query = "
        SELECT jr.*, u.full_name, u.email
        FROM join_requests jr
        JOIN users u ON jr.user_id = u.user_id
        WHERE jr.team_id = ?
    ";

    $params = [(int)$teamId];
    $types = 'i';

    if ($onlyPending) {
        $query .= " AND jr.status = 'Pending'";
    }

    $query .= " ORDER BY jr.requested_at DESC";

    return $db->fetchAll($query, $params, $types);
}

/**
 * Get pending join requests for teams led by the given leader
 *
 * @param int $leaderUserId
 * @return array
 */
function getPendingJoinRequestsForLeader($leaderUserId) {
    global $db;

    if (!dbTableExists('join_requests')) {
        return [];
    }

    return $db->fetchAll(
        "SELECT 
            jr.request_id,
            jr.team_id,
            jr.user_id,
            jr.status,
            jr.requested_at,
            t.team_name,
            t.max_members,
            t.current_members,
            u.full_name AS student_name,
            u.email AS student_email
         FROM join_requests jr
         JOIN teams t ON jr.team_id = t.team_id
         JOIN users u ON jr.user_id = u.user_id
         WHERE t.team_leader_id = ? AND jr.status = 'Pending'
         ORDER BY jr.requested_at DESC",
        [(int)$leaderUserId],
        'i'
    );
}

/**
 * Sync teams.current_members to actual active team_members count
 *
 * @param int $teamId
 * @return int Updated count
 */
function syncTeamCurrentMembers($teamId) {
    global $db;

    $count = $db->fetchValue(
        "SELECT COUNT(*) FROM team_members WHERE team_id = ? AND status = 'Active'",
        [(int)$teamId],
        'i'
    );
    $count = (int)$count;

    $db->update('teams', ['current_members' => $count], 'team_id = ?', [(int)$teamId]);
    return $count;
}

/**
 * Create or re-open a join request (student only)
 *
 * @param int $teamId
 * @param int $userId
 * @return array{success:bool,message:string,status?:string}
 */
function createJoinRequest($teamId, $userId) {
    global $db;

    $teamId = (int)$teamId;
    $userId = (int)$userId;

    if (!userCanRequestToJoinTeam($userId)) {
        return ['success' => false, 'message' => 'Only students can request to join teams'];
    }

    if (!dbTableExists('join_requests')) {
        return ['success' => false, 'message' => 'Join requests are not available yet (run dummy/migrate_db_v4.php)', 'status' => 'Unavailable'];
    }

    $team = $db->fetchRow("SELECT * FROM teams WHERE team_id = ?", [$teamId], 'i');
    if (!$team) {
        return ['success' => false, 'message' => 'Team not found'];
    }

    if ($db->exists('team_members', 'team_id = ? AND user_id = ? AND status = ?', [$teamId, $userId, 'Active'])) {
        return ['success' => false, 'message' => 'You are already a team member', 'status' => 'Member'];
    }

    // Check capacity using real count (not just cached current_members)
    $activeCount = (int)$db->fetchValue(
        "SELECT COUNT(*) FROM team_members WHERE team_id = ? AND status = 'Active'",
        [$teamId],
        'i'
    );
    if ($activeCount >= (int)$team['max_members']) {
        return ['success' => false, 'message' => 'Team is full', 'status' => 'Full'];
    }

    $existing = getJoinRequest($teamId, $userId);
    if ($existing) {
        $status = (string)$existing['status'];
        if ($status === 'Pending') {
            return ['success' => false, 'message' => 'Join request already pending', 'status' => 'Pending'];
        }
        if ($status === 'Accepted') {
            return ['success' => false, 'message' => 'Join request already accepted', 'status' => 'Accepted'];
        }

        // Re-open rejected request
        $db->update(
            'join_requests',
            ['status' => 'Pending', 'requested_at' => date('Y-m-d H:i:s'), 'responded_at' => null, 'responded_by' => null],
            'request_id = ?',
            [(int)$existing['request_id']]
        );

        return ['success' => true, 'message' => 'Join request sent', 'status' => 'Pending'];
    }

    $insertId = $db->insert('join_requests', [
        'team_id' => $teamId,
        'user_id' => $userId,
        'status' => 'Pending'
    ]);

    if ($insertId === false) {
        return ['success' => false, 'message' => 'Failed to create join request'];
    }

    return ['success' => true, 'message' => 'Join request sent', 'status' => 'Pending'];
}

/**
 * Accept or reject a join request (leader only)
 *
 * @param int $requestId
 * @param int $leaderUserId
 * @param string $action Accept|Reject
 * @return array{success:bool,message:string}
 */
function handleJoinRequest($requestId, $leaderUserId, $action) {
    global $db;

    $requestId = (int)$requestId;
    $leaderUserId = (int)$leaderUserId;
    $action = $action === 'Accept' ? 'Accept' : ($action === 'Reject' ? 'Reject' : '');
    if ($action === '') {
        return ['success' => false, 'message' => 'Invalid action'];
    }

    if (!dbTableExists('join_requests')) {
        return ['success' => false, 'message' => 'Join requests are not available yet (run dummy/migrate_db_v4.php)', 'code' => 'unavailable'];
    }

    try {
        $db->beginTransaction();

        $req = $db->fetchRow(
            "SELECT * FROM join_requests WHERE request_id = ? FOR UPDATE",
            [$requestId],
            'i'
        );
        if (!$req) {
            $db->rollback();
            return ['success' => false, 'message' => 'Request not found'];
        }
        if ((string)$req['status'] !== 'Pending') {
            $db->rollback();
            return ['success' => false, 'message' => 'Request is not pending'];
        }

        $team = $db->fetchRow(
            "SELECT * FROM teams WHERE team_id = ? FOR UPDATE",
            [(int)$req['team_id']],
            'i'
        );
        if (!$team) {
            $db->rollback();
            return ['success' => false, 'message' => 'Team not found'];
        }
        if ((int)$team['team_leader_id'] !== $leaderUserId) {
            $db->rollback();
            return ['success' => false, 'message' => 'Only the team leader can manage requests'];
        }

        if ($action === 'Reject') {
            $db->update('join_requests',
                ['status' => 'Rejected', 'responded_at' => date('Y-m-d H:i:s'), 'responded_by' => $leaderUserId],
                'request_id = ?',
                [$requestId]
            );
            $db->commit();
            return ['success' => true, 'message' => 'Request rejected'];
        }

        // Accept: ensure not full
        $activeCount = (int)$db->fetchValue(
            "SELECT COUNT(*) FROM team_members WHERE team_id = ? AND status = 'Active'",
            [(int)$team['team_id']],
            'i'
        );
        if ($activeCount >= (int)$team['max_members']) {
            $db->rollback();
            return ['success' => false, 'message' => 'Team is full'];
        }

        // Add member (ignore if already exists)
        $db->executeQuery(
            "INSERT INTO team_members (team_id, user_id, member_role, status) VALUES (?, ?, 'Member', 'Active')
             ON DUPLICATE KEY UPDATE status = 'Active'",
            [(int)$team['team_id'], (int)$req['user_id']],
            'ii'
        );

        $db->update('join_requests',
            ['status' => 'Accepted', 'responded_at' => date('Y-m-d H:i:s'), 'responded_by' => $leaderUserId],
            'request_id = ?',
            [$requestId]
        );

        syncTeamCurrentMembers((int)$team['team_id']);

        $db->commit();
        return ['success' => true, 'message' => 'Request accepted'];

    } catch (Throwable $e) {
        $db->rollback();
        return ['success' => false, 'message' => 'Failed to process request'];
    }
}

/**
 * Remove a member from a team (leader only)
 *
 * @param int $teamId
 * @param int $memberUserId
 * @param int $leaderUserId
 * @return array{success:bool,message:string}
 */
function removeTeamMember($teamId, $memberUserId, $leaderUserId) {
    global $db;

    $teamId = (int)$teamId;
    $memberUserId = (int)$memberUserId;
    $leaderUserId = (int)$leaderUserId;

    try {
        $db->beginTransaction();

        $team = $db->fetchRow("SELECT * FROM teams WHERE team_id = ? FOR UPDATE", [$teamId], 'i');
        if (!$team) {
            $db->rollback();
            return ['success' => false, 'message' => 'Team not found'];
        }
        if ((int)$team['team_leader_id'] !== $leaderUserId) {
            $db->rollback();
            return ['success' => false, 'message' => 'Only the team leader can remove members'];
        }
        if ($memberUserId === (int)$team['team_leader_id']) {
            $db->rollback();
            return ['success' => false, 'message' => 'You cannot remove the team leader'];
        }

        $affected = $db->update(
            'team_members',
            ['status' => 'Removed'],
            'team_id = ? AND user_id = ? AND status = ?',
            [$teamId, $memberUserId, 'Active']
        );

        if ($affected === false || (int)$affected === 0) {
            $db->rollback();
            return ['success' => false, 'message' => 'Member not found'];
        }

        syncTeamCurrentMembers($teamId);
        $db->commit();

        return ['success' => true, 'message' => 'Member removed'];

    } catch (Throwable $e) {
        $db->rollback();
        return ['success' => false, 'message' => 'Failed to remove member'];
    }
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
        if (is_numeric($category)) {
            $query .= " AND t.category_id = ?";
            $params[] = (int)$category;
            $types .= 'i';
        } else {
            $query .= " AND c.category_name = ?";
            $params[] = (string)$category;
            $types .= 's';
        }
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

    if (dbColumnExists('categories', 'created_by')) {
        return $db->fetchAll(
            "SELECT c.*, u.full_name AS created_by_name
             FROM categories c
             LEFT JOIN users u ON c.created_by = u.user_id
             ORDER BY c.category_name ASC"
        );
    }

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
 * Create a new category (Faculty only)
 *
 * @param int $userId
 * @param string $name
 * @param string $description
 * @param string $iconClass
 * @return array{success:bool,message:string,code?:string}
 */
function createCategory($userId, $name, $description = '', $iconClass = '') {
    global $db;

    $userId = (int)$userId;
    $name = trim((string)$name);
    $description = trim((string)$description);
    $iconClass = trim((string)$iconClass);

    if (strcasecmp((string)getUserRoleName($userId), 'Faculty') !== 0) {
        return ['success' => false, 'message' => 'Only faculty members can add categories', 'code' => 'forbidden'];
    }

    if ($name === '') {
        return ['success' => false, 'message' => 'Category name is required', 'code' => 'name_required'];
    }

    if (mb_strlen($name) > 100) {
        return ['success' => false, 'message' => 'Category name is too long', 'code' => 'name_length'];
    }

    if ($description !== '' && mb_strlen($description) > 250) {
        return ['success' => false, 'message' => 'Description must be 250 characters or less', 'code' => 'desc_length'];
    }

    if ($iconClass === '') {
        $iconClass = 'fas fa-layer-group';
    }
    if (mb_strlen($iconClass) > 50) {
        return ['success' => false, 'message' => 'Icon class is too long', 'code' => 'icon_length'];
    }

    // Ensure schema supports ownership metadata
    if (!dbColumnExists('categories', 'created_by') || !dbColumnExists('categories', 'created_at')) {
        return ['success' => false, 'message' => 'Category creation is not available yet (run dummy/migrate_db_v5.php)', 'code' => 'missing_columns'];
    }

    // Duplicate check (case-insensitive)
    $dup = $db->fetchValue(
        "SELECT COUNT(*) FROM categories WHERE LOWER(category_name) = LOWER(?)",
        [$name],
        's'
    );
    if ((int)$dup > 0) {
        return ['success' => false, 'message' => 'Category name already exists', 'code' => 'duplicate'];
    }

    $insertId = $db->insert('categories', [
        'category_name' => $name,
        'description' => $description === '' ? null : $description,
        'icon_class' => $iconClass,
        'created_by' => $userId
    ]);

    if ($insertId === false) {
        return ['success' => false, 'message' => 'Failed to add category', 'code' => 'db'];
    }

    return ['success' => true, 'message' => 'Category added successfully'];
}


/**
 * Get all faculty members
 * 
 * @return array Array of faculty users
 */
function getFaculty() {
    return getFacultyMembers();
}

/**
 * Update a category (Faculty only)
 *
 * @param int $userId
 * @param int $categoryId
 * @param string $name
 * @param string $description
 * @param string $iconClass
 * @return array{success:bool,message:string,code?:string}
 */
function updateCategory($userId, $categoryId, $name, $description = '', $iconClass = '') {
    global $db;

    $userId = (int)$userId;
    $categoryId = (int)$categoryId;
    $name = trim((string)$name);
    $description = trim((string)$description);
    $iconClass = trim((string)$iconClass);

    if (strcasecmp((string)getUserRoleName($userId), 'Faculty') !== 0) {
        return ['success' => false, 'message' => 'Only faculty members can manage categories', 'code' => 'forbidden'];
    }
    if ($categoryId <= 0) {
        return ['success' => false, 'message' => 'Invalid category', 'code' => 'invalid'];
    }
    if ($name === '') {
        return ['success' => false, 'message' => 'Category name is required', 'code' => 'name_required'];
    }
    if (mb_strlen($name) > 100) {
        return ['success' => false, 'message' => 'Category name is too long', 'code' => 'name_length'];
    }
    if ($description !== '' && mb_strlen($description) > 250) {
        return ['success' => false, 'message' => 'Description must be 250 characters or less', 'code' => 'desc_length'];
    }
    if ($iconClass === '') {
        $iconClass = 'fas fa-layer-group';
    }
    if (mb_strlen($iconClass) > 50) {
        return ['success' => false, 'message' => 'Icon class is too long', 'code' => 'icon_length'];
    }

    $exists = $db->fetchValue("SELECT COUNT(*) FROM categories WHERE category_id = ?", [$categoryId], 'i');
    if ((int)$exists <= 0) {
        return ['success' => false, 'message' => 'Category not found', 'code' => 'not_found'];
    }

    $dup = $db->fetchValue(
        "SELECT COUNT(*) FROM categories WHERE LOWER(category_name) = LOWER(?) AND category_id <> ?",
        [$name, $categoryId],
        'si'
    );
    if ((int)$dup > 0) {
        return ['success' => false, 'message' => 'Category name already exists', 'code' => 'duplicate'];
    }

    $ok = $db->update(
        'categories',
        [
            'category_name' => $name,
            'description' => $description === '' ? null : $description,
            'icon_class' => $iconClass
        ],
        'category_id = ?',
        [$categoryId]
    );

    if ($ok === false) {
        return ['success' => false, 'message' => 'Failed to update category', 'code' => 'db'];
    }

    return ['success' => true, 'message' => 'Category updated'];
}

/**
 * Delete a category (Faculty only). Refuses if used by teams/resources.
 *
 * @param int $userId
 * @param int $categoryId
 * @return array{success:bool,message:string,code?:string}
 */
function deleteCategory($userId, $categoryId) {
    global $db;

    $userId = (int)$userId;
    $categoryId = (int)$categoryId;

    if (strcasecmp((string)getUserRoleName($userId), 'Faculty') !== 0) {
        return ['success' => false, 'message' => 'Only faculty members can manage categories', 'code' => 'forbidden'];
    }
    if ($categoryId <= 0) {
        return ['success' => false, 'message' => 'Invalid category', 'code' => 'invalid'];
    }

    $exists = $db->fetchValue("SELECT COUNT(*) FROM categories WHERE category_id = ?", [$categoryId], 'i');
    if ((int)$exists <= 0) {
        return ['success' => false, 'message' => 'Category not found', 'code' => 'not_found'];
    }

    $teamCount = (int)$db->fetchValue("SELECT COUNT(*) FROM teams WHERE category_id = ?", [$categoryId], 'i');
    $resourceCount = (int)$db->fetchValue("SELECT COUNT(*) FROM resources WHERE category_id = ?", [$categoryId], 'i');
    if ($teamCount > 0 || $resourceCount > 0) {
        return ['success' => false, 'message' => 'Category is in use', 'code' => 'in_use'];
    }

    $deleted = $db->delete('categories', 'category_id = ?', [$categoryId]);
    if ($deleted === false || (int)$deleted <= 0) {
        return ['success' => false, 'message' => 'Failed to delete category', 'code' => 'db'];
    }

    return ['success' => true, 'message' => 'Category deleted'];
}

/**
 * Check if a column exists in the current database
 *
 * @param string $table
 * @param string $column
 * @return bool
 */
function dbColumnExists($table, $column) {
    global $db;

    $count = $db->fetchValue(
        "SELECT COUNT(*) 
         FROM information_schema.COLUMNS 
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $column],
        'ss'
    );

    return (int)$count > 0;
}

/**
 * Check if a table exists in the current database
 *
 * @param string $table
 * @return bool
 */
function dbTableExists($table) {
    global $db;

    $count = $db->fetchValue(
        "SELECT COUNT(*)
         FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
        [(string)$table],
        's'
    );

    return (int)$count > 0;
}

/**
 * Get faculty users from DB (role_name = Faculty)
 *
 * @param bool $onlyActive
 * @param bool $onlyVerified If email_verified column exists, filter by TRUE
 * @return array
 */
function getFacultyMembers($onlyActive = true, $onlyVerified = true) {
    global $db;

    $query = "
        SELECT u.user_id, u.full_name, u.email, u.department, u.bio, u.profile_picture, u.is_active, r.role_name
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE r.role_name = 'Faculty'
    ";

    if ($onlyActive) {
        $query .= " AND u.is_active = TRUE";
    }

    if ($onlyVerified && dbColumnExists('users', 'email_verified')) {
        $query .= " AND u.email_verified = TRUE";
    }

    $query .= " ORDER BY u.full_name ASC";

    return $db->fetchAll($query);
}

/**
 * Get all students (role_name = Student)
 *
 * @param string $search
 * @param bool $onlyActive
 * @return array
 */
function getStudents($search = '', $onlyActive = true) {
    global $db;

    $search = trim((string)$search);
    $query = "
        SELECT u.*, r.role_name AS role
        FROM users u
        JOIN roles r ON u.role_id = r.role_id
        WHERE r.role_name = 'Student'
    ";

    $params = [];
    $types = '';

    if ($onlyActive) {
        $query .= " AND u.is_active = TRUE";
    }

    if ($search !== '') {
        $query .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.department LIKE ?)";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like];
        $types = 'sss';
    }

    $query .= " ORDER BY u.full_name ASC";

    return $db->fetchAll($query, $params, $types);
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
    
    $query = "SELECT r.*, c.category_name, u.full_name as uploader_name, ro.role_name as uploader_role
              FROM resources r
              LEFT JOIN categories c ON r.category_id = c.category_id
              JOIN users u ON r.uploaded_by = u.user_id
              LEFT JOIN roles ro ON u.role_id = ro.role_id
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
 * Get resources uploaded by a specific user
 *
 * @param int $userId
 * @return array
 */
function getResourcesByUploader($userId) {
    global $db;

    return $db->fetchAll(
        "SELECT r.*, c.category_name
         FROM resources r
         LEFT JOIN categories c ON r.category_id = c.category_id
         WHERE r.uploaded_by = ?
         ORDER BY r.created_at DESC",
        [$userId],
        'i'
    );
}

/**
 * Get a single resource by ID
 *
 * @param int $resourceId
 * @return array|null
 */
function getResourceById($resourceId) {
    global $db;

    return $db->fetchRow(
        "SELECT * FROM resources WHERE resource_id = ?",
        [$resourceId],
        'i'
    );
}

/**
 * Delete a resource (DB record only) if requester is the uploader
 *
 * @param int $resourceId
 * @param int $requesterId
 * @return bool
 */
function deleteResource($resourceId, $requesterId) {
    global $db;

    $resource = getResourceById($resourceId);
    if (!$resource) {
        return false;
    }

    if ((int)$resource['uploaded_by'] !== (int)$requesterId) {
        return false;
    }

    $deleted = $db->delete('resources', 'resource_id = ?', [$resourceId]);
    return $deleted !== false && $deleted > 0;
}

/**
 * Update a resource metadata (uploader only)
 *
 * @param int $resourceId
 * @param int $requesterId
 * @param string $title
 * @param string $description
 * @param int $categoryId
 * @param string $type
 * @return array{success:bool,message:string,code?:string}
 */
function updateResource($resourceId, $requesterId, $title, $description, $categoryId, $type) {
    global $db;

    $resourceId = (int)$resourceId;
    $requesterId = (int)$requesterId;
    $title = trim((string)$title);
    $description = trim((string)$description);
    $categoryId = (int)$categoryId;
    $type = trim((string)$type);

    if ($resourceId <= 0) {
        return ['success' => false, 'message' => 'Invalid resource', 'code' => 'invalid'];
    }
    if ($title === '' || mb_strlen($title) > 255) {
        return ['success' => false, 'message' => 'Invalid title', 'code' => 'title'];
    }

    $allowedTypes = ['Paper', 'Dataset', 'Tool', 'Tutorial'];
    if (!in_array($type, $allowedTypes, true)) {
        return ['success' => false, 'message' => 'Invalid type', 'code' => 'type'];
    }

    $resource = getResourceById($resourceId);
    if (!$resource) {
        return ['success' => false, 'message' => 'Resource not found', 'code' => 'not_found'];
    }
    if ((int)$resource['uploaded_by'] !== $requesterId) {
        return ['success' => false, 'message' => 'Forbidden', 'code' => 'forbidden'];
    }

    $ok = $db->update(
        'resources',
        [
            'title' => $title,
            'description' => $description === '' ? null : $description,
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'resource_type' => $type
        ],
        'resource_id = ?',
        [$resourceId]
    );

    if ($ok === false) {
        return ['success' => false, 'message' => 'Failed to update resource', 'code' => 'db'];
    }

    return ['success' => true, 'message' => 'Resource updated'];
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

    if (!userCanUploadResource($userId)) {
        return false;
    }
    
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
            "messages.php?conversation_id=$conversationId"
        );
        
        return $messageId;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Soft-delete a message (sender only): replaces text with [deleted]
 *
 * @param int $messageId
 * @param int $requesterId
 * @return bool
 */
function deleteMessage($messageId, $requesterId) {
    global $db;

    $messageId = (int)$messageId;
    $requesterId = (int)$requesterId;
    if ($messageId <= 0 || $requesterId <= 0) {
        return false;
    }

    $msg = $db->fetchRow("SELECT message_id, sender_id FROM messages WHERE message_id = ?", [$messageId], 'i');
    if (!$msg) {
        return false;
    }
    if ((int)$msg['sender_id'] !== $requesterId) {
        return false;
    }

    $ok = $db->update(
        'messages',
        ['message_text' => '[deleted]', 'message_type' => 'Deleted'],
        'message_id = ?',
        [$messageId]
    );

    return $ok !== false;
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
