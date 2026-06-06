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

1. Install the project locally or deploy it to Vercel.
2. Create a MySQL database and import `ikonex_academy.sql`.
3. Configure database environment variables for local or Vercel deployment.
4. Open `/index.php` in your browser, or visit the Vercel deployment URL.

## Vercel deployment

- Add these environment variables in Vercel: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
- Make sure the database is accessible from Vercel (external MySQL host or database service).
- The project uses the `vercel-php` runtime in `vercel.json` for PHP support.

## Default login

- Username: `admin`
- Password: `Admin@123`

## Files added

- `dashboard.php` - main application entry with session protection
- `index.php` / `logout.php` - authentication pages
- `config.php` - shared database and helper functions
- `api/streams.php`, `api/students.php`, `api/subjects.php`, `api/scores.php`, `api/reports.php`
- `student_report.php`, - printable reports
- `ikonex_academy.sql` - MySQL schema and sample seed data

## Notes

- Keep the theme and styling consistent with the existing UI.
- Use `student_report.php` and the browser print dialog to save PDF reports.
