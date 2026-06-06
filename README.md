# Ikonex Academy - Student Management System

A web-based Student Management System built with PHP, MySQL, HTML, CSS, and JavaScript. The project includes:

- Secure login and session-based access control
- Class stream management
- Student registration, editing, and deletion
- Subject management with stream assignment
- Assessment scoring with duplicate prevention
- Result processing (totals, averages, grades, positions)
- Printable student report cards and class performance reports

## Setup

1. Place the project directory in your XAMPP `htdocs` folder.
2. Start Apache and MySQL in XAMPP.
3. Import `ikonex_academy.sql` into MySQL using phpMyAdmin or the MySQL command line.
4. Open `http://localhost/Ikonex Academy/login.php` in your browser.

## Default login

- Username: `admin`
- Password: `Admin@123`

## Files added

- `dashboard.php` - main application entry with session protection
- `index.php` / `logout.php` - authentication pages
- `config.php` - shared database and helper functions
- `api/streams.php`, `api/students.php`, `api/subjects.php`, `api/scores.php`, `api/reports.php`
- `student_report.php`, `class_report.php` - printable reports
- `db.sql` - MySQL schema and sample seed data

## Notes

- Keep the theme and styling consistent with the existing UI.
- Use `student_report.php` and the browser print dialog to save PDF reports.
