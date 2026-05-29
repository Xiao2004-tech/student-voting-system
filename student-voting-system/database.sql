-- Student Online Voting System Database
-- Import this in phpMyAdmin before running the system

CREATE DATABASE IF NOT EXISTS student_voting_db;
USE student_voting_db;

-- ── POSITIONS (e.g. President, VP, Secretary)
CREATE TABLE IF NOT EXISTS positions (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    max_votes   INT NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── CANDIDATES
CREATE TABLE IF NOT EXISTS candidates (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT NOT NULL,
    full_name   VARCHAR(100) NOT NULL,
    student_id  VARCHAR(30)  NOT NULL UNIQUE,
    course      VARCHAR(100) NOT NULL,
    year_level  ENUM('1st Year','2nd Year','3rd Year','4th Year') NOT NULL,
    platform    TEXT,
    status      ENUM('Active','Disqualified') NOT NULL DEFAULT 'Active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
);

-- ── VOTERS (registered students)
CREATE TABLE IF NOT EXISTS voters (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    student_id  VARCHAR(30)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    course      VARCHAR(100) NOT NULL,
    year_level  ENUM('1st Year','2nd Year','3rd Year','4th Year') NOT NULL,
    has_voted   TINYINT(1)   NOT NULL DEFAULT 0,
    voted_at    TIMESTAMP    NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── VOTES
CREATE TABLE IF NOT EXISTS votes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    voter_id     INT NOT NULL,
    candidate_id INT NOT NULL,
    position_id  INT NOT NULL,
    voted_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voter_id)     REFERENCES voters(id)    ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (position_id)  REFERENCES positions(id)  ON DELETE CASCADE,
    UNIQUE KEY unique_vote (voter_id, position_id)
);

-- ── ELECTION SETTINGS
CREATE TABLE IF NOT EXISTS election_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(150) NOT NULL DEFAULT 'Tangub City Global College',
    election_name VARCHAR(150) NOT NULL DEFAULT 'Student Council Election 2025',
    start_date  DATETIME NOT NULL,
    end_date    DATETIME NOT NULL,
    status      ENUM('Upcoming','Ongoing','Ended') NOT NULL DEFAULT 'Upcoming',
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── SAMPLE DATA
INSERT INTO election_settings (school_name, election_name, start_date, end_date, status) VALUES
('Tangub City Global College', 'Student Council Election 2025', '2025-06-01 08:00:00', '2025-06-01 17:00:00', 'Ongoing');

INSERT INTO positions (title, description, max_votes) VALUES
('President',          'Leads the Student Council',                  1),
('Vice President',     'Assists the President',                      1),
('Secretary',          'Manages records and communications',         1),
('Treasurer',          'Manages the organization funds',             1),
('Public Relations Officer', 'Handles external communications',     1);

INSERT INTO candidates (position_id, full_name, student_id, course, year_level, platform) VALUES
(1, 'Maria Santos',   '2021-0001', 'BSCS', '4th Year', 'Better campus facilities and student welfare programs.'),
(1, 'Jose Reyes',     '2021-0002', 'BSIT', '4th Year', 'Transparent governance and academic excellence.'),
(2, 'Ana Dela Cruz',  '2022-0001', 'BSED', '3rd Year', 'Inclusive programs for all students.'),
(2, 'Carlo Mendoza',  '2022-0002', 'BSCS', '3rd Year', 'Digital transformation of student services.'),
(3, 'Liza Garcia',    '2023-0001', 'BSBA', '2nd Year', 'Efficient and transparent record keeping.'),
(4, 'Mark Villanueva','2023-0002', 'BSIT', '2nd Year', 'Accountable and wise use of student funds.'),
(5, 'Nina Torres',    '2023-0003', 'BSED', '2nd Year', 'Active promotion of school events and achievements.');

INSERT INTO voters (full_name, student_id, email, course, year_level) VALUES
('Juan dela Cruz',  '2024-0001', 'juan@tcgc.edu.ph',  'BSCS', '1st Year'),
('Pedro Bautista',  '2024-0002', 'pedro@tcgc.edu.ph', 'BSIT', '1st Year'),
('Rosa Lim',        '2024-0003', 'rosa@tcgc.edu.ph',  'BSBA', '2nd Year');
