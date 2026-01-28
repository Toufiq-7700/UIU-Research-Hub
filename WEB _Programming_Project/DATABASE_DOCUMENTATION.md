# UIU Research Hub - Database Documentation

## Overview
Complete MySQL database schema for the UIU Research Hub project, featuring user management, team collaboration, messaging systems, and research event management.

## Installation Instructions

### Step 1: Import Database via phpMyAdmin

1. **Open phpMyAdmin**
   - Go to `http://localhost/phpmyadmin/`
   - Login with your XAMPP credentials (default: root, no password)

2. **Import SQL File**
   - Click on "Import" tab in the top menu
   - Click "Choose File" and select `database.sql` from the project directory
   - Click "Go" to execute the import
   - You should see success message: "Import has been successfully finished"

### Step 2: Verify Database Creation

- In phpMyAdmin left sidebar, you should see `uiu_research_hub` database
- Click on it to verify all tables are created (16 main tables + 3 views)

### Step 3: Configure Database Connection

1. Open `db_connect.php` in the project
2. Update database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');     // MySQL server host
   define('DB_USER', 'root');          // MySQL username
   define('DB_PASS', '');              // MySQL password
   define('DB_NAME', 'uiu_research_hub'); // Database name
   define('DB_PORT', 3306);            // MySQL port
   ```

3. For production, change:
   ```php
   define('ENVIRONMENT', 'production');
   ```

## Database Schema

### Core Tables

#### 1. **users** - User Account Management
Stores all user information and authentication data.
- `user_id` (PK): Unique user identifier
- `full_name`: User's full name
- `email` (UNIQUE): User email address
- `password_hash`: Bcrypt hashed password
- `role_id` (FK): Reference to roles table
- `phone`: Contact phone number
- `bio`: User biography
- `profile_picture`: Path to profile image
- `department`: Academic department
- `university`: University name
- `is_active`: Account status
- `email_verified`: Email verification status
- `last_login`: Timestamp of last login
- `created_at`, `updated_at`: Timestamps

**Indexes:**
- `idx_email` (email)
- `idx_role` (role_id)
- `idx_created` (created_at)

---

#### 2. **roles** - User Role Types
Defines different roles in the system.
- `role_id` (PK): Role identifier
- `role_name` (UNIQUE): Role name (Student, Faculty, etc.)
- `description`: Role description
- `created_at`: Creation timestamp

**Available Roles:**
- Student
- Faculty
- Researcher
- Admin

---

#### 3. **categories** - Research Categories
Defines research field categories.
- `category_id` (PK): Category identifier
- `category_name` (UNIQUE): Category name
- `icon`: Font Awesome icon class
- `color`: Color hex code for UI
- `description`: Category description
- `created_at`: Creation timestamp

**Available Categories:**
- Artificial Intelligence
- Natural Language Processing
- Robotics
- Cybersecurity
- IoT
- Machine Learning
- Computer Vision
- Data Science
- Software Engineering
- Bioinformatics
- Cloud Computing
- Mobile Development

---

#### 4. **skills** - Technical Skills
Defines available technical skills.
- `skill_id` (PK): Skill identifier
- `skill_name` (UNIQUE): Skill name
- `category`: Skill category
- `description`: Skill description
- `created_at`: Creation timestamp

---

#### 5. **user_skills** - User Skill Mapping (Many-to-Many)
Links users to their skills.
- `user_skill_id` (PK): Record identifier
- `user_id` (FK): Reference to users
- `skill_id` (FK): Reference to skills
- `proficiency_level` (Beginner, Intermediate, Advanced, Expert)
- `endorsements`: Skill endorsement count
- `added_at`: Timestamp

**Unique Constraint:** `(user_id, skill_id)`

---

#### 6. **events** - Research Events
Stores information about events.
- `event_id` (PK): Event identifier
- `event_name`: Event name
- `description`: Event description
- `start_date`: Event start date
- `end_date`: Event end date
- `location`: Event location
- `event_type` (Competition, Workshop, Hackathon, Conference, Research Initiative)
- `max_teams`: Maximum teams allowed
- `created_by` (FK): Reference to users (event creator)
- `is_active`: Active status
- `created_at`, `updated_at`: Timestamps

---

#### 7. **teams** - Research Teams
Stores team information.
- `team_id` (PK): Team identifier
- `team_name`: Team name
- `description`: Team description
- `category_id` (FK): Research category
- `event_id` (FK): Associated event
- `team_leader_id` (FK): Team leader user
- `max_members`: Maximum team size
- `current_members`: Current member count
- `status` (Recruiting, Active, On Hold, Completed)
- `team_image`: Team image path
- `resources`: Available resources
- `created_at`, `updated_at`: Timestamps

**Indexes:**
- `idx_category`, `idx_event`, `idx_leader`, `idx_status`, `idx_created`

---

#### 8. **team_goals** - Team Goals
Stores team goals and objectives.
- `goal_id` (PK): Goal identifier
- `team_id` (FK): Reference to teams
- `goal_description`: Goal description
- `goal_order`: Display order
- `completed`: Completion status
- `created_at`: Creation timestamp

---

#### 9. **team_required_skills** - Team Skill Requirements (Many-to-Many)
Links teams to required skills.
- `requirement_id` (PK): Record identifier
- `team_id` (FK): Reference to teams
- `skill_id` (FK): Reference to skills
- `is_mandatory`: Mandatory requirement flag
- `added_at`: Timestamp

**Unique Constraint:** `(team_id, skill_id)`

---

#### 10. **team_members** - Team Membership
Stores team member information.
- `member_id` (PK): Member record identifier
- `team_id` (FK): Reference to teams
- `user_id` (FK): Reference to users
- `member_role`: Role in team (Team Leader, AI Researcher, etc.)
- `join_date`: Join date
- `status` (Active, Inactive, Left)
- `contribution_score`: Member contribution score
- `created_at`: Creation timestamp

**Indexes:**
- `idx_team`, `idx_user`, `idx_status`

---

#### 11. **join_requests** - Team Join Requests
Stores requests to join teams.
- `request_id` (PK): Request identifier
- `team_id` (FK): Team being requested
- `user_id` (FK): User requesting
- `message`: Request message
- `status` (Pending, Approved, Rejected, Withdrawn)
- `reviewed_by` (FK): User who reviewed request
- `reviewed_at`: Review timestamp
- `created_at`: Request timestamp

**Unique Constraint:** `(team_id, user_id)`

---

#### 12. **conversations** - Message Conversations
Stores conversation metadata.
- `conversation_id` (PK): Conversation identifier
- `conversation_type` (User-User, User-Team, User-Leader)
- `participant1_id` (FK): First participant user
- `participant1_type` (User, Team)
- `participant2_id` (FK): Second participant user
- `participant2_type` (User, Team)
- `subject`: Conversation subject
- `last_message_at`: Last message timestamp
- `is_archived`: Archive status
- `created_at`, `updated_at`: Timestamps

---

#### 13. **messages** - Individual Messages
Stores individual messages.
- `message_id` (PK): Message identifier
- `conversation_id` (FK): Parent conversation
- `sender_id` (FK): Message sender
- `receiver_id` (FK): Message receiver
- `message_text`: Message content
- `message_type` (Text, File, Join Request, System)
- `file_path`: Path if file attachment
- `is_read`: Read status
- `read_at`: Read timestamp
- `created_at`, `updated_at`: Timestamps

**Indexes:**
- `idx_conversation`, `idx_sender`, `idx_receiver`, `idx_created`, `idx_unread`

---

#### 14. **ratings** - Team Ratings
Stores team ratings and reviews.
- `rating_id` (PK): Rating identifier
- `rated_team_id` (FK): Team being rated
- `rated_by_user_id` (FK): User giving rating
- `rating_score` (1-5): Rating score
- `review_text`: Review text
- `categories_rating` (JSON): Category-specific ratings
- `created_at`, `updated_at`: Timestamps

**Unique Constraint:** `(rated_team_id, rated_by_user_id)`

---

#### 15. **activity_logs** - Activity Audit Trail
Stores user and team activity logs.
- `log_id` (PK): Log identifier
- `user_id` (FK): Associated user
- `team_id` (FK): Associated team
- `activity_type`: Type of activity
- `activity_description`: Activity details
- `ip_address`: User IP address
- `user_agent`: Browser/client information
- `created_at`: Log timestamp

---

#### 16. **notifications** - User Notifications
Stores user notifications.
- `notification_id` (PK): Notification identifier
- `user_id` (FK): Target user
- `notification_type`: Type of notification
- `title`: Notification title
- `message`: Notification message
- `related_team_id` (FK): Related team (if applicable)
- `related_user_id` (FK): Related user (if applicable)
- `is_read`: Read status
- `action_url`: Action URL
- `created_at`: Creation timestamp

---

### Database Views

#### 1. **user_full_profile**
Provides complete user profile information with related data.
```sql
SELECT user_id, full_name, email, phone, bio, department, 
       role_name, team_count, skill_count
```

#### 2. **team_full_profile**
Provides complete team profile information.
```sql
SELECT team_id, team_name, description, status, category_name,
       event_name, team_leader_name, current_members, 
       average_rating, total_ratings
```

#### 3. **conversation_summary**
Provides conversation summary with unread message counts.
```sql
SELECT conversation_id, conversation_type, participant1_name,
       participant2_name, last_message_at, unread_count
```

---

## Key Relationships

### User-Centric
```
users (1) -------- (N) user_skills
              |--- (N) team_members
              |--- (N) messages (sender/receiver)
              |--- (N) ratings
              |--- (N) conversations
```

### Team-Centric
```
teams (1) -------- (N) team_members
       |--------- (N) team_goals
       |--------- (N) team_required_skills
       |--------- (N) join_requests
       |--------- (N) ratings
       |--------- (N) conversations
```

### Messaging Flow
```
conversations (1) ------ (N) messages
      |         |
      |---- participant1 (users)
      |---- participant2 (users)
```

---

## Database Procedures

### 1. **register_user()**
Creates a new user account.
```sql
CALL register_user('John Doe', 'john@example.com', 'hashed_pwd', 1);
```

### 2. **create_team()**
Creates a new team and adds team leader.
```sql
CALL create_team('AI Team', 'Description...', 1, 1, 1, 5);
```

### 3. **send_message()**
Sends a message and updates conversation.
```sql
CALL send_message(1, 1, 2, 'Message text...', 'Text');
```

---

## Security Features

### Password Security
- Bcrypt hashing with cost factor 12
- Salted hash generation
- Password verification function

### SQL Injection Prevention
- Prepared statements with parameterized queries
- Input sanitization functions
- Escape special characters

### Data Validation
- Email format validation
- Role-based access control
- User status verification

### Privacy
- Email verification system
- User activity logging
- Conversation archiving

---

## Usage Examples

### Using db_connect.php

#### 1. Fetch User by Email
```php
<?php
require_once 'db_connect.php';

$user = $db->fetchRow(
    "SELECT * FROM users WHERE email = ?",
    ['user@example.com']
);
?>
```

#### 2. Create a Team
```php
<?php
require_once 'db_connect.php';

$teamData = [
    'team_name' => 'AI Innovators',
    'description' => 'Working on AI...',
    'category_id' => 1,
    'event_id' => 1,
    'team_leader_id' => 5,
    'max_members' => 5,
    'status' => 'Recruiting'
];

$teamId = $db->insert('teams', $teamData);
?>
```

#### 3. Send a Message
```php
<?php
require_once 'db_connect.php';

$messageData = [
    'conversation_id' => 1,
    'sender_id' => 5,
    'receiver_id' => 10,
    'message_text' => 'Hello!',
    'message_type' => 'Text'
];

$messageId = $db->insert('messages', $messageData);
$db->update('conversations', 
    ['last_message_at' => date('Y-m-d H:i:s')],
    'conversation_id = ?',
    [1]
);
?>
```

#### 4. Get Team Profile
```php
<?php
require_once 'db_connect.php';

$team = $db->fetchRow(
    "SELECT * FROM team_full_profile WHERE team_id = ?",
    [1]
);
?>
```

#### 5. List Unread Messages
```php
<?php
require_once 'db_connect.php';

$messages = $db->fetchAll(
    "SELECT * FROM messages WHERE receiver_id = ? AND is_read = FALSE ORDER BY created_at DESC",
    [5]
);
?>
```

---

## Maintenance & Optimization

### Regular Backups
```bash
mysqldump -u root uiu_research_hub > backup.sql
```

### Database Optimization
```sql
OPTIMIZE TABLE users;
OPTIMIZE TABLE teams;
OPTIMIZE TABLE messages;
```

### Check Indexes
```sql
SHOW INDEX FROM users;
SHOW INDEX FROM teams;
SHOW INDEX FROM messages;
```

---

## Troubleshooting

### Connection Issues
1. Verify MySQL is running (XAMPP Control Panel)
2. Check credentials in `db_connect.php`
3. Verify database exists: `SHOW DATABASES;`

### Import Errors
1. Check file encoding (UTF-8 without BOM)
2. Verify XAMPP MySQL version (5.7+ recommended)
3. Check for syntax errors in SQL file

### Permission Issues
1. Ensure root user has database privileges
2. Check `mysql.user` table for permissions
3. Verify XAMPP security settings

---

## Best Practices

✅ Always use prepared statements  
✅ Hash passwords before storing  
✅ Validate user input on server-side  
✅ Log all important activities  
✅ Regular database backups  
✅ Monitor query performance  
✅ Keep passwords in environment variables  
✅ Use HTTPS in production  

---

## Support & Updates

For issues or updates, contact the development team.
Database Version: 1.0
Last Updated: January 2025
