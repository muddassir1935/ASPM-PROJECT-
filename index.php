<?php
require_once 'includes/header.php';

// --- Sprint 2: Timeline feed setup (logged-in dashboard only) ---
$feed = ['posts' => [], 'total' => 0, 'page' => 1, 'has_more' => false];
$flashType = $_SESSION['flash_type'] ?? null;
$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_type'], $_SESSION['flash_message']);

if ($currentUser) {
    require_once 'config/Database.php';
    require_once 'classes/Post.php';
    require_once 'classes/PostController.php';

    $feedPage = max(1, (int)($_GET['page'] ?? 1));
    $feedController = new PostController(new Post(Database::getInstance()->getConnection()));
    $feed = $feedController->getTimelineFeed($feedPage, 10);
}
?>

<?php if($currentUser): ?>
    <!-- Logged In Dashboard View -->
    <div style="max-width: 850px; margin: 1rem auto;">
        <!-- Welcome Banner Card -->
        <div style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; border-radius: 16px; padding: 2.2rem; box-shadow: 0 10px 25px rgba(5, 150, 105, 0.2); margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span style="background: rgba(255,255,255,0.2); font-size: 0.78rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.05em;">
                        Student Portal
                    </span>
                    <h1 style="font-size: 1.85rem; font-weight: 800; margin-top: 8px;">
                        Welcome back, <?= htmlspecialchars($currentUser['name']) ?>! 👋
                    </h1>
                    <p style="color: #d1fae5; font-size: 0.95rem; margin-top: 4px;">
                        <?= htmlspecialchars($currentUser['email']) ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if ($flashMessage): ?>
            <div style="padding: 12px 14px; border-radius: 10px; font-size: 0.9rem; font-weight: 500; margin-bottom: 1rem; <?= $flashType === 'success' ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;' ?>">
                <?= htmlspecialchars($flashMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Compose Box (WBS 2.3.1) -->
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
            <form method="POST" action="post_action.php" id="compose-form">
                <input type="hidden" name="action" value="create">
                <textarea id="compose-content" name="content" rows="3" maxlength="280" placeholder="Share an update or announcement with the campus..." style="width: 100%; padding: 11px 14px; font-size: 0.95rem; border: 1.5px solid #e2e8f0; border-radius: 10px; resize: vertical; outline: none;"></textarea>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                    <span id="compose-counter" style="font-size: 0.82rem; color: #64748b; font-weight: 600;">0 / 280</span>
                    <button type="submit" id="compose-submit" disabled style="background: var(--primary); color: white; border: none; padding: 9px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; opacity: 0.5;">
                        Post
                    </button>
                </div>
            </form>
        </div>
        <script>
            (function () {
                var textarea = document.getElementById('compose-content');
                var counter = document.getElementById('compose-counter');
                var submit = document.getElementById('compose-submit');
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

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.15rem; font-weight: 700; color: #1e293b;">
                Your Feed
            </h3>
            <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;"><?= (int)$feed['total'] ?> post<?= $feed['total'] === 1 ? '' : 's' ?></span>
        </div>

        <?php if (empty($feed['posts'])): ?>
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 3rem; text-align: center; box-shadow: var(--shadow-sm);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                <h4 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 8px;">No updates yet</h4>
                <p style="font-size: 0.9rem; color: #64748b; max-width: 300px; margin: 0 auto;">
                    When you follow other students or societies, their announcements and posts will appear here.
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($feed['posts'] as $feedPost): ?>
                <?php
                    $isOwnPost = (int)$feedPost['user_id'] === (int)$currentUser['id'];
                    $authorInitial = strtoupper(mb_substr(trim($feedPost['author_name']), 0, 1, 'UTF-8'));
                ?>
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;">
                            <?= htmlspecialchars($authorInitial) ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #0f172a; font-size: 0.95rem;">
                                <?= htmlspecialchars($feedPost['author_name']) ?>
                                <span style="background: var(--primary-light, #ecfdf5); color: var(--primary); font-size: 0.68rem; font-weight: 700; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.04em; vertical-align: middle;">
                                    <?= htmlspecialchars($feedPost['author_role']) ?>
                                </span>
                            </div>
                            <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($feedPost['time_ago']) ?></div>
                        </div>
                    </div>

                    <p style="font-size: 0.97rem; color: #1e293b; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;"><?= htmlspecialchars($feedPost['content']) ?></p>

                    <div style="display: flex; gap: 14px; align-items: center; margin-top: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                        <a href="post.php?id=<?= (int)$feedPost['id'] ?>" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.85rem;">View Post</a>
                        <?php if ($isOwnPost): ?>
                            <button type="button" onclick="toggleFeedEdit(<?= (int)$feedPost['id'] ?>)" style="background: none; border: none; color: #475569; font-weight: 600; font-size: 0.85rem; cursor: pointer; padding: 0;">Edit</button>
                            <form method="POST" action="post_action.php" onsubmit="return confirm('Delete this post? This cannot be undone.');" style="display: inline; margin: 0;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="post_id" value="<?= (int)$feedPost['id'] ?>">
                                <button type="submit" style="background: none; border: none; color: #dc2626; font-weight: 600; font-size: 0.85rem; cursor: pointer; padding: 0;">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($isOwnPost): ?>
                        <form id="feed-edit-<?= (int)$feedPost['id'] ?>" method="POST" action="post_action.php" style="display: none; margin-top: 12px;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="post_id" value="<?= (int)$feedPost['id'] ?>">
                            <textarea name="content" rows="3" maxlength="280" required style="width: 100%; padding: 11px 14px; font-size: 0.95rem; border: 1.5px solid #e2e8f0; border-radius: 10px; resize: vertical; outline: none;"><?= htmlspecialchars($feedPost['content']) ?></textarea>
                            <div style="display: flex; gap: 8px; margin-top: 8px;">
                                <button type="submit" style="background: var(--primary); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
                                <button type="button" onclick="toggleFeedEdit(<?= (int)$feedPost['id'] ?>)" style="background: white; color: #64748b; border: 1.5px solid #e2e8f0; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($feed['page'] > 1 || $feed['has_more']): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin: 1rem 0;">
                    <?php if ($feed['page'] > 1): ?>
                        <a href="index.php?page=<?= (int)$feed['page'] - 1 ?>" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">&larr; Previous</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    <span style="font-size: 0.85rem; color: #64748b; font-weight: 600;">Page <?= (int)$feed['page'] ?></span>
                    <?php if ($feed['has_more']): ?>
                        <a href="index.php?page=<?= (int)$feed['page'] + 1 ?>" style="color: var(--primary); text-decoration: none; font-weight: 600; font-size: 0.9rem;">Next &rarr;</a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <script>
                function toggleFeedEdit(postId) {
                    var form = document.getElementById('feed-edit-' + postId);
                    if (form) {
                        form.style.display = (form.style.display === 'none') ? 'block' : 'none';
                    }
                }
            </script>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Public Hero View -->
    <div style="max-width: 850px; margin: 4rem auto 2rem auto; text-align: center;">
        <span style="background: #ecfdf5; color: #059669; font-size: 0.85rem; font-weight: 700; padding: 6px 14px; border-radius: 20px; display: inline-block; margin-bottom: 1.2rem; border: 1px solid #a7f3d0;">
            🎓 The #1 Student Network
        </span>

        <h1 style="font-size: 3rem; font-weight: 800; color: #0f172a; line-height: 1.15; letter-spacing: -0.03em; margin-bottom: 1.2rem;">
            Campus Life,<br>Connected.
        </h1>

        <p style="font-size: 1.1rem; color: #64748b; max-width: 600px; margin: 0 auto 2.5rem auto; line-height: 1.6;">
            Join the ultimate student network. Share updates, discover university events, and stay connected with your campus community in real-time.
        </p>

        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="signup.php" class="btn-nav-primary" style="padding: 14px 32px; font-size: 1.05rem;">
                Create Your Account
            </a>
            <a href="login.php" class="nav-link" style="padding: 14px 32px; font-size: 1.05rem; border: 1.5px solid #cbd5e1; background: white; border-radius: 10px; color: #1e293b;">
                Log In
            </a>
        </div>

        <!-- Features Overview Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-top: 5rem; text-align: left;">
            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="font-size: 1.8rem; margin-bottom: 8px;">📢</div>
                <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Real-time Announcements</h4>
                <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5;">
                    Never miss an important update. Get instant announcements from faculty, societies, and administration.
                </p>
            </div>

            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="font-size: 1.8rem; margin-bottom: 8px;">🤝</div>
                <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Connect with Peers</h4>
                <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5;">
                    Find and follow students from your department. Share notes, discuss projects, and build your network.
                </p>
            </div>

            <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="font-size: 1.8rem; margin-bottom: 8px;">📱</div>
                <h4 style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Always Accessible</h4>
                <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5;">
                    Experience a fast, responsive design that works perfectly on your laptop, tablet, or smartphone.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>