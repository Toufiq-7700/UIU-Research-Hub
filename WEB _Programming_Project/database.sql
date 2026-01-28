-- Database Schema for UIU Research Hub
-- Generated based on PHP codebase analysis

CREATE DATABASE IF NOT EXISTS uiu_research_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uiu_research_hub;

-- ============================================
-- 1. USERS & AUTHENTICATION
-- ============================================

-- Roles Table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT DEFAULT 1,
    phone VARCHAR(20),
    department VARCHAR(100),
    bio TEXT,
    skills TEXT,
    profile_picture VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 2. RESEARCH & TEAMS
-- ============================================

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon_class VARCHAR(50) DEFAULT 'fas fa-layer-group',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_categories_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Events Table (Optional, for Hackathons/Research phases)
CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status VARCHAR(50) DEFAULT 'Upcoming'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Teams Table
CREATE TABLE IF NOT EXISTS teams (
    team_id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    event_id INT,
    team_leader_id INT NOT NULL,
    max_members INT DEFAULT 5,
    current_members INT DEFAULT 1,
    status VARCHAR(50) DEFAULT 'Recruiting', -- Recruiting, Full, Inactive
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id),
    FOREIGN KEY (team_leader_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Team Members Table
CREATE TABLE IF NOT EXISTS team_members (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    member_role VARCHAR(50) DEFAULT 'Member', -- Leader, Researcher, etc.
    status VARCHAR(50) DEFAULT 'Active',
    contribution_score INT DEFAULT 0,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_membership (team_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Join Requests Table
CREATE TABLE IF NOT EXISTS join_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Pending', -- Pending, Accepted, Rejected
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    responded_by INT NULL,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE KEY uniq_join_request (team_id, user_id),
    INDEX idx_join_requests_team_status (team_id, status),
    INDEX idx_join_requests_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 3. COMMUNICATION
-- ============================================

-- Conversations Table
CREATE TABLE IF NOT EXISTS conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_type VARCHAR(50) DEFAULT 'User-User', -- User-User, Group
    participant1_id INT, -- For 1-on-1 chats
    participant1_type VARCHAR(20) DEFAULT 'User',
    participant2_id INT, -- For 1-on-1 chats
    participant2_type VARCHAR(20) DEFAULT 'User',
    last_message_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages Table
CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT, -- Optional if broadcasting to group
    message_text TEXT NOT NULL,
    message_type VARCHAR(20) DEFAULT 'Text', -- Text, File, Image
    is_read BOOLEAN DEFAULT FALSE,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    related_team_id INT,
    related_user_id INT,
    action_url VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 4. RESOURCES & LOGS
-- ============================================

-- Activity Logs
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    team_id INT,
    activity_type VARCHAR(50) NOT NULL,
    activity_description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Resources Table
CREATE TABLE IF NOT EXISTS resources (
    resource_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    resource_type VARCHAR(50) NOT NULL, -- Paper, Dataset, Tool
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_resources_uploaded_by (uploaded_by),
    INDEX idx_resources_created_at (created_at),
    INDEX idx_resources_category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- 5. VIEWS
-- ============================================

-- View: Team Full Profile
CREATE OR REPLACE VIEW team_full_profile AS
SELECT 
    t.team_id,
    t.team_name,
    t.description,
    t.status,
    t.current_members,
    t.max_members,
    c.category_name,
    e.event_name,
    u.full_name AS leader_name,
    u.email AS leader_email
FROM teams t
LEFT JOIN categories c ON t.category_id = c.category_id
LEFT JOIN events e ON t.event_id = e.event_id
JOIN users u ON t.team_leader_id = u.user_id;

-- ============================================
-- 6. SEED DATA
-- ============================================

-- Insert Roles
INSERT INTO roles (role_id, role_name) VALUES 
(1, 'Student'),
(2, 'Faculty'),
(3, 'Admin')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

-- Insert Categories
INSERT INTO categories (category_name, icon_class) VALUES 
('Artificial Intelligence', 'fas fa-brain'),
('Natural Language Processing', 'fas fa-language'),
('Robotics', 'fas fa-robot'),
('Cybersecurity', 'fas fa-shield-alt'),
('IoT', 'fas fa-wifi'),
('Machine Learning', 'fas fa-cogs'),
('Computer Vision', 'fas fa-eye'),
('Data Science', 'fas fa-database'),
('Software Engineering', 'fas fa-code'),
('Bioinformatics', 'fas fa-flask'),
('Cloud Computing', 'fas fa-network-wired'),
('Mobile Development', 'fas fa-mobile-alt')
ON DUPLICATE KEY UPDATE icon_class = VALUES(icon_class);

-- Insert Demo Admin (Optional)
-- Password is 'password123' (hashed)
INSERT INTO users (full_name, email, password_hash, role_id) VALUES 
('System Admin', 'admin@uiu.ac.bd', '$2y$12$J9.5.l.5.l.5.l.5.l.5.ue5.5.5.5.5.5.5.5.5.5.5.5', 3);
