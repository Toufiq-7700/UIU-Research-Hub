# UIU Research Hub - Quick Database Setup Guide

## 📋 Files Created

1. **database.sql** - Complete MySQL database schema
2. **db_connect.php** - Secure PHP database connection class
3. **DATABASE_DOCUMENTATION.md** - Comprehensive documentation

---

## ⚡ Quick Setup (5 Minutes)

### Step 1: Open phpMyAdmin
```
http://localhost/phpmyadmin/
```

### Step 2: Import Database
1. Click **"Import"** tab
2. Click **"Choose File"** 
3. Select **`database.sql`** from your project folder
4. Click **"Go"**
5. Wait for success message ✅

### Step 3: Verify Installation
In phpMyAdmin left sidebar, you should see:
- **Database:** `uiu_research_hub`
- **Tables:** 16 core tables + 3 views
- **Rows:** Pre-populated categories, roles, skills, events

### Step 4: Test Connection
Create a test PHP file:
```php
<?php
require_once 'db_connect.php';

// Test connection
$result = $db->fetchAll("SELECT * FROM categories LIMIT 5");
echo "✓ Connected! Found " . count($result) . " categories";
?>
```

---

## 📊 Database Structure Overview

### 16 Tables
```
1. users              - User accounts & authentication
2. roles             - User role definitions
3. categories        - Research field categories
4. skills            - Technical skills library
5. user_skills       - User-Skill relationships
6. events            - Research events/competitions
7. teams             - Research teams
8. team_goals        - Team objectives
9. team_required_skills - Team skill requirements
10. team_members      - Team membership
11. join_requests     - Team join requests
12. conversations     - Message conversations
13. messages          - Individual messages
14. ratings           - Team ratings/reviews
15. activity_logs     - Activity audit trail
16. notifications     - User notifications
```

### 3 Views
```
- user_full_profile      - Complete user information
- team_full_profile      - Complete team information
- conversation_summary   - Message conversation summary
```

---

## 🔐 Security Features

✅ Bcrypt password hashing (cost 12)  
✅ Prepared statements (SQL injection prevention)  
✅ Foreign key constraints (data integrity)  
✅ Unique indexes (duplicate prevention)  
✅ Email verification support  
✅ Activity logging  
✅ Transaction support  

---

## 💡 Using db_connect.php

### Basic Operations

#### Get User
```php
$user = $db->fetchRow("SELECT * FROM users WHERE user_id = ?", [1]);
```

#### Get Multiple Records
```php
$users = $db->fetchAll("SELECT * FROM users WHERE role_id = ?", [1]);
```

#### Insert Record
```php
$userId = $db->insert('users', [
    'full_name' => 'John Doe',
    'email' => 'john@example.com',
    'password_hash' => password_hash('pwd', PASSWORD_BCRYPT),
    'role_id' => 1
]);
```

#### Update Record
```php
$db->update('users', 
    ['last_login' => date('Y-m-d H:i:s')],
    'user_id = ?',
    [1]
);
```

#### Delete Record
```php
$db->delete('users', 'user_id = ?', [999]);
```

#### Check if Exists
```php
if ($db->exists('users', 'email = ?', ['john@example.com'])) {
    echo "User exists";
}
```

#### Count Records
```php
$count = $db->count('users', 'role_id = ?', [1]);
```

#### Execute Query
```php
$stmt = $db->executeQuery("SELECT * FROM users WHERE email = ?", ['john@example.com']);
$result = $stmt->get_result();
```

### Transaction Support

```php
try {
    $db->beginTransaction();
    
    // Create team
    $teamId = $db->insert('teams', [...]);
    
    // Add team leader as member
    $db->insert('team_members', [...]);
    
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    echo "Error: " . $e->getMessage();
}
```

---

## 🎯 Pre-populated Data

### Roles (4)
- Student
- Faculty
- Researcher
- Admin

### Categories (12)
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

### Skills (20+)
- Python, C/C++, Java
- Machine Learning, Deep Learning
- Cryptography, Network Security, Blockchain
- Data Analysis, SQL
- Web Development, Mobile Development
- And more...

### Events (6)
- AI Research Initiative 2025
- Cybersecurity Challenge 2025
- Robotics Competition 2025
- NLP Summit 2025
- Data Science Hackathon 2025
- IoT Innovation Challenge 2025

---

## 🔄 Database Workflow

### User Registration
```
1. Check if email exists
2. Hash password
3. Insert into users table
4. Return user_id
```

### Team Creation
```
1. Insert team record
2. Add team leader as member
3. Add required skills
4. Create conversation
5. Return team_id
```

### Messaging
```
1. Check if conversation exists
2. If not, create conversation
3. Insert message
4. Update conversation last_message_at
5. Create notification
```

### Join Request
```
1. Insert join_request record
2. Create notification for team leader
3. Create system message in conversation
4. Return request_id
```

---

## 📝 Important Notes

### Credentials
```
Host:     localhost
Username: root
Password: (empty)
Port:     3306
```

### Environment
- **Development:** DEBUG_MODE = true (shows detailed errors)
- **Production:** ENVIRONMENT = 'production' (hides errors)

### Password Security
Always use password hashing:
```php
$hash = password_hash($plaintext, PASSWORD_BCRYPT);
if (password_verify($plaintext, $hash)) {
    // Password is correct
}
```

### Backups
Create regular backups:
```bash
mysqldump -u root uiu_research_hub > backup.sql
```

---

## 🚨 Troubleshooting

### Database Connection Failed
```
✓ Check XAMPP MySQL is running
✓ Verify credentials in db_connect.php
✓ Check MySQL port (default 3306)
✓ Look at error logs for details
```

### Import Failed
```
✓ Ensure file is UTF-8 encoded
✓ Check MySQL version (5.7+)
✓ Verify file is not corrupted
✓ Check phpMyAdmin upload limits
```

### Queries Returning Empty
```
✓ Check if data was inserted
✓ Verify table structure: SHOW CREATE TABLE users;
✓ Test with simple SELECT * query
✓ Check user permissions
```

---

## 📚 Additional Resources

- Complete documentation: `DATABASE_DOCUMENTATION.md`
- PHP connection file: `db_connect.php`
- SQL schema: `database.sql`
- MySQL official docs: https://dev.mysql.com/doc/

---

## ✅ Setup Checklist

- [ ] Download and extract project
- [ ] Copy files to `C:\xampp\htdocs\Web\`
- [ ] Start XAMPP MySQL
- [ ] Open phpMyAdmin
- [ ] Import `database.sql`
- [ ] Verify database created
- [ ] Test `db_connect.php`
- [ ] Update credentials if needed
- [ ] Ready to develop!

---

## 🎉 You're All Set!

The database is now ready for use. Start building your application with confidence!

For questions, refer to `DATABASE_DOCUMENTATION.md` or check code comments in `db_connect.php`.

Happy coding! 🚀
