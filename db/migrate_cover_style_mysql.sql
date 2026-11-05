-- Cover style picker migration for an EXISTING MySQL database.
-- Fresh installs should just import db/schema.sql instead.
-- Run once in phpMyAdmin (or: mysql -u root campusconnect < db/migrate_cover_style_mysql.sql).
-- The users.cover_style column is added only if missing, so
-- re-running this file is harmless.

-- Profile cover style column on users (Sprint 3 cover picker)
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'cover_style'
);
SET @alter_sql := IF(@col_exists = 0,
    'ALTER TABLE users ADD COLUMN cover_style VARCHAR(20) NOT NULL DEFAULT ''signature''',
    'SELECT "users.cover_style already exists"');
PREPARE stmt FROM @alter_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
