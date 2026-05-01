-- ============================================================
-- QuickFix ZW Database Schema
-- Group 3 | BSIT Level 2.2 | CUT | Mr. Masamha
-- ============================================================
-- SETUP: Import this file into a database named quickfix_db
-- via phpMyAdmin → Import tab
-- ============================================================

CREATE DATABASE IF NOT EXISTS quickfix_db;
USE quickfix_db;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(20),
    role          ENUM('client','professional','admin') NOT NULL,
    national_id   VARCHAR(30) DEFAULT NULL,
    verified      TINYINT DEFAULT 0,
    location      VARCHAR(100) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- PROFESSIONAL PROFILES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS professional_profiles (
    profile_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNIQUE NOT NULL,
    trade            ENUM('Plumbing','Electrical','Painting','Carpentry','Tiling','Roofing','Welding','Landscaping','General Handyman') NOT NULL,
    bio              TEXT,
    years_experience INT DEFAULT 0,
    hourly_rate      DECIMAL(10,2) DEFAULT 5.00,
    rating_avg       DECIMAL(3,2) DEFAULT 0.00,
    total_reviews    INT DEFAULT 0,
    jobs_completed   INT DEFAULT 0,
    is_available     TINYINT DEFAULT 1,
    service_area     VARCHAR(200),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS professional_portfolio_images (
    image_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    image_path     VARCHAR(255) NOT NULL,
    original_name  VARCHAR(255) DEFAULT NULL,
    mime_type      VARCHAR(100) DEFAULT NULL,
    file_size      INT DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ============================================================
-- JOB REQUESTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS job_requests (
    job_id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id           INT NOT NULL,
    title               VARCHAR(200) NOT NULL,
    description         TEXT NOT NULL,
    trade               ENUM('Plumbing','Electrical','Painting','Carpentry','Tiling','Roofing','Welding','Landscaping','General Handyman') NOT NULL,
    location            VARCHAR(100) NOT NULL,
    client_budget       DECIMAL(10,2) DEFAULT 0.00,
    urgency             ENUM('flexible','within_week','urgent') DEFAULT 'flexible',
    status              ENUM('open','in_progress','completed','cancelled') DEFAULT 'open',
    hired_professional  INT DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(user_id),
    FOREIGN KEY (hired_professional) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS job_request_images (
    image_id       INT AUTO_INCREMENT PRIMARY KEY,
    job_id         INT NOT NULL,
    image_path     VARCHAR(255) NOT NULL,
    original_name  VARCHAR(255) DEFAULT NULL,
    mime_type      VARCHAR(100) DEFAULT NULL,
    file_size      INT DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES job_requests(job_id) ON DELETE CASCADE
);

-- ============================================================
-- BIDS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS bids (
    bid_id          INT AUTO_INCREMENT PRIMARY KEY,
    job_id          INT NOT NULL,
    professional_id INT NOT NULL,
    bid_amount      DECIMAL(10,2) NOT NULL,
    message         TEXT,
    estimated_days  INT DEFAULT 1,
    status          ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES job_requests(job_id) ON DELETE CASCADE,
    FOREIGN KEY (professional_id) REFERENCES users(user_id)
);

-- ============================================================
-- BOOKINGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS bookings (
    booking_id      INT AUTO_INCREMENT PRIMARY KEY,
    job_id          INT DEFAULT NULL,
    client_id       INT NOT NULL,
    professional_id INT NOT NULL,
    bid_id          INT DEFAULT NULL,
    agreed_amount   DECIMAL(10,2) NOT NULL,
    scheduled_date  DATE DEFAULT NULL,
    status          ENUM('confirmed','in_progress','completed','cancelled','disputed') DEFAULT 'confirmed',
    payment_status  ENUM('pending','held','released','refunded') DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at    TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (job_id) REFERENCES job_requests(job_id),
    FOREIGN KEY (client_id) REFERENCES users(user_id),
    FOREIGN KEY (professional_id) REFERENCES users(user_id),
    FOREIGN KEY (bid_id) REFERENCES bids(bid_id)
);

-- ============================================================
-- REVIEWS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS reviews (
    review_id   INT AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    rating      TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment     TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id),
    FOREIGN KEY (reviewee_id) REFERENCES users(user_id)
);

-- ============================================================
-- MESSAGES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS messages (
    message_id  INT AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT NOT NULL,
    receiver_id INT NOT NULL,
    booking_id  INT DEFAULT NULL,
    message     TEXT NOT NULL,
    is_read     TINYINT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES users(user_id)
);

-- ============================================================
-- DEMO DATA
-- All demo accounts use password: password
-- Hash below = bcrypt of "password"
-- ============================================================

INSERT IGNORE INTO users (full_name, email, password_hash, phone, role, national_id, verified, location) VALUES
('System Admin',       'admin@quickfixzw.co.zw',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263771000000', 'admin',        NULL,           1, 'Harare'),
('Tafadzwa Moyo',      'plumber@quickfixzw.co.zw',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263772111111', 'professional', '63-111111A21', 1, 'Chinhoyi'),
('Blessing Chikwanda', 'electrician@quickfixzw.co.zw',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263772222222', 'professional', '63-222222B21', 1, 'Harare'),
('Rudo Masara',        'painter@quickfixzw.co.zw',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263772333333', 'professional', '63-333333C21', 1, 'Chinhoyi'),
('Farai Zimba',        'carpenter@quickfixzw.co.zw',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263772444444', 'professional', '63-444444D21', 1, 'Harare'),
('Tendai Mwari',       'client1@quickfixzw.co.zw',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263773000001', 'client',       NULL,           1, 'Harare'),
('Chipo Nyamukapa',    'client2@quickfixzw.co.zw',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+263773000002', 'client',       NULL,           1, 'Chinhoyi');

-- Professional profiles
INSERT IGNORE INTO professional_profiles (user_id, trade, bio, years_experience, hourly_rate, rating_avg, total_reviews, jobs_completed, service_area) VALUES
(2, 'Plumbing',     'Experienced plumber specialising in burst pipes, geyser installation, bathroom fitting and drainage. Available across Mashonaland West.', 5, 8.00,  4.80, 12, 15, 'Chinhoyi, Karoi, Murombedzi, Harare'),
(3, 'Electrical',   'Certified electrician. DB board installation, fault finding, solar system wiring, and new house connections. Based in Harare.', 7, 10.00, 4.60,  8, 20, 'Harare, Chitungwiza, Epworth'),
(4, 'Painting',     'Professional interior and exterior painter. We supply materials or work with yours. Clean, neat finish guaranteed.', 3, 6.00,  4.90,  5, 10, 'Chinhoyi, Kadoma, Chegutu'),
(5, 'Carpentry',    'Furniture making, door and window fitting, kitchen units, decking, and general carpentry. 9 years experience in Harare.', 9, 9.00,  4.70,  3,  8, 'Harare, Ruwa, Marondera');

-- Sample open job requests from clients
INSERT IGNORE INTO job_requests (client_id, title, description, trade, location, client_budget, urgency, status) VALUES
(6, 'Fix leaking kitchen sink pipe',   'My kitchen sink pipe has been leaking under the cabinet for 3 days. Water is damaging the cabinet floor. Need urgent repair. House in Kuwadzana, Harare.', 'Plumbing',   'Harare',   15.00, 'urgent',      'open'),
(6, 'Paint 3 bedroom house exterior',  'Need full exterior of my 3-bedroom house painted. Walls only, not roof. Located in Chinhoyi town. About 180 square metres total.', 'Painting',   'Chinhoyi', 120.00, 'within_week', 'open'),
(7, 'Install 6 ceiling light fittings','Need 6 ceiling light fittings installed in my newly built house in Chinhoyi. Wiring is already in place — just need the fittings connected and covers fitted.', 'Electrical', 'Chinhoyi',  40.00, 'flexible',    'open'),
(7, 'Build wooden kitchen cabinets',   'Need a carpenter to build and install 4 wooden kitchen wall cabinets. I have the measurements. Kitchen in Harare Norton Road area.', 'Carpentry',  'Harare',    80.00, 'within_week', 'open');
