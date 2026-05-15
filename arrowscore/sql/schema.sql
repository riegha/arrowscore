CREATE DATABASE IF NOT EXISTS arrowscore_db;
USE arrowscore_db;

CREATE TABLE clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    club_id INT NULL,
    birth_date DATE NOT NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE competitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('single_event','series') DEFAULT 'single_event',
    public_link_slug VARCHAR(100) UNIQUE,
    status ENUM('draft','ongoing','finished') DEFAULT 'draft',
    logo_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    max_score_per_shot INT DEFAULT 10,
    min_score_per_shot INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE rounds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    `order` INT NOT NULL,
    format ENUM('bracket','qualification') DEFAULT 'qualification',
    shots_per_rambahan INT NOT NULL,
    total_rambahan INT NOT NULL,
    face_target_count INT NOT NULL DEFAULT 1,
    status ENUM('pending','ongoing','finished') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE face_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    number INT NOT NULL,
    label VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE,
    UNIQUE KEY unique_target_per_round (round_id, number)
) ENGINE=InnoDB;

CREATE TABLE participant_face_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    participant_id INT NOT NULL,
    face_target_id INT NOT NULL,
    shooting_order TINYINT NOT NULL CHECK (shooting_order IN (1,2)),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
    FOREIGN KEY (face_target_id) REFERENCES face_targets(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assign (round_id, face_target_id, shooting_order)
) ENGINE=InnoDB;

CREATE TABLE rambahans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    number INT NOT NULL,
    is_locked BOOLEAN DEFAULT FALSE,
    locked_at TIMESTAMP NULL,
    locked_by_session INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shots (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    rambahan_id INT NOT NULL,
    participant_id INT NOT NULL,
    shot_number INT NOT NULL,
    score INT NOT NULL DEFAULT 0,
    is_x BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rambahan_id) REFERENCES rambahans(id) ON DELETE CASCADE,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','committee') DEFAULT 'committee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE input_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    round_id INT NOT NULL,
    unique_slug VARCHAR(100) UNIQUE NOT NULL,
    hashed_password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (round_id) REFERENCES rounds(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE input_session_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    input_session_id INT NOT NULL,
    face_target_id INT NOT NULL,
    display_order INT NOT NULL,
    FOREIGN KEY (input_session_id) REFERENCES input_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (face_target_id) REFERENCES face_targets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE score_audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    shot_id BIGINT NOT NULL,
    old_score INT,
    new_score INT,
    old_is_x BOOLEAN,
    new_is_x BOOLEAN,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shot_id) REFERENCES shots(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE series_points_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    competition_id INT NOT NULL,
    `rank` INT NOT NULL,
    points INT NOT NULL,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (name, email, password_hash, role) VALUES
('Administrator', 'admin@arrowscore.com', '$2y$10$8Mx1ZqY0Jq3Vh7EjFb0dC.0pBtT1Jm3K9vqHt1Xk3n3u3O3z3lWPe', 'admin');