-- ===================================
-- PILAH.IN DATABASE SCHEMA
-- Production-Ready MySQL Database
-- ===================================

-- Drop existing database if exists (use with caution in production)
DROP DATABASE IF EXISTS pilahin;
CREATE DATABASE pilahin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pilahin;

-- ===================================
-- USERS & AUTHENTICATION
-- ===================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_picture VARCHAR(255) DEFAULT '/assets/images/default-avatar.png',
    bio TEXT,
    province VARCHAR(50),
    city VARCHAR(50),
    referral_code VARCHAR(20) UNIQUE,
    referred_by INT UNSIGNED,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(100),
    phone_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('user', 'admin', 'moderator') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_referral_code (referral_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    newsletter_enabled BOOLEAN DEFAULT TRUE,
    push_notifications BOOLEAN DEFAULT TRUE,
    email_notifications BOOLEAN DEFAULT TRUE,
    language VARCHAR(10) DEFAULT 'id',
    theme VARCHAR(20) DEFAULT 'light',
    privacy_level ENUM('public', 'friends', 'private') DEFAULT 'public',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preferences (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- GAMIFICATION SYSTEM
-- ===================================

CREATE TABLE user_points (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total_points INT DEFAULT 0,
    lifetime_points INT DEFAULT 0,
    level INT DEFAULT 1,
    rank_position INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_points (user_id),
    INDEX idx_total_points (total_points DESC),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE points_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    points INT NOT NULL,
    transaction_type ENUM('earn', 'spend', 'bonus', 'penalty') NOT NULL,
    source ENUM('waste_log', 'event', 'achievement', 'referral', 'reward_redemption', 'admin_adjustment') NOT NULL,
    reference_id INT UNSIGNED,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(255),
    category ENUM('waste', 'community', 'streak', 'special') NOT NULL,
    points_reward INT DEFAULT 0,
    requirement_type VARCHAR(50) NOT NULL,
    requirement_value INT NOT NULL,
    tier ENUM('bronze', 'silver', 'gold', 'platinum') DEFAULT 'bronze',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_tier (tier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_earned_at (earned_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- WASTE MANAGEMENT
-- ===================================

CREATE TABLE waste_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(255),
    color VARCHAR(7),
    points_per_kg INT DEFAULT 10,
    co2_reduction_per_kg DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE waste_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    weight_kg DECIMAL(10,2) NOT NULL,
    points_earned INT DEFAULT 0,
    co2_reduced DECIMAL(10,2) DEFAULT 0.00,
    location_id INT UNSIGNED,
    photo VARCHAR(255),
    notes TEXT,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT UNSIGNED,
    verified_at TIMESTAMP NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES waste_categories(id),
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_logged (user_id, logged_at DESC),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tpa_locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('TPA', 'TPS', 'Bank Sampah') NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(50) NOT NULL,
    province VARCHAR(50) NOT NULL,
    latitude DECIMAL(10, 7) NOT NULL,
    longitude DECIMAL(10, 7) NOT NULL,
    phone VARCHAR(20),
    operating_hours VARCHAR(100),
    accepts_categories JSON,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_city (city),
    INDEX idx_type (type),
    INDEX idx_location (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE location_reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES tpa_locations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_location_review (user_id, location_id),
    INDEX idx_location_rating (location_id, rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- REWARDS & MARKETPLACE
-- ===================================

CREATE TABLE rewards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    category ENUM('voucher', 'product', 'donation', 'discount') NOT NULL,
    points_cost INT NOT NULL,
    stock_quantity INT DEFAULT 0,
    stock_unlimited BOOLEAN DEFAULT FALSE,
    partner_name VARCHAR(100),
    partner_logo VARCHAR(255),
    terms_conditions TEXT,
    valid_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_until TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    featured BOOLEAN DEFAULT FALSE,
    total_redemptions INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active_featured (is_active, featured)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reward_redemptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    reward_id INT UNSIGNED NOT NULL,
    points_spent INT NOT NULL,
    redemption_code VARCHAR(50) UNIQUE,
    status ENUM('pending', 'confirmed', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reward_id) REFERENCES rewards(id),
    INDEX idx_user_redeemed (user_id, redeemed_at DESC),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- EVENTS & CHALLENGES
-- ===================================

CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    banner_image VARCHAR(255),
    event_type ENUM('cleanup', 'workshop', 'competition', 'webinar') NOT NULL,
    location_id INT UNSIGNED,
    custom_location VARCHAR(255),
    organizer_id INT UNSIGNED,
    max_participants INT,
    current_participants INT DEFAULT 0,
    points_reward INT DEFAULT 0,
    start_datetime TIMESTAMP NOT NULL,
    end_datetime TIMESTAMP NOT NULL,
    registration_deadline TIMESTAMP,
    status ENUM('draft', 'published', 'ongoing', 'completed', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (location_id) REFERENCES tpa_locations(id) ON DELETE SET NULL,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status_start (status, start_datetime),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE event_participants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    registration_status ENUM('registered', 'confirmed', 'attended', 'cancelled') DEFAULT 'registered',
    check_in_time TIMESTAMP NULL,
    points_earned INT DEFAULT 0,
    feedback TEXT,
    rating TINYINT CHECK (rating BETWEEN 1 AND 5),
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_event_participant (event_id, user_id),
    INDEX idx_event_status (event_id, registration_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE challenges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    challenge_type ENUM('daily', 'weekly', 'monthly', 'special') NOT NULL,
    goal_type VARCHAR(50) NOT NULL,
    goal_value INT NOT NULL,
    points_reward INT DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    total_participants INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_dates (challenge_type, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE challenge_progress (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    challenge_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    current_progress INT DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_challenge_user (challenge_id, user_id),
    INDEX idx_completed (completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- COMMUNITY & SOCIAL
-- ===================================

CREATE TABLE communities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    avatar VARCHAR(255),
    cover_image VARCHAR(255),
    creator_id INT UNSIGNED NOT NULL,
    city VARCHAR(50),
    province VARCHAR(50),
    member_count INT DEFAULT 1,
    total_waste_kg DECIMAL(10,2) DEFAULT 0.00,
    is_verified BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id),
    INDEX idx_slug (slug),
    INDEX idx_member_count (member_count DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE community_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    community_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role ENUM('owner', 'admin', 'member') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_community_member (community_id, user_id),
    INDEX idx_community_role (community_id, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    community_id INT UNSIGNED,
    content TEXT NOT NULL,
    images JSON,
    post_type ENUM('text', 'photo', 'achievement', 'waste_log') DEFAULT 'text',
    related_id INT UNSIGNED,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    is_pinned BOOLEAN DEFAULT FALSE,
    is_hidden BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (community_id) REFERENCES communities(id) ON DELETE CASCADE,
    INDEX idx_community_created (community_id, created_at DESC),
    INDEX idx_user_created (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_like (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    parent_id INT UNSIGNED,
    likes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_post_created (post_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE follows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id INT UNSIGNED NOT NULL,
    following_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- NOTIFICATIONS
-- ===================================

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT,
    link VARCHAR(255),
    icon VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read_created (user_id, is_read, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- ANALYTICS & LOGS
-- ===================================

CREATE TABLE user_activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_created (user_id, created_at DESC),
    INDEX idx_activity_type (activity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_stats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL UNIQUE,
    total_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    total_waste_kg DECIMAL(10,2) DEFAULT 0.00,
    total_co2_reduced DECIMAL(10,2) DEFAULT 0.00,
    total_points_earned INT DEFAULT 0,
    total_events INT DEFAULT 0,
    total_rewards_redeemed INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stat_date (stat_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- INSERT INITIAL DATA
-- ===================================

-- Waste Categories
INSERT INTO waste_categories (name, slug, description, color, points_per_kg, co2_reduction_per_kg) VALUES
('Plastik', 'plastik', 'Botol, kantong, kemasan plastik', '#1a3d2e', 15, 2.50),
('Kertas', 'kertas', 'Kardus, koran, majalah, kertas kantor', '#d4a574', 10, 1.80),
('Organik', 'organik', 'Sisa makanan, daun, ranting', '#8b7355', 8, 0.50),
('Logam', 'logam', 'Kaleng, aluminium, besi', '#2d5a45', 20, 3.20),
('Kaca', 'kaca', 'Botol kaca, pecahan kaca', '#6b8e9f', 12, 1.50);

-- Sample Achievements
INSERT INTO achievements (name, slug, description, category, points_reward, requirement_type, requirement_value, tier) VALUES
('First Step', 'first-step', 'Log sampah pertama Anda', 'waste', 100, 'waste_logs_count', 1, 'bronze'),
('Eco Warrior', 'eco-warrior', 'Log 100kg sampah', 'waste', 500, 'total_waste_kg', 100, 'silver'),
('Community Leader', 'community-leader', 'Buat komunitas pertama', 'community', 200, 'communities_created', 1, 'bronze'),
('Week Streak', 'week-streak', 'Log sampah 7 hari berturut-turut', 'streak', 300, 'login_streak_days', 7, 'silver'),
('Planet Saver', 'planet-saver', 'Kurangi 1 ton CO2', 'waste', 1000, 'total_co2_reduced', 1000, 'gold');

-- Sample TPA Locations (Bali examples)
INSERT INTO tpa_locations (name, type, address, city, province, latitude, longitude, phone, operating_hours, accepts_categories, verified) VALUES
('TPA Suwung', 'TPA', 'Jl. TPA Suwung, Suwung Kangin, Denpasar Selatan', 'Denpasar', 'Bali', -8.721574, 115.232246, '(0361) 427771', '06:00 - 18:00', '["plastik","kertas","organik","logam","kaca"]', TRUE),
('Bank Sampah Gemah Ripah', 'Bank Sampah', 'Jl. Tukad Yeh Aya No.10, Renon, Denpasar', 'Denpasar', 'Bali', -8.672829, 115.232958, '0812-3456-7890', '08:00 - 16:00', '["plastik","kertas","logam"]', TRUE),
('TPS 3R Desa Adat Kuta', 'TPS', 'Jl. Dewi Sartika, Kuta, Badung', 'Badung', 'Bali', -8.720000, 115.172000, '0813-3456-7890', '07:00 - 17:00', '["plastik","kertas","organik"]', TRUE);

-- Sample Rewards
INSERT INTO rewards (name, description, category, points_cost, stock_quantity, partner_name, is_active, featured) VALUES
('Voucher Indomaret Rp 50.000', 'Voucher belanja di Indomaret senilai Rp 50.000', 'voucher', 5000, 100, 'Indomaret', TRUE, TRUE),
('Eco Bag Premium', 'Tas belanja ramah lingkungan berkualitas tinggi', 'product', 3000, 50, 'Pilah.in Store', TRUE, TRUE),
('Voucher Grab Rp 25.000', 'Voucher transportasi Grab senilai Rp 25.000', 'voucher', 2500, 200, 'Grab', TRUE, FALSE),
('Donasi 1 Pohon', 'Kami tanam 1 pohon atas nama Anda', 'donation', 1000, 0, 'Green Indonesia', TRUE, TRUE),
('Tumbler Stainless Steel', 'Tumbler stainless steel 500ml', 'product', 4000, 30, 'Pilah.in Store', TRUE, FALSE);

-- Sample Challenges
INSERT INTO challenges (title, description, challenge_type, goal_type, goal_value, points_reward, start_date, end_date, is_active) VALUES
('7 Days Green Challenge', 'Log sampah setiap hari selama 7 hari', 'weekly', 'daily_logs', 7, 500, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), TRUE),
('100kg Waste Warrior', 'Kumpulkan 100kg sampah dalam sebulan', 'monthly', 'total_weight', 100, 1000, CURDATE(), LAST_DAY(CURDATE()), TRUE),
('Plastic Free Day', 'Tidak log sampah plastik hari ini', 'daily', 'zero_plastic', 1, 200, CURDATE(), CURDATE(), TRUE);

-- Admin User (password: admin123)
INSERT INTO users (username, email, password_hash, fullname, phone, province, city, role, email_verified) VALUES
('admin', 'admin@pilahin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '081234567890', 'bali', 'denpasar', 'admin', TRUE);

-- Initialize admin points
INSERT INTO user_points (user_id, total_points, lifetime_points, level) VALUES
(1, 0, 0, 1);

-- Initialize admin preferences
INSERT INTO user_preferences (user_id) VALUES (1);

-- ===================================
-- STORED PROCEDURES & TRIGGERS
-- ===================================

DELIMITER $$

-- Procedure to update user points and level
CREATE PROCEDURE update_user_points(IN p_user_id INT, IN p_points INT)
BEGIN
    UPDATE user_points 
    SET 
        total_points = total_points + p_points,
        lifetime_points = lifetime_points + GREATEST(p_points, 0),
        level = FLOOR(SQRT(lifetime_points / 100)) + 1
    WHERE user_id = p_user_id;
END$$

-- Procedure to calculate leaderboard ranks
CREATE PROCEDURE calculate_leaderboard()
BEGIN
    SET @rank = 0;
    UPDATE user_points
    SET rank_position = (@rank := @rank + 1)
    ORDER BY total_points DESC, updated_at ASC;
END$$

-- Trigger to update post likes count
CREATE TRIGGER after_post_like_insert
AFTER INSERT ON post_likes
FOR EACH ROW
BEGIN
    UPDATE posts SET likes_count = likes_count + 1 WHERE id = NEW.post_id;
END$$

CREATE TRIGGER after_post_like_delete
AFTER DELETE ON post_likes
FOR EACH ROW
BEGIN
    UPDATE posts SET likes_count = likes_count - 1 WHERE id = OLD.post_id;
END$$

-- Trigger to update community member count
CREATE TRIGGER after_community_member_insert
AFTER INSERT ON community_members
FOR EACH ROW
BEGIN
    UPDATE communities SET member_count = member_count + 1 WHERE id = NEW.community_id;
END$$

CREATE TRIGGER after_community_member_delete
AFTER DELETE ON community_members
FOR EACH ROW
BEGIN
    UPDATE communities SET member_count = member_count - 1 WHERE id = OLD.community_id;
END$$

DELIMITER ;

-- ===================================
-- GRANT PERMISSIONS (adjust for production)
-- ===================================

-- CREATE USER 'pilahin_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON pilahin.* TO 'pilahin_user'@'localhost';
-- FLUSH PRIVILEGES;
