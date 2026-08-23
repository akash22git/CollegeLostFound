# College Lost & Found System

A PHP and MySQL web application that helps students and staff report, search for, and recover lost or found belongings on campus.

## Features

- User registration, login, logout, and password reset pages
- Mandatory 10-digit contact number during registration
- Lost-item and found-item reports with optional images
- Report details, including the reporter's phone number for active reports
- Search and filters for item name, type, category, and location
- Pagination on the reports list (10 reports per page)
- Personal **My Reports** page
- Admin dashboard with report statistics and status management
- Admin matching of lost reports with found reports
- Validation for report dates and image uploads

## Technology Used

- Frontend: HTML
- Backend: PHP
- Database: MySQL
- Local development: PHP built-in server / XAMPP

## Requirements

- PHP 8.0 or later
- MySQL or MariaDB
- PHP extensions: PDO MySQL and Fileinfo

## Installation

1. Create a MySQL database by importing `database/schema.sql` in phpMyAdmin or MySQL.
2. Copy `.env.example` to a new file named `.env` in the project root.
3. Update the database details in `.env`:

```env
DB_HOST=localhost
DB_NAME=college_lost_found
DB_USER=your_database_user
DB_PASSWORD=your_database_password
```

4. Start the project from the project root:

```bash
php -S localhost:8000 -t public
```

5. Open `http://localhost:8000/index.php` in your browser.

## Create an Admin Account

First register a normal account through the website. Then run this query in phpMyAdmin, replacing the email address with the registered email:

```sql
UPDATE users
SET role = 'admin'
WHERE email = 'your-email@example.com';
```

Log out and log in again. The account will open the Admin Dashboard.

## Password Reset Email

The password-reset pages securely create one-time, one-hour reset tokens in the `password_resets` table.

For local configuration, add these settings to `.env`:

```env
APP_URL=http://localhost:8000
MAIL_FROM=your-email@example.com
```

Actual email delivery requires a configured mail service. When deploying to InfinityFree free hosting, use PHPMailer with an external SMTP provider instead of PHP's `mail()` function.

## Project Structure

```text
app/          Authentication, navigation, CSRF, and validation helpers
config/       Database connection configuration
database/     Database schema
public/       Website pages and uploaded images
public/admin/ Admin-only pages
```

## Security Measures

- Passwords are hashed with `password_hash()`.
- Database queries use prepared statements.
- Output is escaped with `htmlspecialchars()`.
- Image uploads check file size and MIME type.
- Uploaded PHP files are blocked from running.
- Password-reset tokens are hashed, single-use, and expire after one hour.
