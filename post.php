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
    $viewerId = $currentUser ? (int)$currentUser['id'] : 0;
    $post = $postModel->getById($postId, $viewerId);
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
$canDelete = $isOwner || Auth::can('post.delete_any');
?>

<div class="narrow-wrap">
    <a href="index.php" class="back-link">&larr; Back to Feed</a>

    <?php if ($flashMessage): ?>
        <div class="alert <?= $flashType === 'success' ? 'alert-success' : 'alert-error' ?>" style="margin-top: 1rem;">
            <div><?= htmlspecialchars($flashMessage) ?></div>
        </div>
    <?php endif; ?>

    <?php if (!$post): ?>
        <div class="empty-state" style="margin-top: 1rem;">
            <span class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/></svg></span>
            <h4 class="empty-title">Post not found</h4>
            <p class="empty-text">
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
        <?php $detailIsAnnouncement = !empty($post['is_announcement']); ?>
        <div class="detail-card">
            <?php if ($detailIsAnnouncement): ?>
                <div class="announce-line"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 10.5v3h2.5L18 19V5L6.5 10.5H4Z"/><path d="M8 14v4.5"/></svg>Announcement</div>
            <?php endif; ?>
            <div class="detail-head">
                <span class="avatar-md<?= avatarToneClass((int)$post['user_id'], (string)($post['author_role'] ?? '')) ?>"><?= htmlspecialchars($authorInitial) ?></span>
                <div>
                    <div class="detail-author">
                        <?= htmlspecialchars($post['author_name']) ?>
                    </div>
                    <div class="detail-meta">
                        <?= htmlspecialchars(Authorization::displayLabel($post['author_role'] ?? 'student')) ?> &middot; <?= htmlspecialchars($post['time_ago']) ?>
                    </div>
                </div>
            </div>

            <p class="detail-content"><?= Hashtag::renderHashtagLinks(htmlspecialchars($post['content'])) ?></p>

            <div class="detail-foot">
                Posted on <?= htmlspecialchars($createdFormatted) ?>
                <?php if ($isEdited): ?>
                    &middot; <span title="Last edited <?= htmlspecialchars($updatedFormatted) ?>">edited</span>
                <?php endif; ?>
            </div>

            <?php if ($currentUser): ?>
                <div class="detail-actions">
                    <span class="action-stat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z"/></svg><?= (int)($post['reply_count'] ?? 0) ?> repl<?= ((int)($post['reply_count'] ?? 0) === 1) ? 'y' : 'ies' ?></span>
                    <button type="button" class="action-like <?= !empty($post['is_liked']) ? 'is-liked' : '' ?>" data-like-toggle data-post-id="<?= (int)$post['id'] ?>" aria-label="Like post"><span class="action-ic"><svg viewBox="0 0 24 24" fill="<?= !empty($post['is_liked']) ? '#e0245e' : 'none' ?>" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg></span><span class="like-count"><?= (int)($post['like_count'] ?? 0) ?></span></button>
                    <span class="spacer" style="flex: 1;"></span>
                    <button type="button" class="btn-bookmark" data-bookmark-toggle data-post-id="<?= (int)$post['id'] ?>"><?= $isBookmarked ? 'Saved' : 'Save' ?></button>
                </div>
            <?php endif; ?>

            <?php if ($isOwner || $canDelete): ?>
                <div class="post-owner-actions" style="margin-top: 1rem; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <?php if ($isOwner): ?>
                        <button type="button" class="btn-outline" onclick="toggleDetailEdit()">Edit Post</button>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                        <form method="POST" action="post_action.php" onsubmit="return confirm('Delete this post? This cannot be undone.');" style="margin: 0;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                            <button type="submit" class="btn-danger-outline">Delete Post</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($isOwner): ?>
                <form id="detail-edit-form" method="POST" action="post_action.php" class="edit-form" style="display: none; margin-top: 1rem;">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                    <input type="hidden" name="return" value="<?= (int)$post['id'] ?>">
                    <textarea name="content" class="form-control" rows="4" maxlength="280" required><?= htmlspecialchars($post['content']) ?></textarea>
                    <div class="edit-form-actions">
                        <button type="submit" class="btn-primary">Save Changes</button>
                        <button type="button" class="btn-cancel" onclick="toggleDetailEdit()">Cancel</button>
                    </div>
                </form>
                <script>
                    function toggleDetailEdit() {
                        var form = document.getElementById('detail-edit-form');
                        form.style.display = (form.style.display === 'none') ? 'block' : 'none';
                    }
                </script>
            <?php endif; ?>
        </div>

        <!-- Replies (Sprint 3, WBS 3.4.2): threaded under the post, oldest first -->
        <h3 class="section-title">
            Replies (<?= count($replies) ?>)
        </h3>

        <?php if ($currentUser): ?>
            <div class="compose-card">
                <form method="POST" action="reply_action.php" id="reply-form">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                    <div class="compose-flex">
                        <span class="avatar-md<?= avatarToneClass((int)$currentUser['id'], (string)$currentUser['role']) ?>"><?= htmlspecialchars(strtoupper(mb_substr(trim($currentUser['name']), 0, 1, 'UTF-8'))) ?></span>
                        <textarea id="reply-content" class="form-control" name="content" rows="3" maxlength="280" placeholder="Write a reply..."></textarea>
                    </div>
                    <div class="compose-row">
                        <span id="reply-counter" class="char-counter">0 / 280</span>
                        <button type="submit" id="reply-submit" class="btn-primary" disabled>Reply</button>
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
                        counter.style.color = len > MAX ? '#D92D20' : '#536471';
                        submit.disabled = invalid;
                    }
                    textarea.addEventListener('input', refreshCounter);
                    refreshCounter();
                })();
            </script>
        <?php else: ?>
            <p class="login-to-reply">
                <a href="login.php">Log in</a> to reply.
            </p>
        <?php endif; ?>

        <?php if (empty($replies)): ?>
            <p class="login-to-reply">No replies yet. Be the first to reply.</p>
        <?php else: ?>
            <?php foreach ($replies as $reply): ?>
                <?php
                    $replyInitial = strtoupper(mb_substr(trim($reply['author_name']), 0, 1, 'UTF-8'));
                    $replyIsOwner = $currentUser && (int)$reply['user_id'] === (int)$currentUser['id'];
                    $replyCanDelete = $replyIsOwner || Auth::can('reply.delete_any');
                ?>
                <div class="reply-card">
                    <span class="avatar-sm<?= avatarToneClass((int)$reply['user_id']) ?>"><?= htmlspecialchars($replyInitial) ?></span>
                    <div class="reply-body">
                        <div class="reply-head">
                            <a class="reply-author" href="profile.php?id=<?= (int)$reply['user_id'] ?>"><?= htmlspecialchars($reply['author_name']) ?></a>
                            <span class="reply-time">&middot; <?= htmlspecialchars(date('M j, g:i A', strtotime($reply['created_at']))) ?></span>
                        </div>
                        <p class="reply-content"><?= htmlspecialchars($reply['content']) ?></p>
                        <?php if ($replyCanDelete): ?>
                            <form method="POST" action="reply_action.php" onsubmit="return confirm('Delete this reply?');" style="margin-top: 4px;">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="reply_id" value="<?= (int)$reply['id'] ?>">
                                <input type="hidden" name="return_post_id" value="<?= (int)$post['id'] ?>">
                                <button type="submit" class="btn-text btn-text-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
