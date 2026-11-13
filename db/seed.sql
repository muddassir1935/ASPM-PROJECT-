-- =====================================================================
-- CampusConnect Demo Seed Data (Sprint 5, Problem 12)
-- Standard University Dataset for Evaluation, Demo, and Testing
-- Compatible with MySQL (XAMPP / MariaDB) and SQLite
-- =====================================================================

-- Default password for all seed accounts: StrongPass123!
-- Bcrypt Hash: $2y$10$fDK5wW3xgr8fRlkILk2URuMARwJo9on/0xWquENZUNPMEk9CmY23e

-- ---------------------------------------------------------------------
-- 1. DEMO USERS
-- ---------------------------------------------------------------------
INSERT INTO users (id, name, email, password_hash, role, bio, cover_style, created_at) VALUES
(1, 'Ali Ahmed', 'student@nu.edu.pk', '$2y$10$fDK5wW3xgr8fRlkILk2URuMARwJo9on/0xWquENZUNPMEk9CmY23e', 'student', 'BS Computer Science (Junior) @ FAST-NUCES. Full-stack developer & open-source enthusiast.', 'cobalt', NOW() - INTERVAL 10 DAY),
(2, 'ACM NUCES Chapter', 'society@nu.edu.pk', '$2y$10$fDK5wW3xgr8fRlkILk2URuMARwJo9on/0xWquENZUNPMEk9CmY23e', 'society', 'Official ACM Student Chapter. Fostering technical excellence, workshops, and annual hackathons.', 'teal', NOW() - INTERVAL 14 DAY),
(3, 'Campus Administrator', 'admin@nu.edu.pk', '$2y$10$fDK5wW3xgr8fRlkILk2URuMARwJo9on/0xWquENZUNPMEk9CmY23e', 'admin', 'Central University Administration & Academic Coordination Hub.', 'signature', NOW() - INTERVAL 30 DAY),
(4, 'Zainab Malik', 'zainab@nu.edu.pk', '$2y$10$fDK5wW3xgr8fRlkILk2URuMARwJo9on/0xWquENZUNPMEk9CmY23e', 'student', 'AI & Machine Learning researcher. Working on NLP for regional languages. Coffee and code.', 'sunset', NOW() - INTERVAL 8 DAY),
(5, 'Bilal Khan', 'bilal@nu.edu.pk', '$2y$10$fDK5wW3xgr8fRlkILk2URuMARwJo9on/0xWquENZUNPMEk9CmY23e', 'student', 'Software Engineering senior. President of Competitive Programming Club. Solved 500+ problems.', 'violet', NOW() - INTERVAL 6 DAY),
(6, 'Sara Tariq', 'sara@nu.edu.pk', '$2y$10$fDK5wW3xgr8fRlkILk2URuMARwJo9on/0xWquENZUNPMEk9CmY23e', 'student', 'Computer Systems Engineering sophomore. Robotics lover and embedded hardware geek.', 'graphite', NOW() - INTERVAL 5 DAY),
(7, 'IEEE Student Branch', 'ieee@nu.edu.pk', '$2y$10$fDK5wW3xgr8fRlkILk2URuMARwJo9on/0xWquENZUNPMEk9CmY23e', 'society', 'IEEE FAST Student Branch. Empowering engineers through innovation, seminars, and networking.', 'signature', NOW() - INTERVAL 12 DAY)
ON DUPLICATE KEY UPDATE name=VALUES(name), bio=VALUES(bio);

-- ---------------------------------------------------------------------
-- 2. DEMO POSTS & ANNOUNCEMENTS
-- ---------------------------------------------------------------------
INSERT INTO posts (id, user_id, content, created_at, updated_at, deleted_at) VALUES
(1, 3, 'Welcome to the new academic semester on CampusConnect! Please review the updated library timings and lab schedule on the student portal. #FASTNUCES #CampusConnect', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY, NULL),
(2, 2, 'Registrations are now officially live for FAST Hackathon 2026! 48 hours of building, mentoring, and prizes worth 200,000 PKR. Form link in bio! #Hackathon2026 #ACM #Tech', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY, NULL),
(3, 1, 'Working on our ASPM microblogging project with PHP and MySQL. Super excited to present the working software! #WebDev #CampusConnect #Agile', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY, NULL),
(4, 4, 'Our research paper on Transformers for Low-Resource Languages has been accepted at ACL 2026! Big thanks to the CS faculty for their support. #Research #NLP #AI', NOW() - INTERVAL 20 HOUR, NOW() - INTERVAL 20 HOUR, NULL),
(5, 5, 'Competitive Programming practice session this Saturday at 3 PM in Lab 3. We will cover dynamic programming and graph trees. All batches welcome! #Code #CS', NOW() - INTERVAL 14 HOUR, NOW() - INTERVAL 14 HOUR, NULL),
(6, 6, 'Anyone know if the hardware robotics lab is open over the weekend? Need to calibrate sensor rigs for our FYP prototype. #Robotics #FYP', NOW() - INTERVAL 8 HOUR, NOW() - INTERVAL 8 HOUR, NULL),
(7, 7, 'Workshop Alert: Cloud Computing with AWS & Kubernetes happening next Tuesday! Certified instructor session with hands-on labs. #IEEE #Cloud #AWS', NOW() - INTERVAL 5 HOUR, NOW() - INTERVAL 5 HOUR, NULL),
(8, 1, 'Quick poll for CS batch: who is attending the Hackathon next week? Looking for one more frontend teammate! #Hackathon2026 #WebDev', NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 2 HOUR, NULL)
ON DUPLICATE KEY UPDATE content=VALUES(content);

-- ---------------------------------------------------------------------
-- 3. HASHTAGS & POST_HASHTAGS INDEX
-- ---------------------------------------------------------------------
INSERT INTO hashtags (id, tag, created_at) VALUES
(1, 'fastnuces', NOW() - INTERVAL 3 DAY),
(2, 'campusconnect', NOW() - INTERVAL 3 DAY),
(3, 'hackathon2026', NOW() - INTERVAL 2 DAY),
(4, 'acm', NOW() - INTERVAL 2 DAY),
(5, 'tech', NOW() - INTERVAL 2 DAY),
(6, 'webdev', NOW() - INTERVAL 1 DAY),
(7, 'agile', NOW() - INTERVAL 1 DAY),
(8, 'research', NOW() - INTERVAL 20 HOUR),
(9, 'nlp', NOW() - INTERVAL 20 HOUR),
(10, 'ai', NOW() - INTERVAL 20 HOUR),
(11, 'code', NOW() - INTERVAL 14 HOUR),
(12, 'cs', NOW() - INTERVAL 14 HOUR),
(13, 'robotics', NOW() - INTERVAL 8 HOUR),
(14, 'fyp', NOW() - INTERVAL 8 HOUR),
(15, 'ieee', NOW() - INTERVAL 5 HOUR),
(16, 'cloud', NOW() - INTERVAL 5 HOUR),
(17, 'aws', NOW() - INTERVAL 5 HOUR)
ON DUPLICATE KEY UPDATE tag=VALUES(tag);

INSERT INTO post_hashtags (post_id, hashtag_id, created_at) VALUES
(1, 1, NOW() - INTERVAL 3 DAY), (1, 2, NOW() - INTERVAL 3 DAY),
(2, 3, NOW() - INTERVAL 2 DAY), (2, 4, NOW() - INTERVAL 2 DAY), (2, 5, NOW() - INTERVAL 2 DAY),
(3, 6, NOW() - INTERVAL 1 DAY), (3, 2, NOW() - INTERVAL 1 DAY), (3, 7, NOW() - INTERVAL 1 DAY),
(4, 8, NOW() - INTERVAL 20 HOUR), (4, 9, NOW() - INTERVAL 20 HOUR), (4, 10, NOW() - INTERVAL 20 HOUR),
(5, 11, NOW() - INTERVAL 14 HOUR), (5, 12, NOW() - INTERVAL 14 HOUR),
(6, 13, NOW() - INTERVAL 8 HOUR), (6, 14, NOW() - INTERVAL 8 HOUR),
(7, 15, NOW() - INTERVAL 5 HOUR), (7, 16, NOW() - INTERVAL 5 HOUR), (7, 17, NOW() - INTERVAL 5 HOUR),
(8, 3, NOW() - INTERVAL 2 HOUR), (8, 6, NOW() - INTERVAL 2 HOUR)
ON DUPLICATE KEY UPDATE post_id=VALUES(post_id);

-- ---------------------------------------------------------------------
-- 4. SOCIAL GRAPH (FOLLOWS)
-- ---------------------------------------------------------------------
INSERT INTO follows (follower_id, followed_id, created_at) VALUES
(1, 2, NOW() - INTERVAL 5 DAY),  -- Ali follows ACM
(1, 3, NOW() - INTERVAL 5 DAY),  -- Ali follows Admin
(1, 4, NOW() - INTERVAL 4 DAY),  -- Ali follows Zainab
(1, 5, NOW() - INTERVAL 3 DAY),  -- Ali follows Bilal
(4, 1, NOW() - INTERVAL 3 DAY),  -- Zainab follows Ali
(4, 2, NOW() - INTERVAL 4 DAY),  -- Zainab follows ACM
(5, 1, NOW() - INTERVAL 2 DAY),  -- Bilal follows Ali
(5, 2, NOW() - INTERVAL 3 DAY),  -- Bilal follows ACM
(6, 1, NOW() - INTERVAL 1 DAY),  -- Sara follows Ali
(6, 7, NOW() - INTERVAL 2 DAY)   -- Sara follows IEEE
ON DUPLICATE KEY UPDATE follower_id=VALUES(follower_id);

-- ---------------------------------------------------------------------
-- 5. THREADED REPLIES
-- ---------------------------------------------------------------------
INSERT INTO replies (id, post_id, user_id, content, created_at, updated_at, deleted_at) VALUES
(1, 2, 1, 'Registered our team! Looking forward to the hackathon challenges.', NOW() - INTERVAL 36 HOUR, NOW() - INTERVAL 36 HOUR, NULL),
(2, 2, 4, 'Will there be mentorship sessions for AI tracks during the event?', NOW() - INTERVAL 30 HOUR, NOW() - INTERVAL 30 HOUR, NULL),
(3, 2, 2, '@Zainab Yes! We have industry mentors from leading AI companies on board.', NOW() - INTERVAL 28 HOUR, NOW() - INTERVAL 28 HOUR, NULL),
(4, 4, 1, 'Huge congratulations Zainab! Super proud moment for the department.', NOW() - INTERVAL 18 HOUR, NOW() - INTERVAL 18 HOUR, NULL),
(5, 6, 3, 'Robotics Lab is open on Saturday from 10 AM to 5 PM with lab technician supervision.', NOW() - INTERVAL 6 HOUR, NOW() - INTERVAL 6 HOUR, NULL),
(6, 8, 5, 'Hey Ali, I am down to join! Sent you a message with my GitHub profile.', NOW() - INTERVAL 1 HOUR, NOW() - INTERVAL 1 HOUR, NULL)
ON DUPLICATE KEY UPDATE content=VALUES(content);

-- ---------------------------------------------------------------------
-- 6. LIKES (HEART REACTIONS)
-- ---------------------------------------------------------------------
INSERT INTO likes (user_id, post_id, created_at) VALUES
(1, 1, NOW() - INTERVAL 2 DAY),
(1, 2, NOW() - INTERVAL 2 DAY),
(1, 4, NOW() - INTERVAL 18 HOUR),
(4, 1, NOW() - INTERVAL 2 DAY),
(4, 2, NOW() - INTERVAL 1 DAY),
(4, 3, NOW() - INTERVAL 20 HOUR),
(5, 2, NOW() - INTERVAL 1 DAY),
(5, 3, NOW() - INTERVAL 18 HOUR),
(5, 4, NOW() - INTERVAL 15 HOUR),
(6, 4, NOW() - INTERVAL 12 HOUR),
(6, 7, NOW() - INTERVAL 4 HOUR)
ON DUPLICATE KEY UPDATE user_id=VALUES(user_id);

-- ---------------------------------------------------------------------
-- 7. BOOKMARKS
-- ---------------------------------------------------------------------
INSERT INTO bookmarks (user_id, post_id, created_at) VALUES
(1, 2, NOW() - INTERVAL 1 DAY),  -- Ali saved ACM Hackathon post
(1, 7, NOW() - INTERVAL 3 HOUR), -- Ali saved IEEE Cloud post
(4, 1, NOW() - INTERVAL 2 DAY)   -- Zainab saved Admin Welcome post
ON DUPLICATE KEY UPDATE user_id=VALUES(user_id);

-- ---------------------------------------------------------------------
-- 8. SAMPLE NOTIFICATIONS
-- ---------------------------------------------------------------------
INSERT INTO notifications (id, user_id, actor_id, type, reference_id, message, is_read, created_at) VALUES
(1, 1, 4, 'follow', 4, 'Zainab Malik started following you.', 1, NOW() - INTERVAL 3 DAY),
(2, 1, 5, 'follow', 5, 'Bilal Khan started following you.', 1, NOW() - INTERVAL 2 DAY),
(3, 1, 4, 'like', 3, 'Zainab Malik liked your post.', 1, NOW() - INTERVAL 20 HOUR),
(4, 1, 5, 'like', 3, 'Bilal Khan liked your post.', 1, NOW() - INTERVAL 18 HOUR),
(5, 1, 6, 'follow', 6, 'Sara Tariq started following you.', 0, NOW() - INTERVAL 1 DAY),
(6, 1, 5, 'reply', 8, 'Bilal Khan replied to your post.', 0, NOW() - INTERVAL 1 HOUR),
(7, 2, 1, 'reply', 2, 'Ali Ahmed replied to your post.', 1, NOW() - INTERVAL 36 HOUR),
(8, 4, 1, 'like', 4, 'Ali Ahmed liked your post.', 1, NOW() - INTERVAL 18 HOUR),
(9, 4, 1, 'reply', 4, 'Ali Ahmed replied to your post.', 0, NOW() - INTERVAL 18 HOUR)
ON DUPLICATE KEY UPDATE message=VALUES(message);
