<?php
/**
 * IDTC Drone Database Setup Script
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/content_migration.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Connect to MySQL Server (without selecting DB first)
    $dsn = "mysql:host=" . DB_HOST . ";port=3306;charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3
    ]);

    // 2. Create Database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    // 3. Create registrations table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL,
            whatsapp VARCHAR(30) NOT NULL,
            instansi VARCHAR(150) DEFAULT NULL,
            program VARCHAR(100) NOT NULL,
            kebutuhan VARCHAR(100) NOT NULL,
            catatan TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Create admin_users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 5. Create articles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS articles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            category VARCHAR(100) NOT NULL,
            author VARCHAR(100) NOT NULL,
            summary TEXT NULL,
            content LONGTEXT NOT NULL,
            image_url TEXT NULL,
            display_order INT NOT NULL DEFAULT 0,
            status ENUM('draft', 'scheduled', 'published') DEFAULT 'draft',
            scheduled_at DATETIME NULL,
            published_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by VARCHAR(100) NULL,
            updated_by VARCHAR(100) NULL,
            INDEX idx_articles_status_schedule (status, scheduled_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Keep existing installations compatible with the current article model.
    $columnCheck = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'articles' AND COLUMN_NAME = 'updated_by'");
    $columnCheck->execute([DB_NAME]);
    if (!(int)$columnCheck->fetchColumn()) {
        $pdo->exec("ALTER TABLE articles ADD COLUMN updated_by VARCHAR(100) NULL AFTER created_by");
    }

    // 6. Seed Default Admin Account (admin / admin123)
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
    $stmt->execute(['admin']);
    if (!$stmt->fetch()) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $insertStmt = $pdo->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
        $insertStmt->execute(['admin', $hash]);
    }

    // 7. Public content modules managed from /admin/
    $pdo->exec("CREATE TABLE IF NOT EXISTS training_programs (
        id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, subtitle VARCHAR(255) NULL,
        description TEXT NOT NULL, duration VARCHAR(120) NULL, target_audience VARCHAR(255) NULL,
        features TEXT NULL, image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active',
        display_order INT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_training_public (status, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, description TEXT NOT NULL,
        price VARCHAR(100) NULL, image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active',
        display_order INT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_services_public (status, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_items (
        id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(100) NOT NULL, title VARCHAR(180) NOT NULL,
        caption VARCHAR(255) NULL, image_url TEXT NOT NULL,
        status ENUM('active','draft') NOT NULL DEFAULT 'active', display_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_gallery_public (category, status, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Gallery photo folders. Categories group photos and
    // mirror the folder cards shown on the public TRAINING GALLERY section.
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery_folders (
        id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL UNIQUE, description TEXT NULL, cover_image_url TEXT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    try { $pdo->exec("ALTER TABLE gallery_folders ADD COLUMN description TEXT NULL AFTER category"); } catch (PDOException $e) { /* Column already exists. */ }
    try { $pdo->exec("ALTER TABLE gallery_folders ADD COLUMN cover_image_url TEXT NULL AFTER description"); } catch (PDOException $e) { /* Column already exists. */ }
    $pdo->exec("INSERT IGNORE INTO gallery_folders (category) SELECT DISTINCT category FROM gallery_items");
    // Network & Credentials (TRUSTED & RECOGNIZED) content module. Creating the
    // table here keeps fresh installs in sync with the ad-hoc creation in the API.
    $pdo->exec("CREATE TABLE IF NOT EXISTS credentials (
        id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL, org_name VARCHAR(180) NOT NULL,
        relationship VARCHAR(255) NULL, image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active',
        display_order INT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_credentials_public (status, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    // Certification cards (section CERTIFICATION on the homepage).
    $pdo->exec("CREATE TABLE IF NOT EXISTS certifications (
        id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL, title VARCHAR(180) NOT NULL,
        description TEXT NOT NULL, image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active',
        display_order INT NOT NULL DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_certifications_public (status, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS certifications (
    id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL, title VARCHAR(180) NOT NULL, description TEXT NOT NULL,
    image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active', display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_certifications_public (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS testimonials (
        id INT AUTO_INCREMENT PRIMARY KEY, customer_name VARCHAR(150) NOT NULL, role VARCHAR(180) NULL,
        testimonial TEXT NOT NULL, photo_url TEXT NULL, rating TINYINT UNSIGNED NULL,
        status ENUM('active','draft') NOT NULL DEFAULT 'active', display_order INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_testimonials_public (status, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Import the real static website data only when each destination is empty.
    // It is safe to run this setup script again: existing admin edits are kept.
    $migration = idtcMigrateExistingContent($pdo, __DIR__);

    echo json_encode([
        'status' => 'success',
        'message' => 'Database initialized and existing website content synchronized.',
        'migrated' => $migration
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database setup failed: ' . $e->getMessage()
    ]);
}
?>
