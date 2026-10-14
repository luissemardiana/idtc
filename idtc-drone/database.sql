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

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    ,INDEX idx_articles_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS training_programs (
    id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, subtitle VARCHAR(255) NULL, description TEXT NOT NULL,
    duration VARCHAR(120) NULL, target_audience VARCHAR(255) NULL, features TEXT NULL, image_url TEXT NULL,
    status ENUM('active','draft') NOT NULL DEFAULT 'active', display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_training_public (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, category VARCHAR(120) NULL, subtitle VARCHAR(255) NULL,
    description TEXT NOT NULL, price VARCHAR(100) NULL, image_url TEXT NULL, full_case_study LONGTEXT NULL, metrics LONGTEXT NULL,
    status ENUM('active','draft') NOT NULL DEFAULT 'active', display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_services_public (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gallery_items (
    id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(100) NOT NULL, title VARCHAR(180) NOT NULL, caption VARCHAR(255) NULL,
    image_url TEXT NOT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active', display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gallery_public (category, status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gallery_folders (
    id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL UNIQUE, description TEXT NULL, cover_image_url TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS credentials (
    id INT AUTO_INCREMENT PRIMARY KEY, category VARCHAR(120) NOT NULL, org_name VARCHAR(180) NOT NULL, relationship VARCHAR(255) NULL,
    image_url TEXT NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active', display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_credentials_public (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY, customer_name VARCHAR(150) NOT NULL, role VARCHAR(180) NULL, testimonial TEXT NOT NULL,
    photo_url TEXT NULL, rating TINYINT UNSIGNED NULL, status ENUM('active','draft') NOT NULL DEFAULT 'active', display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_testimonials_public (status, display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Default Admin (Username: admin, Password: admin123)
-- Hash generated via BCRYPT
INSERT INTO admin_users (username, password)
SELECT 'admin', '$2y$10$4n9P.t17DGBn1fV7y2578O77KqN7e7m3VzS61/Wz95N6B2A8D9kE2'
WHERE NOT EXISTS (SELECT 1 FROM admin_users WHERE username = 'admin');
