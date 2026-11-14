-- Sprint 5 RBAC migration (MySQL): rename roles
--   student       -> student (unchanged)
--   admin         -> system_admin
--   society       -> society_admin
-- Run in phpMyAdmin (or: mysql -u root campusconnect < db/migrate_sprint5_rbac_mysql.sql).
-- Fresh installs do not need this file: db/schema.sql already has the new ENUM.
--
-- Step 1: widen the ENUM so old and new values coexist. A direct MODIFY to
-- the final ENUM would fail (or blank the value) on rows still holding
-- 'admin'/'society' under MySQL strict mode.
ALTER TABLE users MODIFY role ENUM('student','admin','society','society_admin','system_admin') NOT NULL DEFAULT 'student';

-- Step 2: move existing accounts to the new role names.
UPDATE users SET role = 'system_admin' WHERE role = 'admin';
UPDATE users SET role = 'society_admin' WHERE role = 'society';

-- Step 3: narrow the ENUM to the final role set.
ALTER TABLE users MODIFY role ENUM('student','society_admin','system_admin') NOT NULL DEFAULT 'student';

-- Step 4: society announcements (posts.is_announcement, Sprint 5 RBAC).
-- Only society_admin / system_admin may set it (enforced in PostController).
ALTER TABLE posts ADD COLUMN is_announcement TINYINT(1) NOT NULL DEFAULT 0;

-- Step 5: login rate-limit / lockout table (see classes/LoginRateLimiter.php).
CREATE TABLE IF NOT EXISTS login_attempts (
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    last_attempt INT NOT NULL DEFAULT 0,
    locked_until INT NOT NULL DEFAULT 0,
    PRIMARY KEY (email, ip)
);
