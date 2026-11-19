-- Migration 6: Post Reporting & Moderation Queue (Sprint 5)
-- CampusConnect - Agile Software Project Management
-- Creates the reports table for user flagging of policy-violating posts.

CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    post_id INT NOT NULL,
    reason ENUM('spam','harassment','misinformation','inappropriate','violence','other') NOT NULL,
    detail TEXT DEFAULT NULL,
    status ENUM('pending','dismissed','actioned') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    UNIQUE KEY uq_reporter_post (reporter_id, post_id),
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_post FOREIGN KEY (post_id)
        REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_reviewer FOREIGN KEY (reviewed_by)
        REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_reports_status ON reports(status);
CREATE INDEX idx_reports_post ON reports(post_id);
