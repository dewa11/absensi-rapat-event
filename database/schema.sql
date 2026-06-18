CREATE DATABASE IF NOT EXISTS rsutirapat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rsutirapat;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('Rapat', 'Event') NOT NULL,
    title VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(150) NOT NULL,
    notes TEXT NOT NULL,
    attendance_token VARCHAR(64) NULL UNIQUE,
    token_generated_at DATETIME NULL,
    token_is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_admin FOREIGN KEY (created_by) REFERENCES admin_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    participant_name VARCHAR(120) NOT NULL,
    nip VARCHAR(32) NOT NULL,
    unit_name VARCHAR(120) NULL,
    selfie_path VARCHAR(255) NULL,
    attendance_status ENUM('Hadir', 'Izin', 'Tidak Hadir') NOT NULL,
    present_at DATETIME NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_event FOREIGN KEY (event_id) REFERENCES events(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO admin_users (username, full_name, password_hash)
SELECT 'adminrsuti', 'Administrator', '$2y$10$QaXiVrcmgrIYNsb6kChRHu4PiJqKlO4sqJPyHdVfkpDBmNLZjTaBe'
WHERE NOT EXISTS (
    SELECT 1 FROM admin_users WHERE username = 'adminrsuti'
);
