-- ============================================================
-- IT8415 Blog Project — schema.sql
-- Student: 202200881 | Group: 5
-- DB: db202200881 | User: u202200881
-- ============================================================

USE db202200881;

-- ============================================================
-- 1. USERS (roles: admin, creator, viewer)
-- ============================================================
CREATE TABLE IF NOT EXISTS dbProj_users (
    uid          INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL,
    email        VARCHAR(100) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,   -- password_hash()
    role         ENUM('admin','creator','viewer') DEFAULT 'viewer',
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS dbProj_categories (
    cat_id   INT AUTO_INCREMENT PRIMARY KEY,
    cat_name VARCHAR(100) NOT NULL
);

-- ============================================================
-- 3. POSTS (main content)
-- ============================================================
CREATE TABLE IF NOT EXISTS dbProj_posts (
    post_id     INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    short_desc  TEXT,
    full_content TEXT,
    image_path  VARCHAR(255),
    pdf_path    VARCHAR(255),
    cat_id      INT,
    uid         INT,
    published   TINYINT(1) DEFAULT 0,
    is_deleted  TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cat_id) REFERENCES dbProj_categories(cat_id) ON DELETE SET NULL,
    FOREIGN KEY (uid)    REFERENCES dbProj_users(uid)          ON DELETE CASCADE,
    INDEX idx_cat_id      (cat_id),
    INDEX idx_uid         (uid),
    INDEX idx_created_at  (created_at),
    INDEX idx_published   (published)
);

-- FULLTEXT index for search (title + content)
ALTER TABLE dbProj_posts ADD FULLTEXT INDEX ft_posts (title, full_content);

-- ============================================================
-- 4. COMMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS dbProj_comments (
    comment_id   INT AUTO_INCREMENT PRIMARY KEY,
    post_id      INT NOT NULL,
    uid          INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES dbProj_posts(post_id)   ON DELETE CASCADE,
    FOREIGN KEY (uid)     REFERENCES dbProj_users(uid)        ON DELETE CASCADE
);

-- ============================================================
-- 5. RATINGS (1–5 stars, one per user per post)
-- ============================================================
CREATE TABLE IF NOT EXISTS dbProj_ratings (
    rating_id  INT AUTO_INCREMENT PRIMARY KEY,
    post_id    INT NOT NULL,
    uid        INT NOT NULL,
    rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_rating_per_user (post_id, uid),
    FOREIGN KEY (post_id) REFERENCES dbProj_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (uid)     REFERENCES dbProj_users(uid)     ON DELETE CASCADE
);

-- ============================================================
-- 6. NOTIFICATIONS (delivered to a recipient_uid, set by triggers)
-- ============================================================
CREATE TABLE IF NOT EXISTS dbProj_notifications (
    notif_id      INT AUTO_INCREMENT PRIMARY KEY,
    recipient_uid INT NOT NULL,
    type          ENUM('comment','rating','system') NOT NULL DEFAULT 'system',
    message       VARCHAR(500) NOT NULL,
    link          VARCHAR(255),
    is_read       TINYINT(1) NOT NULL DEFAULT 0,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_uid) REFERENCES dbProj_users(uid) ON DELETE CASCADE,
    INDEX idx_recipient_unread  (recipient_uid, is_read),
    INDEX idx_recipient_created (recipient_uid, created_at)
);

-- ============================================================
-- TRIGGERS — auto-create notifications for comments and ratings
-- ============================================================
DELIMITER $$
CREATE TRIGGER trg_comment_notify
AFTER INSERT ON dbProj_comments
FOR EACH ROW
BEGIN
    DECLARE post_owner INT;
    DECLARE post_title VARCHAR(200);
    DECLARE commenter_name VARCHAR(50);
    SELECT uid, title INTO post_owner, post_title FROM dbProj_posts WHERE post_id = NEW.post_id;
    SELECT username INTO commenter_name FROM dbProj_users WHERE uid = NEW.uid;
    IF post_owner IS NOT NULL AND post_owner <> NEW.uid THEN
        INSERT INTO dbProj_notifications (recipient_uid, type, message, link)
        VALUES (post_owner, 'comment',
                CONCAT(commenter_name, ' commented on "', post_title, '"'),
                CONCAT('view_post.php?id=', NEW.post_id));
    END IF;
END$$

CREATE TRIGGER trg_rating_notify
AFTER INSERT ON dbProj_ratings
FOR EACH ROW
BEGIN
    DECLARE post_owner INT;
    DECLARE post_title VARCHAR(200);
    DECLARE rater_name VARCHAR(50);
    SELECT uid, title INTO post_owner, post_title FROM dbProj_posts WHERE post_id = NEW.post_id;
    SELECT username INTO rater_name FROM dbProj_users WHERE uid = NEW.uid;
    IF post_owner IS NOT NULL AND post_owner <> NEW.uid THEN
        INSERT INTO dbProj_notifications (recipient_uid, type, message, link)
        VALUES (post_owner, 'rating',
                CONCAT(rater_name, ' rated "', post_title, '" ', NEW.rating, ' star(s)'),
                CONCAT('view_post.php?id=', NEW.post_id));
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- STORED PROCEDURE: GetPopularContent(startDate, endDate)
-- Returns posts ordered by average rating in date range
-- ============================================================
DELIMITER $$
CREATE PROCEDURE GetPopularContent(IN startDate DATE, IN endDate DATE)
BEGIN
    SELECT
        p.post_id,
        p.title,
        p.short_desc,
        p.image_path,
        p.created_at,
        u.username AS author,
        c.cat_name AS category,
        ROUND(AVG(r.rating), 1) AS avg_rating,
        COUNT(r.rating_id)      AS total_ratings
    FROM dbProj_posts p
    LEFT JOIN dbProj_users      u ON p.uid    = u.uid
    LEFT JOIN dbProj_categories c ON p.cat_id = c.cat_id
    LEFT JOIN dbProj_ratings    r ON p.post_id = r.post_id
    WHERE p.published = 1
      AND p.is_deleted = 0
      AND DATE(p.created_at) BETWEEN startDate AND endDate
    GROUP BY p.post_id
    ORDER BY avg_rating DESC, total_ratings DESC;
END$$
DELIMITER ;

-- ============================================================
-- SEED DATA — 3 users, 5 categories, 15+ posts
-- ============================================================

-- Users (passwords hashed with password_hash('pass123', PASSWORD_DEFAULT))
-- Plain passwords for testing: Admin123! / Creator123! / Viewer123!
INSERT INTO dbProj_users (username, email, password, role) VALUES
('admin',         'admin@blog.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('jane_writer',   'jane@blog.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'creator'),
('reader_bob',    'bob@blog.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'viewer');

-- Categories
INSERT INTO dbProj_categories (cat_name) VALUES
('Technology'),
('Lifestyle'),
('Travel'),
('Health'),
('Sports');

-- 15 Posts (published, spread across categories)
INSERT INTO dbProj_posts (title, short_desc, full_content, image_path, cat_id, uid, published, created_at) VALUES
('Getting Started with PHP', 'A beginners guide to PHP programming.', 'PHP is a server-side scripting language widely used for web development. In this post we cover variables, loops, and functions to get you started quickly.', 'images/default.jpg', 1, 2, 1, '2026-01-10 09:00:00'),
('Top 10 VS Code Extensions', 'Boost your productivity with these must-have extensions.', 'From Prettier to GitLens, these extensions will transform your development workflow. We review each one with setup tips and real-world usage examples.', 'images/default.jpg', 1, 2, 1, '2026-01-15 10:00:00'),
('MySQL FULLTEXT Search Explained', 'How to implement fast full-text search in MySQL.', 'FULLTEXT indexes allow you to perform natural language searches across large text columns. Learn how to create indexes and use MATCH...AGAINST queries.', 'images/default.jpg', 1, 2, 1, '2026-01-20 11:00:00'),
('Morning Routine for Developers', 'Start your day right and stay productive all day.', 'A consistent morning routine can dramatically improve your coding focus. We discuss exercise, journaling, and time-blocking techniques used by top developers.', 'images/default.jpg', 2, 2, 1, '2026-01-25 08:00:00'),
('Minimalist Home Office Setup', 'Less clutter, more focus — the perfect workspace.', 'A clean desk leads to a clear mind. Here are our top picks for a minimal yet functional home office including monitor arms, cable management, and lighting.', 'images/default.jpg', 2, 2, 1, '2026-02-01 09:30:00'),
('Budget Travel in Southeast Asia', 'How to explore 5 countries on $30 a day.', 'Southeast Asia is one of the most affordable travel destinations. This guide covers Thailand, Vietnam, Cambodia, Laos, and Indonesia with real budget breakdowns.', 'images/default.jpg', 3, 2, 1, '2026-02-05 12:00:00'),
('Hidden Gems of Oman', 'Beyond the tourist trail — Oman off the beaten path.', 'Oman offers dramatic landscapes, ancient forts, and warm hospitality away from the crowds. We share 10 under-the-radar spots worth visiting.', 'images/default.jpg', 3, 2, 1, '2026-02-10 10:00:00'),
('Japan on a Shoestring', 'Traveling Japan affordably is totally possible.', 'With a JR Pass, hostel stays, and convenience store meals, Japan can be surprisingly affordable. Here is our two-week itinerary under $1500 total.', 'images/default.jpg', 3, 2, 1, '2026-02-15 14:00:00'),
('The Science of Better Sleep', 'Evidence-based tips for deeper, more restful sleep.', 'Sleep deprivation affects memory, mood, and productivity. We review the research on blue light, sleep cycles, and bedroom temperature to help you sleep better.', 'images/default.jpg', 4, 2, 1, '2026-02-20 08:00:00'),
('Beginner Gym Guide', 'Everything you need for your first month at the gym.', 'Starting at the gym is intimidating. This guide covers the essential compound lifts, a simple 3-day program, and nutrition basics to get real results fast.', 'images/default.jpg', 4, 2, 1, '2026-02-25 09:00:00'),
('Mental Health and Coding', 'How to protect your wellbeing as a developer.', 'Burnout is real in the tech industry. We discuss setting boundaries, recognising warning signs, and practical strategies to maintain mental health while coding.', 'images/default.jpg', 4, 2, 1, '2026-03-01 11:00:00'),
('Premier League 2025-26 Preview', 'Who will win the title this season?', 'With new signings and tactical changes across the top clubs, this season promises to be unpredictable. We preview all 20 teams and our top 4 prediction.', 'images/default.jpg', 5, 2, 1, '2026-03-05 15:00:00'),
('Why Formula 1 is Growing Fast', 'The global rise of F1 as a sport and entertainment brand.', 'Drive to Survive changed everything. F1 now has record viewership, new US races, and a younger fanbase than ever. We explore how the sport reinvented itself.', 'images/default.jpg', 5, 2, 1, '2026-03-10 13:00:00'),
('Building a REST API with PHP', 'Create a simple JSON API using pure PHP and MySQL.', 'No frameworks needed. This tutorial walks you through building a REST API from scratch with PHP, handling GET, POST, PUT, DELETE requests and returning JSON.', 'images/default.jpg', 1, 2, 1, '2026-03-15 10:00:00'),
('jQuery vs Vanilla JS in 2026', 'Is jQuery still worth using? An honest look.', 'jQuery once ruled the web. Today vanilla JS covers most use cases natively. We compare them on bundle size, syntax, and browser support with real code examples.', 'images/default.jpg', 1, 2, 1, '2026-03-20 09:00:00');

-- Sample ratings
INSERT INTO dbProj_ratings (post_id, uid, rating) VALUES
(1, 1, 5), (1, 3, 4),
(2, 1, 4), (2, 3, 5),
(3, 1, 3), (3, 3, 4),
(4, 1, 5), (5, 3, 4),
(6, 1, 5), (6, 3, 5),
(9, 1, 4), (12, 3, 5);

-- Sample comments
INSERT INTO dbProj_comments (post_id, uid, comment_text) VALUES
(1, 3, 'Great intro to PHP, very helpful for beginners!'),
(1, 1, 'Good post, maybe add a section on PHP 8 features.'),
(6, 3, 'Been to Thailand and this budget estimate is spot on.'),
(9, 1, 'The blue light section is backed by solid research. Bookmarked.');
