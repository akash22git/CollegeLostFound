CREATE DATABASE IF NOT EXISTS college_lost_found;

USE college_lost_found;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('student', 'staff', 'admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(150) NOT NULL,
    item_date DATE NOT NULL,
    image VARCHAR(255),
    status ENUM('active', 'resolved', 'rejected') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_items_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE item_matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    lost_item_id INT UNSIGNED NOT NULL,

    found_item_id INT UNSIGNED NOT NULL,

    status ENUM('pending', 'approved', 'rejected')
        NOT NULL DEFAULT 'pending',

    resolved_by INT UNSIGNED NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_item_match (
        lost_item_id,
        found_item_id
    ),

    CONSTRAINT fk_match_lost_item
        FOREIGN KEY (lost_item_id)
        REFERENCES items(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_match_found_item
        FOREIGN KEY (found_item_id)
        REFERENCES items(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_match_admin
        FOREIGN KEY (resolved_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_password_resets_user (user_id),
    INDEX idx_password_resets_expiry (expires_at),

    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE contact_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    from_user_id INT UNSIGNED NOT NULL,

    to_user_id INT UNSIGNED NOT NULL,

    item_id INT UNSIGNED NOT NULL,

    message TEXT NOT NULL,

    status ENUM('pending', 'accepted', 'declined')
        NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_contact_from_user
        FOREIGN KEY (from_user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_contact_to_user
        FOREIGN KEY (to_user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_contact_item
        FOREIGN KEY (item_id)
        REFERENCES items(id)
        ON DELETE CASCADE
);
