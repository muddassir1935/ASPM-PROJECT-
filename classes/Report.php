<?php
/**
 * Class Report
 *
 * Sprint 5: Post Reporting & Admin Moderation Queue
 *
 * Allows users to report policy-violating posts with structured reasons and
 * optional context, and allows system admins to review the queue, dismissing
 * reports or actioning them via post soft-deletion.
 */
require_once __DIR__ . '/Post.php';
require_once __DIR__ . '/Notification.php';

class Report {
    public const REASON_SPAM = 'spam';
    public const REASON_HARASSMENT = 'harassment';
    public const REASON_MISINFORMATION = 'misinformation';
    public const REASON_INAPPROPRIATE = 'inappropriate';
    public const REASON_VIOLENCE = 'violence';
    public const REASON_OTHER = 'other';

    public const REASONS = [
        self::REASON_SPAM,
        self::REASON_HARASSMENT,
        self::REASON_MISINFORMATION,
        self::REASON_INAPPROPRIATE,
        self::REASON_VIOLENCE,
        self::REASON_OTHER,
    ];

    public const REASON_LABELS = [
        self::REASON_SPAM => 'Spam',
        self::REASON_HARASSMENT => 'Harassment or hate speech',
        self::REASON_MISINFORMATION => 'Misinformation',
        self::REASON_INAPPROPRIATE => 'Sexually explicit or inappropriate content',
        self::REASON_VIOLENCE => 'Violence or threats',
        self::REASON_OTHER => 'Other',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_ACTIONED = 'actioned';

    public const MAX_DETAIL_LENGTH = 500;

    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Check if a reason string is in the fixed whitelist.
     */
    public static function isValidReason(string $reason): bool {
        return in_array($reason, self::REASONS, true);
    }

    /**
     * Human-readable label for a reason key.
     */
    public static function displayReason(string $reason): string {
        return self::REASON_LABELS[$reason] ?? 'Other';
    }

    /**
     * Validate report parameters according to domain rules:
     * - reason must belong to the whitelist
     * - detail (if supplied) must not exceed 500 characters
     */
    public static function validate(string $reason, ?string $detail = null): array {
        if (!self::isValidReason($reason)) {
            return [
                'valid' => false,
                'error' => 'Please select a valid report reason.',
            ];
        }

        if ($detail !== null && mb_strlen(trim($detail), 'UTF-8') > self::MAX_DETAIL_LENGTH) {
            return [
                'valid' => false,
                'error' => 'Report detail cannot exceed ' . self::MAX_DETAIL_LENGTH . ' characters.',
            ];
        }

        return [
            'valid' => true,
            'error' => null,
        ];
    }

    /**
     * Has this reporter already reported this post?
     */
    public function alreadyReported(int $reporterId, int $postId): bool {
        if ($reporterId <= 0 || $postId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare(
            "SELECT id FROM reports WHERE reporter_id = :reporter_id AND post_id = :post_id LIMIT 1"
        );
        $stmt->execute([
            ':reporter_id' => $reporterId,
            ':post_id' => $postId,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Retrieve all post IDs reported by a user (for bulk badge / button states).
     *
     * @param int $reporterId
     * @return int[]
     */
    public function getReportedPostIds(int $reporterId): array {
        if ($reporterId <= 0) {
            return [];
        }

        $stmt = $this->db->prepare("SELECT post_id FROM reports WHERE reporter_id = :reporter_id");
        $stmt->execute([':reporter_id' => $reporterId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Create a new report against a post.
     * Enforces:
     * - valid parameters and reason whitelist
     * - post existence and active status (not soft-deleted)
     * - no self-reporting
     * - one report per user per post
     */
    public function create(int $reporterId, int $postId, string $reason, ?string $detail = null): array {
        if ($reporterId <= 0 || $postId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid reporter or post ID.',
                'report_id' => null,
            ];
        }

        $validation = self::validate($reason, $detail);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['error'],
                'report_id' => null,
            ];
        }

        // Verify post existence and author (cannot report own post or deleted post)
        $postStmt = $this->db->prepare(
            "SELECT id, user_id, deleted_at FROM posts WHERE id = :post_id LIMIT 1"
        );
        $postStmt->execute([':post_id' => $postId]);
        $post = $postStmt->fetch(PDO::FETCH_ASSOC);

        if (!$post || $post['deleted_at'] !== null) {
            return [
                'success' => false,
                'message' => 'Post not found or has been deleted.',
                'report_id' => null,
            ];
        }

        if ((int)$post['user_id'] === $reporterId) {
            return [
                'success' => false,
                'message' => 'You cannot report your own post.',
                'report_id' => null,
            ];
        }

        if ($this->alreadyReported($reporterId, $postId)) {
            return [
                'success' => false,
                'message' => 'You have already reported this post.',
                'report_id' => null,
            ];
        }

        $cleanDetail = ($detail !== null && trim($detail) !== '') ? trim($detail) : null;

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO reports (reporter_id, post_id, reason, detail, status, created_at)
                 VALUES (:reporter_id, :post_id, :reason, :detail, :status, CURRENT_TIMESTAMP)"
            );
            $stmt->execute([
                ':reporter_id' => $reporterId,
                ':post_id' => $postId,
                ':reason' => $reason,
                ':detail' => $cleanDetail,
                ':status' => self::STATUS_PENDING,
            ]);

            $reportId = (int)$this->db->lastInsertId();

            return [
                'success' => true,
                'message' => 'Report submitted successfully. Thank you for helping keep CampusConnect safe.',
                'report_id' => $reportId,
            ];
        } catch (PDOException $e) {
            // Check duplicate entry violation
            if ($this->alreadyReported($reporterId, $postId)) {
                return [
                    'success' => false,
                    'message' => 'You have already reported this post.',
                    'report_id' => null,
                ];
            }
            error_log("Report creation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to submit report. Please try again.',
                'report_id' => null,
            ];
        }
    }

    /**
     * Retrieve a single report by ID.
     */
    public function getById(int $reportId): ?array {
        if ($reportId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT r.*,
                    p.content AS post_content, p.user_id AS post_author_id, p.deleted_at AS post_deleted_at,
                    u_author.name AS post_author_name, u_author.email AS post_author_email,
                    u_rep.name AS reporter_name, u_rep.email AS reporter_email
             FROM reports r
             JOIN posts p ON r.post_id = p.id
             JOIN users u_author ON p.user_id = u_author.id
             JOIN users u_rep ON r.reporter_id = u_rep.id
             WHERE r.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $reportId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $row['time_ago'] = Post::formatTimeAgo($row['created_at']);
            if (!empty($row['reviewed_at'])) {
                $row['reviewed_time_ago'] = Post::formatTimeAgo($row['reviewed_at']);
            }
        }

        return $row ?: null;
    }

    /**
     * Count total reports for a specific post.
     */
    public function countForPost(int $postId): int {
        if ($postId <= 0) {
            return 0;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM reports WHERE post_id = :post_id");
        $stmt->execute([':post_id' => $postId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count all pending reports waiting for moderation review.
     */
    public function countPending(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count all reviewed reports (dismissed or actioned).
     */
    public function countReviewed(): int {
        $stmt = $this->db->query("SELECT COUNT(*) FROM reports WHERE status IN ('dismissed', 'actioned')");
        return (int)$stmt->fetchColumn();
    }

    /**
     * List pending reports ordered newest-first.
     * Includes post snippet, post author, reporter, report reason/detail,
     * and total report count for that post.
     */
    public function listPending(int $limit = 50, int $offset = 0): array {
        $sql = "SELECT r.id, r.reporter_id, r.post_id, r.reason, r.detail, r.status, r.created_at,
                       p.content AS post_content, p.user_id AS post_author_id, p.deleted_at AS post_deleted_at,
                       u_author.name AS post_author_name, u_author.email AS post_author_email, u_author.role AS post_author_role,
                       u_rep.name AS reporter_name, u_rep.email AS reporter_email,
                       (SELECT COUNT(*) FROM reports r2 WHERE r2.post_id = r.post_id) AS post_report_count
                FROM reports r
                JOIN posts p ON r.post_id = p.id
                JOIN users u_author ON p.user_id = u_author.id
                JOIN users u_rep ON r.reporter_id = u_rep.id
                WHERE r.status = 'pending'
                ORDER BY r.created_at DESC, r.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['time_ago'] = Post::formatTimeAgo($row['created_at']);
            $row['reason_label'] = self::displayReason($row['reason']);
        }
        unset($row);

        return $rows;
    }

    /**
     * List reviewed reports (dismissed or actioned) ordered newest-first.
     */
    public function listReviewed(int $limit = 50, int $offset = 0): array {
        $sql = "SELECT r.id, r.reporter_id, r.post_id, r.reason, r.detail, r.status, r.created_at,
                       r.reviewed_at, r.reviewed_by,
                       p.content AS post_content, p.user_id AS post_author_id, p.deleted_at AS post_deleted_at,
                       u_author.name AS post_author_name,
                       u_rep.name AS reporter_name,
                       u_rev.name AS reviewer_name
                FROM reports r
                JOIN posts p ON r.post_id = p.id
                JOIN users u_author ON p.user_id = u_author.id
                JOIN users u_rep ON r.reporter_id = u_rep.id
                LEFT JOIN users u_rev ON r.reviewed_by = u_rev.id
                WHERE r.status IN ('dismissed', 'actioned')
                ORDER BY r.reviewed_at DESC, r.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['time_ago'] = Post::formatTimeAgo($row['created_at']);
            $row['reviewed_time_ago'] = $row['reviewed_at'] ? Post::formatTimeAgo($row['reviewed_at']) : 'recently';
            $row['reason_label'] = self::displayReason($row['reason']);
        }
        unset($row);

        return $rows;
    }

    /**
     * Admin review action:
     * - 'dismiss': marks the report as 'dismissed', post remains untouched.
     * - 'action' / 'delete': soft-deletes the post via Post::delete and marks
     *   all pending reports for this post as 'actioned'.
     *
     * @param int $reportId Report to review
     * @param string $action 'dismiss' | 'action' | 'delete'
     * @param int $reviewerId User ID of reviewing administrator
     * @return array ['success' => bool, 'message' => string]
     */
    public function review(int $reportId, string $action, int $reviewerId): array {
        if ($reportId <= 0 || $reviewerId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid report or reviewer ID.',
            ];
        }

        $stmt = $this->db->prepare(
            "SELECT r.*, p.id AS p_id, p.user_id AS post_author_id, p.deleted_at
             FROM reports r
             JOIN posts p ON r.post_id = p.id
             WHERE r.id = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $reportId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$report) {
            return [
                'success' => false,
                'message' => 'Report not found.',
            ];
        }

        if ($report['status'] !== self::STATUS_PENDING) {
            return [
                'success' => false,
                'message' => 'This report has already been reviewed.',
            ];
        }

        $action = strtolower(trim($action));

        if ($action === 'dismiss') {
            $update = $this->db->prepare(
                "UPDATE reports
                 SET status = :status, reviewed_at = CURRENT_TIMESTAMP, reviewed_by = :reviewer_id
                 WHERE id = :id"
            );
            $update->execute([
                ':status' => self::STATUS_DISMISSED,
                ':reviewer_id' => $reviewerId,
                ':id' => $reportId,
            ]);

            (new Notification($this->db))->create(
                (int)$report['reporter_id'],
                'moderation',
                null,
                'Your report has been reviewed by administration.'
            );

            return [
                'success' => true,
                'message' => 'Report has been dismissed.',
            ];
        }

        if ($action === 'action' || $action === 'delete') {
            $postModel = new Post($this->db);
            $postId = (int)$report['post_id'];

            $reportersStmt = $this->db->prepare(
                "SELECT reporter_id FROM reports WHERE post_id = :post_id AND status = :status"
            );
            $reportersStmt->execute([':post_id' => $postId, ':status' => self::STATUS_PENDING]);
            $reporterIds = array_unique(array_map('intval', $reportersStmt->fetchAll(PDO::FETCH_COLUMN)));

            // Soft-delete the post through the existing post model delete path
            $deleted = $postModel->delete($postId, $reviewerId, true);
            if (!$deleted) {
                return [
                    'success' => false,
                    'message' => 'The reported post could not be removed.',
                ];
            }

            // Mark this report (and any other pending reports for the same post) as actioned
            $update = $this->db->prepare(
                "UPDATE reports
                 SET status = :status, reviewed_at = CURRENT_TIMESTAMP, reviewed_by = :reviewer_id
                 WHERE post_id = :post_id AND status = :pending_status"
            );
            $update->execute([
                ':status' => self::STATUS_ACTIONED,
                ':reviewer_id' => $reviewerId,
                ':post_id' => $postId,
                ':pending_status' => self::STATUS_PENDING,
            ]);

            $notifications = new Notification($this->db);
            $notifications->create(
                (int)$report['post_author_id'],
                'moderation',
                null,
                'Your post was removed by administration for violating campus policies.'
            );
            foreach ($reporterIds as $reporterId) {
                $notifications->create(
                    $reporterId,
                    'moderation',
                    null,
                    'Your report has been reviewed by administration.'
                );
            }

            return [
                'success' => true,
                'message' => 'Post soft-deleted and report actioned successfully.',
            ];
        }

        return [
            'success' => false,
            'message' => 'Unknown review action. Allowed actions: dismiss, action.',
        ];
    }
}
