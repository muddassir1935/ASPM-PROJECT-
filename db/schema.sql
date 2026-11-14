CREATE DATABASE IF NOT EXISTS campusconnect;
USE campusconnect;

-- Users Table (Sprint 1)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'society_admin', 'system_admin') DEFAULT 'student',
    bio TEXT DEFAULT NULL,
    cover_style VARCHAR(20) NOT NULL DEFAULT 'signature',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Posts Table (Sprint 2: Core Post Engine)
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    is_announcement TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Performance Indexes for Feed & User Queries
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_posts_created_at ON posts(created_at DESC);

-- Follows Table (Sprint 3: Social Graph, WBS 3.2)
CREATE TABLE IF NOT EXISTS follows (
    follower_id INT NOT NULL,
    followed_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, followed_id),
    CONSTRAINT fk_follows_follower FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_follows_followed FOREIGN KEY (followed_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_follows_no_self CHECK (follower_id <> followed_id)
);
CREATE INDEX idx_follows_followed_id ON follows(followed_id);

-- Replies Table (Sprint 3: Threaded Replies, WBS 3.4.1)
CREATE TABLE IF NOT EXISTS replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_replies_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_replies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_replies_post_id ON replies(post_id);
CREATE INDEX idx_replies_created_at ON replies(created_at);

-- Bookmarks Table (Sprint 3: Saved Posts, WBS 3.4.3)
CREATE TABLE IF NOT EXISTS bookmarks (
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    CONSTRAINT fk_bookmarks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookmarks_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
CREATE INDEX idx_bookmarks_post_id ON bookmarks(post_id);

-- Sessions Table (Sprint 3: DB-backed Sessions, approved Problem 2)
-- Session data lives here instead of files; user_id is set at login.
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id INT DEFAULT NULL,
    data TEXT NOT NULL,
    last_activity INT NOT NULL,
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

-- Likes Table (Sprint 4: Heart Reactions, WBS 4.1)
CREATE TABLE IF NOT EXISTS likes (
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
CREATE INDEX idx_likes_post_id ON likes(post_id);
CREATE INDEX idx_likes_created_at ON likes(created_at);

-- Hashtags Table (Sprint 4: Hashtag System, WBS 4.2)
CREATE TABLE IF NOT EXISTS hashtags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tag VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_hashtags_tag ON hashtags(tag);

-- Post Hashtags Association Table (Sprint 4, WBS 4.2)
CREATE TABLE IF NOT EXISTS post_hashtags (
    post_id INT NOT NULL,
    hashtag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (post_id, hashtag_id),
    CONSTRAINT fk_post_hashtags_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_post_hashtags_tag FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE
);
CREATE INDEX idx_post_hashtags_tag ON post_hashtags(hashtag_id);

-- Notifications Table (Sprint 5: In-App Notifications, WBS 5.x)
-- Fresh installs get this table from schema.sql; migrate_sprint5_notifications_mysql.sql
-- is only for databases created before Sprint 5.
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    actor_id INT DEFAULT NULL,
    type VARCHAR(32) NOT NULL,
    reference_id INT DEFAULT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read);
CREATE INDEX idx_notifications_user_created ON notifications(user_id, created_at DESC);

-- Login Attempts Table (Sprint 5: login rate-limit / lockout)
-- One row per (email, IP) pair; see classes/LoginRateLimiter.php.
-- Fresh installs get this table from schema.sql; migrate_sprint5_rbac_mysql.sql
-- is only for databases created before Sprint 5.
CREATE TABLE IF NOT EXISTS login_attempts (
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    last_attempt INT NOT NULL DEFAULT 0,
    locked_until INT NOT NULL DEFAULT 0,
    PRIMARY KEY (email, ip)
);
