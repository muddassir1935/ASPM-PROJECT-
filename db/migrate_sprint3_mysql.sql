-- Sprint 3 migration for an EXISTING MySQL database.
-- Fresh installs should just import db/schema.sql instead.
-- Run once in phpMyAdmin (or: mysql -u root campusconnect < db/migrate_sprint3_mysql.sql).
-- The users.bio column is added only if missing, and the tables use
-- CREATE TABLE IF NOT EXISTS, so re-running is harmless except that
-- the CREATE INDEX lines will report "duplicate key name" and can
-- then be ignored.

-- 1. Profile bio column on users
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'bio'
);
SET @alter_sql := IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL',
    'SELECT "users.bio already exists"');
PREPARE stmt FROM @alter_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Follows table (WBS 3.2)
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

-- 3. Replies table (WBS 3.4.1)
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

-- 4. Bookmarks table (WBS 3.4.3)
CREATE TABLE IF NOT EXISTS bookmarks (
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    CONSTRAINT fk_bookmarks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookmarks_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);
CREATE INDEX idx_bookmarks_post_id ON bookmarks(post_id);

-- 5. Sessions table (approved Problem 2: DB-backed sessions)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id INT DEFAULT NULL,
    data TEXT NOT NULL,
    last_activity INT NOT NULL,
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_sessions_user_id ON sessions(user_id);
CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);
