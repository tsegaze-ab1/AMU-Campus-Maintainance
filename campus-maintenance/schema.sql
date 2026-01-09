-- schema.sql
-- Minimal database schema required for the login/register + role dashboards.
-- Import this in phpMyAdmin (XAMPP) after creating the database.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    -- Keep 'staff' for backward compatibility; prefer using 'technician' going forward.
    role ENUM('admin','technician','staff','student') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintenance request categories (admin-managed)
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintenance requests (student-created, admin-assigned, technician-updated)
CREATE TABLE IF NOT EXISTS requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(150) NOT NULL,
    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status ENUM('new','in_progress','resolved') NOT NULL DEFAULT 'new',
    created_by INT UNSIGNED NOT NULL,
    assigned_to INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_requests_created_by (created_by),
    KEY idx_requests_assigned_to (assigned_to),
    KEY idx_requests_status (status),
    KEY idx_requests_category_id (category_id),
    CONSTRAINT fk_requests_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_requests_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_requests_assigned_to
        FOREIGN KEY (assigned_to) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Request comments / updates timeline
CREATE TABLE IF NOT EXISTS request_comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_request_comments_request_id (request_id),
    KEY idx_request_comments_user_id (user_id),
    CONSTRAINT fk_request_comments_request
        FOREIGN KEY (request_id) REFERENCES requests(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_request_comments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: attachments table (structure only; no upload UI required)
CREATE TABLE IF NOT EXISTS request_attachments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_request_attachments_request_id (request_id),
    KEY idx_request_attachments_user_id (user_id),
    CONSTRAINT fk_request_attachments_request
        FOREIGN KEY (request_id) REFERENCES requests(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_request_attachments_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: seed an admin user.
-- 1) Create a normal account via register.php
-- 2) Then run: UPDATE users SET role='admin' WHERE email='you@example.com';

-- If you're upgrading an existing database created from an older schema,
-- run this to allow the newer 'technician' role.
ALTER TABLE users
    MODIFY role ENUM('admin','technician','staff','student') NOT NULL DEFAULT 'student';
