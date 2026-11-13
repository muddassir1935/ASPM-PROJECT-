<?php
/**
 * Re-seed the demo database with clean fixtures (Sprint 5, H4/M-18).
 *
 * Wipes ALL data — users, posts, replies, follows, likes, bookmarks,
 * hashtags, notifications, sessions, login attempts — then inserts
 * the canonical 7-user / 8-post demo dataset (the same accounts and
 * content as db/seed.sql; password for every account: StrongPass123!).
 *
 * The test suites used to pollute the demo DB (200+ junk users), and
 * the UI audit found that junk all over the demo surface (trending
 * #tech_1794493163_7469, users named "Followed Fred 17944…"). Run
 * this before the Sunday demo to start from a clean database.
 *
 * CLI only:  php db/reseed_demo.php --confirm
 *
 * Works on MySQL and on the SQLite fallback (plain SQL with bound
 * parameters — no MySQL-only syntax, unlike db/seed.sql). Never run
 * against a database holding real data: the wipe is irreversible.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}
if (!in_array('--confirm', $argv ?? [], true)) {
    fwrite(STDERR, "This wipes ALL data in the CampusConnect database and re-seeds it.\n");
    fwrite(STDERR, "Usage: php db/reseed_demo.php --confirm\n");
    exit(1);
}

require_once __DIR__ . '/../config/Database.php';

// Bcrypt hash of the shared demo password StrongPass123! (same as db/seed.sql).
const DEMO_PASSWORD_HASH = '$2y$10$fDK5wW3xgr8fRlkILk2URuMARwJo9on/0xWquENZUNPMEk9CmY23e';

$ago = static fn(int $seconds): string => date('Y-m-d H:i:s', time() - $seconds);
$DAY = 86400;
$HOUR = 3600;

try {
    $db = Database::getInstance()->getConnection();
    $driver = Database::getInstance()->getDriver();
    echo "Driver: {$driver}\n";

    // --- 1. Wipe (children first, so foreign keys never complain) ---
    foreach (['post_hashtags', 'notifications', 'likes', 'bookmarks', 'replies', 'follows', 'hashtags', 'sessions', 'login_attempts', 'posts', 'users'] as $table) {
        $db->exec("DELETE FROM {$table}");
    }
    echo "Wiped all tables.\n";

    // --- 2. Users (same 7 accounts as db/seed.sql) ---
    $users = [
        [1, 'Ali Ahmed', 'student@nu.edu.pk', 'student', 'BS Computer Science (Junior) @ FAST-NUCES. Full-stack developer & open-source enthusiast.', 'cobalt', 10 * $DAY],
        [2, 'ACM NUCES Chapter', 'society@nu.edu.pk', 'society_admin', 'Official ACM Student Chapter. Fostering technical excellence, workshops, and annual hackathons.', 'teal', 14 * $DAY],
        [3, 'Campus Administrator', 'admin@nu.edu.pk', 'system_admin', 'Central University Administration & Academic Coordination Hub.', 'signature', 30 * $DAY],
        [4, 'Zainab Malik', 'zainab@nu.edu.pk', 'student', 'AI & Machine Learning researcher. Working on NLP for regional languages. Coffee and code.', 'sunset', 8 * $DAY],
        [5, 'Bilal Khan', 'bilal@nu.edu.pk', 'student', 'Software Engineering senior. President of Competitive Programming Club. Solved 500+ problems.', 'violet', 6 * $DAY],
        [6, 'Sara Tariq', 'sara@nu.edu.pk', 'student', 'Computer Systems Engineering sophomore. Robotics lover and embedded hardware geek.', 'graphite', 5 * $DAY],
        [7, 'IEEE Student Branch', 'ieee@nu.edu.pk', 'society_admin', 'IEEE FAST Student Branch. Empowering engineers through innovation, seminars, and networking.', 'signature', 12 * $DAY],
    ];
    $stmt = $db->prepare("INSERT INTO users (id, name, email, password_hash, role, bio, cover_style, created_at) VALUES (:id, :name, :email, :hash, :role, :bio, :cover, :created)");
    foreach ($users as [$id, $name, $email, $role, $bio, $cover, $age]) {
        $stmt->execute([':id' => $id, ':name' => $name, ':email' => $email, ':hash' => DEMO_PASSWORD_HASH, ':role' => $role, ':bio' => $bio, ':cover' => $cover, ':created' => $ago($age)]);
    }
    echo "Seeded " . count($users) . " users.\n";

    // --- 3. Posts (2 announcements + 6 posts) ---
    $posts = [
        [1, 3, 'Welcome to the new academic semester on CampusConnect! Please review the updated library timings and lab schedule on the student portal. #FASTNUCES #CampusConnect', 1, 3 * $DAY],
        [2, 2, 'Registrations are now officially live for FAST Hackathon 2026! 48 hours of building, mentoring, and prizes worth 200,000 PKR. Form link in bio! #Hackathon2026 #ACM #Tech', 1, 2 * $DAY],
        [3, 1, 'Working on our ASPM microblogging project with PHP and MySQL. Super excited to present the working software! #WebDev #CampusConnect #Agile', 0, 1 * $DAY],
        [4, 4, 'Our research paper on Transformers for Low-Resource Languages has been accepted at ACL 2026! Big thanks to the CS faculty for their support. #Research #NLP #AI', 0, 20 * $HOUR],
        [5, 5, 'Competitive Programming practice session this Saturday at 3 PM in Lab 3. We will cover dynamic programming and graph trees. All batches welcome! #Code #CS', 0, 14 * $HOUR],
        [6, 6, 'Anyone know if the hardware robotics lab is open over the weekend? Need to calibrate sensor rigs for our FYP prototype. #Robotics #FYP', 0, 8 * $HOUR],
        [7, 7, 'Workshop Alert: Cloud Computing with AWS & Kubernetes happening next Tuesday! Certified instructor session with hands-on labs. #IEEE #Cloud #AWS', 0, 5 * $HOUR],
        [8, 1, 'Quick poll for CS batch: who is attending the Hackathon next week? Looking for one more frontend teammate! #Hackathon2026 #WebDev', 0, 2 * $HOUR],
    ];
    $stmt = $db->prepare("INSERT INTO posts (id, user_id, content, is_announcement, created_at, updated_at, deleted_at) VALUES (:id, :uid, :content, :ann, :created, :created, NULL)");
    foreach ($posts as [$id, $uid, $content, $ann, $age]) {
        $stmt->execute([':id' => $id, ':uid' => $uid, ':content' => $content, ':ann' => $ann, ':created' => $ago($age)]);
    }
    echo "Seeded " . count($posts) . " posts.\n";

    // --- 4. Hashtags + post links ---
    $tags = [
        [1, 'fastnuces', 3 * $DAY], [2, 'campusconnect', 3 * $DAY],
        [3, 'hackathon2026', 2 * $DAY], [4, 'acm', 2 * $DAY], [5, 'tech', 2 * $DAY],
        [6, 'webdev', 1 * $DAY], [7, 'agile', 1 * $DAY],
        [8, 'research', 20 * $HOUR], [9, 'nlp', 20 * $HOUR], [10, 'ai', 20 * $HOUR],
        [11, 'code', 14 * $HOUR], [12, 'cs', 14 * $HOUR],
        [13, 'robotics', 8 * $HOUR], [14, 'fyp', 8 * $HOUR],
        [15, 'ieee', 5 * $HOUR], [16, 'cloud', 5 * $HOUR], [17, 'aws', 5 * $HOUR],
    ];
    $stmt = $db->prepare("INSERT INTO hashtags (id, tag, created_at) VALUES (:id, :tag, :created)");
    foreach ($tags as [$id, $tag, $age]) {
        $stmt->execute([':id' => $id, ':tag' => $tag, ':created' => $ago($age)]);
    }
    $postTags = [
        [1, 1, 3 * $DAY], [1, 2, 3 * $DAY],
        [2, 3, 2 * $DAY], [2, 4, 2 * $DAY], [2, 5, 2 * $DAY],
        [3, 6, 1 * $DAY], [3, 2, 1 * $DAY], [3, 7, 1 * $DAY],
        [4, 8, 20 * $HOUR], [4, 9, 20 * $HOUR], [4, 10, 20 * $HOUR],
        [5, 11, 14 * $HOUR], [5, 12, 14 * $HOUR],
        [6, 13, 8 * $HOUR], [6, 14, 8 * $HOUR],
        [7, 15, 5 * $HOUR], [7, 16, 5 * $HOUR], [7, 17, 5 * $HOUR],
        [8, 3, 2 * $HOUR], [8, 6, 2 * $HOUR],
    ];
    $stmt = $db->prepare("INSERT INTO post_hashtags (post_id, hashtag_id, created_at) VALUES (:pid, :tid, :created)");
    foreach ($postTags as [$pid, $tid, $age]) {
        $stmt->execute([':pid' => $pid, ':tid' => $tid, ':created' => $ago($age)]);
    }
    echo "Seeded " . count($tags) . " hashtags.\n";

    // --- 5. Follows, replies, likes, bookmarks, notifications ---
    $follows = [[1, 2, 5 * $DAY], [1, 3, 5 * $DAY], [1, 4, 4 * $DAY], [1, 5, 3 * $DAY], [4, 1, 3 * $DAY], [4, 2, 4 * $DAY], [5, 1, 2 * $DAY], [5, 2, 3 * $DAY], [6, 1, 1 * $DAY], [6, 7, 2 * $DAY]];
    $stmt = $db->prepare("INSERT INTO follows (follower_id, followed_id, created_at) VALUES (:a, :b, :created)");
    foreach ($follows as [$a, $b, $age]) {
        $stmt->execute([':a' => $a, ':b' => $b, ':created' => $ago($age)]);
    }

    $replies = [
        [1, 2, 1, 'Registered our team! Looking forward to the hackathon challenges.', 36 * $HOUR],
        [2, 2, 4, 'Will there be mentorship sessions for AI tracks during the event?', 30 * $HOUR],
        [3, 2, 2, '@Zainab Yes! We have industry mentors from leading AI companies on board.', 28 * $HOUR],
        [4, 4, 1, 'Huge congratulations Zainab! Super proud moment for the department.', 18 * $HOUR],
        [5, 6, 3, 'Robotics Lab is open on Saturday from 10 AM to 5 PM with lab technician supervision.', 6 * $HOUR],
        [6, 8, 5, 'Hey Ali, I am down to join! Sent you a message with my GitHub profile.', 1 * $HOUR],
    ];
    $stmt = $db->prepare("INSERT INTO replies (id, post_id, user_id, content, created_at, updated_at, deleted_at) VALUES (:id, :pid, :uid, :content, :created, :created, NULL)");
    foreach ($replies as [$id, $pid, $uid, $content, $age]) {
        $stmt->execute([':id' => $id, ':pid' => $pid, ':uid' => $uid, ':content' => $content, ':created' => $ago($age)]);
    }

    $likes = [[1, 1, 2 * $DAY], [1, 2, 2 * $DAY], [1, 4, 18 * $HOUR], [4, 1, 2 * $DAY], [4, 2, 1 * $DAY], [4, 3, 20 * $HOUR], [5, 2, 1 * $DAY], [5, 3, 18 * $HOUR], [5, 4, 15 * $HOUR], [6, 4, 12 * $HOUR], [6, 7, 4 * $HOUR]];
    $stmt = $db->prepare("INSERT INTO likes (user_id, post_id, created_at) VALUES (:uid, :pid, :created)");
    foreach ($likes as [$uid, $pid, $age]) {
        $stmt->execute([':uid' => $uid, ':pid' => $pid, ':created' => $ago($age)]);
    }

    $bookmarks = [[1, 2, 1 * $DAY], [1, 7, 3 * $HOUR], [4, 1, 2 * $DAY]];
    $stmt = $db->prepare("INSERT INTO bookmarks (user_id, post_id, created_at) VALUES (:uid, :pid, :created)");
    foreach ($bookmarks as [$uid, $pid, $age]) {
        $stmt->execute([':uid' => $uid, ':pid' => $pid, ':created' => $ago($age)]);
    }

    $notifications = [
        [1, 1, 4, 'follow', 4, 'Zainab Malik started following you.', 1, 3 * $DAY],
        [2, 1, 5, 'follow', 5, 'Bilal Khan started following you.', 1, 2 * $DAY],
        [3, 1, 4, 'like', 3, 'Zainab Malik liked your post.', 1, 20 * $HOUR],
        [4, 1, 5, 'like', 3, 'Bilal Khan liked your post.', 1, 18 * $HOUR],
        [5, 1, 6, 'follow', 6, 'Sara Tariq started following you.', 0, 1 * $DAY],
        [6, 1, 5, 'reply', 8, 'Bilal Khan replied to your post.', 0, 1 * $HOUR],
        [7, 2, 1, 'reply', 2, 'Ali Ahmed replied to your post.', 1, 36 * $HOUR],
        [8, 4, 1, 'like', 4, 'Ali Ahmed liked your post.', 1, 18 * $HOUR],
        [9, 4, 1, 'reply', 4, 'Ali Ahmed replied to your post.', 0, 18 * $HOUR],
    ];
    $stmt = $db->prepare("INSERT INTO notifications (id, user_id, actor_id, type, reference_id, message, is_read, created_at) VALUES (:id, :uid, :actor, :type, :ref, :msg, :read, :created)");
    foreach ($notifications as [$id, $uid, $actor, $type, $ref, $msg, $read, $age]) {
        $stmt->execute([':id' => $id, ':uid' => $uid, ':actor' => $actor, ':type' => $type, ':ref' => $ref, ':msg' => $msg, ':read' => $read, ':created' => $ago($age)]);
    }
    echo "Seeded " . count($follows) . " follows, " . count($replies) . " replies, " . count($likes) . " likes, " . count($bookmarks) . " bookmarks, " . count($notifications) . " notifications.\n";
    echo "Done. Demo accounts (password for all: StrongPass123!): student@nu.edu.pk, admin@nu.edu.pk (system admin), society@nu.edu.pk.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Reseed failed: " . $e->getMessage() . "\n");
    exit(1);
}
