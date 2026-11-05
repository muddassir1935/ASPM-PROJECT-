<?php
/**
 * Post Detail Page (Sprint 2: WBS 2.3.4)
 *
 * Shows a single post with its full metadata. The owner gets an inline
 * edit form and a delete button; admins also see the delete button.
 * Soft-deleted or unknown posts show a friendly "not found" state.
 */
require_once 'config/Database.php';
require_once 'classes/Post.php';
require_once 'classes/Reply.php';
require_once 'classes/Bookmark.php';

require_once 'includes/header.php';

$postId = (int)($_GET['id'] ?? 0);
$post = false;
$replies = [];
$isBookmarked = false;

if ($postId > 0) {
    $db = Database::getInstance()->getConnection();
    $postModel = new Post($db);
    $post = $postModel->getById($postId);
    if ($post) {
        $replyModel = new Reply($db);
        $replies = $replyModel->getByPostId($postId);
        if ($currentUser) {
            $isBookmarked = (new Bookmark($db))->isBookmarked((int)$currentUser['id'], $postId);
        }
    }
}

// Flash messages (e.g. after an edit submitted from this page)
$flashType = $_SESSION['flash_type'] ?? null;
$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_type'], $_SESSION['flash_message']);

$isOwner = $post && $currentUser && (int)$post['user_id'] === (int)$currentUser['id'];
$canDelete = $isOwner || ($currentUser && ($currentUser['role'] ?? '') === 'admin');
?>

<div style="max-width: 850px; margin: 1rem auto;">
    <a href="index.php" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">&larr; Back to Feed</a>

    <?php if ($flashMessage): ?>
        <div style="margin-top: 1rem; padding: 12px 14px; border-radius: 10px; font-size: 0.9rem; font-weight: 500; <?= $flashType === 'success' ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
            <?= htmlspecialchars($flashMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (!$post): ?>
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: var(--shadow-sm); margin-top: 1rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
            <h4 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Post not found</h4>
            <p style="font-size: 0.9rem; color: #64748b; max-width: 320px; margin: 0 auto;">
                This post may have been deleted, or the link is incorrect.
            </p>
        </div>
    <?php else: ?>
        <?php
            $authorInitial = strtoupper(mb_substr(trim($post['author_name']), 0, 1, 'UTF-8'));
            $createdFormatted = date('M j, Y \a\t g:i A', strtotime($post['created_at']));
            $isEdited = !empty($post['updated_at']) && $post['updated_at'] !== $post['created_at'];
            $updatedFormatted = $isEdited ? date('M j, Y \a\t g:i A', strtotime($post['updated_at'])) : null;
        ?>
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.75rem; box-shadow: var(--shadow-sm); margin-top: 1rem;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.15rem; flex-shrink: 0;">
                    <?= htmlspecialchars($authorInitial) ?>
                </div>
                <div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 1.05rem;">
                        <?= htmlspecialchars($post['author_name']) ?>
                        <span style="background: var(--primary-light, #ecfdf5); color: var(--primary); font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.04em; vertical-align: middle;">
                            <?= htmlspecialchars($post['author_role']) ?>
                        </span>
                    </div>
                    <div style="font-size: 0.83rem; color: #64748b;">
                        <?= htmlspecialchars($post['author_email']) ?> &bull; <?= htmlspecialchars($createdFormatted) ?> (<?= htmlspecialchars($post['time_ago']) ?>)
                    </div>
                </div>
            </div>

            <p style="font-size: 1.05rem; color: #1e293b; line-height: 1.65; white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($post['content']) ?></p>

            <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.82rem; color: #64748b;">
                Posted on <?= htmlspecialchars($createdFormatted) ?>
                <?php if ($isEdited): ?>
                    &bull; <span title="Last edited <?= htmlspecialchars($updatedFormatted) ?>">edited</span>
                <?php endif; ?>
            </div>

            <?php if ($currentUser): ?>
                <div style="margin-top: 1rem; display: flex; gap: 14px; align-items: center;">
                    <button type="button" class="btn-bookmark" data-bookmark-toggle data-post-id="<?= (int)$post['id'] ?>" style="font-size: 0.9rem;"><?= $isBookmarked ? 'Saved' : 'Save' ?></button>
                    <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;"><?= (int)($post['reply_count'] ?? 0) ?> repl<?= ((int)($post['reply_count'] ?? 0) === 1) ? 'y' : 'ies' ?></span>
                </div>
            <?php endif; ?>

            <?php if ($isOwner): ?>
                <div style="margin-top: 1rem;">
                    <button type="button" onclick="toggleDetailEdit()" style="background: white; color: var(--primary); border: 1.5px solid var(--primary); padding: 7px 14px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Edit Post</button>
                </div>
                <form id="detail-edit-form" method="POST" action="post_action.php" style="display: none; margin-top: 1rem;">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                    <input type="hidden" name="return" value="<?= (int)$post['id'] ?>">
                    <textarea name="content" rows="4" maxlength="280" required style="width: 100%; padding: 11px 14px; font-size: 0.95rem; border: 1.5px solid #e2e8f0; border-radius: 10px; resize: vertical; outline: none;"><?= htmlspecialchars($post['content']) ?></textarea>
                    <div style="display: flex; gap: 8px; margin-top: 8px;">
                        <button type="submit" style="background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
                        <button type="button" onclick="toggleDetailEdit()" style="background: white; color: #64748b; border: 1.5px solid #e2e8f0; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                    </div>
                </form>
                <script>
                    function toggleDetailEdit() {
                        var form = document.getElementById('detail-edit-form');
                        form.style.display = (form.style.display === 'none') ? 'block' : 'none';
                    }
                </script>
            <?php endif; ?>

            <?php if ($canDelete): ?>
                <form method="POST" action="post_action.php" onsubmit="return confirm('Delete this post? This cannot be undone.');" style="margin-top: 1rem;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                    <button type="submit" style="background: white; color: #dc2626; border: 1.5px solid #fecaca; padding: 7px 14px; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer;">Delete Post</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Replies (Sprint 3, WBS 3.4.2): threaded under the post, oldest first -->
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 1.5rem 0 1rem 0;">
            Replies (<?= count($replies) ?>)
        </h3>

        <?php if ($currentUser): ?>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
                <form method="POST" action="reply_action.php" id="reply-form">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                    <textarea id="reply-content" name="content" rows="3" maxlength="280" placeholder="Write a reply..." style="width: 100%; padding: 11px 14px; font-size: 0.95rem; border: 1.5px solid #e2e8f0; border-radius: 10px; resize: vertical; outline: none;"></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <span id="reply-counter" style="font-size: 0.82rem; color: #64748b; font-weight: 600;">0 / 280</span>
                        <button type="submit" id="reply-submit" disabled style="background: var(--primary); color: white; border: none; padding: 9px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; opacity: 0.5;">Reply</button>
                    </div>
                </form>
            </div>
            <script>
                (function () {
                    var textarea = document.getElementById('reply-content');
                    var counter = document.getElementById('reply-counter');
                    var submit = document.getElementById('reply-submit');
                    var MAX = 280;
                    function refreshCounter() {
                        var len = textarea.value.trim().length;
                        counter.textContent = len + ' / ' + MAX;
                        var invalid = len === 0 || len > MAX;
                        counter.style.color = len > MAX ? '#dc2626' : '#64748b';
                        submit.disabled = invalid;
                        submit.style.opacity = invalid ? '0.5' : '1';
                    }
                    textarea.addEventListener('input', refreshCounter);
                    refreshCounter();
                })();
            </script>
        <?php else: ?>
            <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1rem;">
                <a href="login.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Log in</a> to reply.
            </p>
        <?php endif; ?>

        <?php if (empty($replies)): ?>
            <p style="font-size: 0.9rem; color: #64748b;">No replies yet. Be the first to reply.</p>
        <?php else: ?>
            <?php foreach ($replies as $reply): ?>
                <?php
                    $replyInitial = strtoupper(mb_substr(trim($reply['author_name']), 0, 1, 'UTF-8'));
                    $replyIsOwner = $currentUser && (int)$reply['user_id'] === (int)$currentUser['id'];
                    $replyCanDelete = $replyIsOwner || ($currentUser && ($currentUser['role'] ?? '') === 'admin');
                ?>
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.1rem 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 0.75rem; margin-left: 1.5rem; border-left: 3px solid var(--primary);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; flex-shrink: 0;">
                            <?= htmlspecialchars($replyInitial) ?>
                        </div>
                        <div>
                            <a href="profile.php?id=<?= (int)$reply['user_id'] ?>" style="font-weight: 700; color: #0f172a; font-size: 0.9rem; text-decoration: none;"><?= htmlspecialchars($reply['author_name']) ?></a>
                            <span style="font-size: 0.78rem; color: #64748b; margin-left: 6px;"><?= htmlspecialchars(date('M j, Y \a\t g:i A', strtotime($reply['created_at']))) ?></span>
                        </div>
                    </div>
                    <p style="font-size: 0.93rem; color: #1e293b; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($reply['content']) ?></p>
                    <?php if ($replyCanDelete): ?>
                        <form method="POST" action="reply_action.php" onsubmit="return confirm('Delete this reply?');" style="margin-top: 8px;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="reply_id" value="<?= (int)$reply['id'] ?>">
                            <input type="hidden" name="return_post_id" value="<?= (int)$post['id'] ?>">
                            <button type="submit" style="background: none; border: none; color: #dc2626; font-weight: 600; font-size: 0.82rem; cursor: pointer; padding: 0;">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
