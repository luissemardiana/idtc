# IDTC Drone (Indonesia Drone Training Center)

This is a premium, editorial, and industrial tech Single-Page Website for IDTC Drone, a professional drone training center. The website is built using PHP 8+, Tailwind CSS (via CDN), and Vanilla JavaScript.

## Features
- **Editorial UX/UI**: Deep Black theme with Primary Orange accents, minimalist typography (Anton & Montserrat).
- **Interactive Components**: Vanilla JS modals for Core Services, animated FAQ accordions, and a scroll-triggered certification timeline.
- **PHP Form Processing**: Secure backend registration with server-side validation, including new fields for Corporate/Institution tracking.
- **Mobile-First Design**: Fully responsive on all devices without horizontal scrolling.
- **Admin Content Studio**: Session-protected article management integrated into the main IDTC page.
- **Admin Panel**: A separate responsive control center at `/admin/` for training programs, services, gallery items, and testimonials.
- **Article Workflow**: Draft, scheduled, published, unpublish, preview, edit, and safe delete.
- **WIB Schedule Calendar**: Monthly calendar for monitoring and selecting article publication dates.

## Project Structure
```text
idtc-drone/
│
├── index.php                 # Main entry point with the massive single-page structure
├── process_registration.php  # Backend script handling form submissions
├── config.php                # Database configuration (PDO)
├── api/
│   ├── auth.php               # Admin login, session check, and logout
│   └── articles.php           # Protected article and image API
├── cron_publish.php           # CLI scheduler for automatic publishing
├── README.md                 # Project documentation
├── database.sql              # Database schema with new fields
└── assets/
    ├── css/
    │   └── style.css         # Custom animations, scrollbars, fonts, modals
    └── js/
        └── script.js         # Vanilla JavaScript (Timeline, Accordion, Modals, Counters)
```

## Setup Instructions

### 1. Requirements
- PHP 8.0 or higher.
- A local web server such as XAMPP, Laragon, or MAMP.
- (Optional) MySQL database for storing registration data.

### 2. Installation
1. Clone or copy the project into your web server's root directory (e.g., `htdocs/idtc-drone`).
2. The project will work out of the box without a database connection. The registration form will simulate success.

### 3. Database Configuration
If you want to save registrations to a database:
1. Create a database named `idtc_drone` in your MySQL server.
2. Import the `database.sql` file.
3. Open the site once at `http://localhost/project/idtc-drone/setup_database.php` to create/update the tables.
4. The default account created by setup is `admin` / `admin123`. Change the password before production use.

### Admin URLs

- `http://localhost/project/idtc-drone/admin/` — dashboard for Training, Services, Gallery, and Testimonials.
- `http://localhost/project/idtc-drone/admin.php` — existing Article and homepage text Content Studio.

The new dashboard persists data in `training_programs`, `services`, `gallery_items`, and `testimonials`. Published training, gallery, and testimonial records are rendered on the public site automatically. Gallery categories become public folders and use the existing lightbox page. Service records are available for management now; the public page currently has no services-card section to render them into.

The Content Studio is part of `index.php`; it is hidden from visitors and all write operations are protected again by the PHP session and CSRF token.

### 4. Automatic Scheduled Publishing (Windows)

Scheduled publishing is performed by `cron_publish.php`, independently of visitors or an open browser. Configure Windows Task Scheduler to run every minute:

```text
Program: C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\Project\idtc-drone\cron_publish.php
Start in: C:\xampp\htdocs\Project\idtc-drone
```

Set the task trigger to repeat every 1 minute indefinitely and ensure the task uses the same PHP installation as Apache. PHP is configured for `Asia/Jakarta`, so dates selected in Content Studio are WIB.

### 5. Content Studio Workflow

1. Log in through the Content Studio entry in the desktop or mobile navigation.
2. Use **Simpan Draft**, **Jadwalkan**, or **Publish Sekarang**.
3. Use the monthly calendar to review scheduled and published dates. Selecting a date fills the schedule field with 09:00 WIB.
4. The scheduler changes due `scheduled` records to `published` and records the actual `published_at` timestamp.
5. **Tarik dari Website** changes an article back to `draft` without deleting its content or image.

### MySQL Schema (`database.sql`)
```sql
CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    whatsapp VARCHAR(30) NOT NULL,
    instansi VARCHAR(150) DEFAULT NULL,
    program VARCHAR(100) NOT NULL,
    kebutuhan VARCHAR(100) NOT NULL,
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```
