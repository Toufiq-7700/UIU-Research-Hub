# UIU Research Hub (PHP + MySQL)

UIU Research Hub is a PHP/MySQL web application for connecting students and faculty, forming research teams, and sharing research resources.

## Features

- **Authentication**: Sign up / login with role-based access (Student / Faculty / Admin)
- **Team Finder**: Browse/search teams by category and request to join
- **Team Management**: Create teams (students), manage members, handle join requests, transfer leadership
- **Faculty Directory**: Browse faculty profiles
- **Research Categories**: View and manage research areas/categories
- **Resource Sharing**: Upload and browse papers/datasets/tools/tutorials (files stored in `uploads/`)
- **Messaging (basic)**: Conversation/messages pages for user communication

## Run On Localhost (XAMPP)

### 1) Prerequisites

- **XAMPP** (Apache + MySQL) with **PHP 7.4+**
- A browser (Chrome/Edge/Firefox)

### 2) Put the project in `htdocs`

1. Copy/clone this project folder into your XAMPP `htdocs` directory:
   - Windows example: `C:\xampp\htdocs\WEB_Progamming_Project\`
2. (Recommended) Avoid spaces in the folder name for cleaner URLs.

### 3) Start Apache + MySQL

Open **XAMPP Control Panel** and start:
- **Apache**
- **MySQL**

### 4) Import the database

1. Open phpMyAdmin: `http://localhost/phpmyadmin/`
2. Go to the **Import** tab
3. Select `database.sql` from this project
4. Click **Go**

This creates the database **`uiu_research_hub`** and required tables.

### 5) Configure database connection (if needed)

Update credentials in:
- `db-connect.php` (used by the main pages)

If your project setup references the underscore version as well, also update:
- `db_connect.php`

Default (XAMPP) values are usually:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'uiu_research_hub');
define('DB_PORT', 3306);
```

### 6) Open the app

Visit:
- `http://localhost/<project-folder>/` (example: `http://localhost/WEB_Progamming_Project/`)

Entry page:
- `index.php`

Create an account from:
- `signup.php`

## License → MIT — see LICENSE.

## Notes
- Additional setup/database details are in `SETUP_GUIDE.md` and `DATABASE_DOCUMENTATION.md`.
- File uploads are stored in `uploads/` (make sure it exists and is writable on your system).
