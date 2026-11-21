-- Migration: foreign key on notifications.reference_id (intent 003, unit 009 / EF-6)
-- CampusConnect - Agile Software Project Management
-- Fixes Sir's DB-2 deduction (-0.5): notifications.reference_id had no FK to posts,
-- allowing dangling references when posts were deleted.
--
-- Run in phpMyAdmin (or: mysql -u root campusconnect < db/migrate_sprint5_notifications_fk_mysql.sql).
-- Fresh installs do not need this file: db/schema.sql already has the constraint.
--
-- NOTE: Scoped to post-type notifications ('like', 'reply'). For follow notifications,
-- reference_id is NULL and the follower user reference is captured via actor_id -> users(id).
-- Existing follow notifications store the follower's USER id in reference_id,
-- which would violate the new FK. Clear non-post references first:
UPDATE notifications
SET reference_id = NULL
WHERE type NOT IN ('like', 'reply')
  AND reference_id IS NOT NULL;

-- Clean up any orphaned post references before applying:
DELETE FROM notifications
WHERE type IN ('like', 'reply')
  AND reference_id IS NOT NULL
  AND reference_id NOT IN (SELECT id FROM posts);

ALTER TABLE notifications
    ADD CONSTRAINT fk_notifications_post
    FOREIGN KEY (reference_id) REFERENCES posts(id) ON DELETE CASCADE;
