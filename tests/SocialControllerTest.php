<?php
/**
 * SocialController suite (intent 003, unit 004-controllers-social, EF-3b).
 *
 * Proves that like / follow / bookmark / reply request handling lives in
 * SocialController, that the *_action.php pages only delegate, and that the
 * AJAX responses keep exactly the same keys, values and HTTP codes the pages
 * produced before the refactor.
 *
 * Run via CLI: php tests/SocialControllerTest.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Post.php';
require_once __DIR__ . '/../classes/Csrf.php';
require_once __DIR__ . '/../classes/SocialController.php';

// Start the session BEFORE any output (CLI quirk, see RbacTest): once
// headers_sent() is true, Csrf can no longer start the session.
$csrfHelper = new Csrf();
$csrf = $csrfHelper->token();

$passed = 0;
$total = 0;

function assertSC(string $name, bool $condition, ?string $detail = null): void {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo " [PASS] Test {$total}: {$name}" . PHP_EOL;
    } else {
        echo " [FAIL] Test {$total}: {$name}" . ($detail ? " ({$detail})" : '') . PHP_EOL;
    }
}

$db = Database::getInstance()->getConnection();
$userModel = new User($db);
$postModel = new Post($db);

$uniq = time() . '_' . rand(1000, 9999);
$userModel->register("Ctrl Cara {$uniq}", "ctrl_cara_{$uniq}@nu.edu.pk", 'StrongPass123!');
$userModel->register("Ctrl Dan {$uniq}", "ctrl_dan_{$uniq}@nu.edu.pk", 'StrongPass123!');
$cara = $userModel->verifyPassword("ctrl_cara_{$uniq}@nu.edu.pk", 'StrongPass123!');
$dan = $userModel->verifyPassword("ctrl_dan_{$uniq}@nu.edu.pk", 'StrongPass123!');
$caraUser = ['id' => (int)$cara['id'], 'name' => 'Cara', 'email' => '', 'role' => 'student'];
$danId = (int)$dan['id'];
$postId = (int)$postModel->create($danId, "Controller test post {$uniq}");

$controller = SocialController::fromConnection($db, $caraUser);
$ok = ['csrf_token' => $csrf];

echo "--- GUARDS (same codes and messages as before) ---" . PHP_EOL;

// Logged out: no session user, so Auth::user() is null.
$guest = SocialController::fromConnection($db);
foreach (['handleLike', 'handleFollow', 'handleBookmark'] as $m) {
    $r = $guest->$m('POST', $ok + ['post_id' => $postId, 'user_id' => $danId]);
    assertSC("{$m}: logged out -> 401 Login required",
        $r === ['status' => 401, 'body' => ['success' => false, 'message' => 'Login required.']]);
    $r = $controller->$m('GET', $ok + ['post_id' => $postId, 'user_id' => $danId]);
    assertSC("{$m}: GET -> 405 POST required",
        $r === ['status' => 405, 'body' => ['success' => false, 'message' => 'POST required.']]);
    $r = $controller->$m('POST', ['csrf_token' => 'wrong', 'post_id' => $postId, 'user_id' => $danId]);
    assertSC("{$m}: bad CSRF -> 403 token expired",
        $r === ['status' => 403, 'body' => ['success' => false, 'message' => 'Security token expired. Please refresh and try again.']]);
}

echo "--- LIKE ---" . PHP_EOL;
$r = $controller->handleLike('POST', $ok + ['post_id' => 0]);
assertSC('Like: invalid post id', $r === ['status' => 200, 'body' => ['success' => false, 'message' => 'Invalid post.']]);
$r = $controller->handleLike('POST', $ok + ['post_id' => $postId]);
assertSC('Like: response keys unchanged (success, liked, like_count, message)',
    array_keys($r['body']) === ['success', 'liked', 'like_count', 'message'], json_encode($r));
assertSC('Like: first toggle likes the post',
    $r === ['status' => 200, 'body' => ['success' => true, 'liked' => true, 'like_count' => 1, 'message' => 'Post liked.']], json_encode($r));
$r = $controller->handleLike('POST', $ok + ['post_id' => (string)$postId]);
assertSC('Like: second toggle removes it',
    $r['body'] === ['success' => true, 'liked' => false, 'like_count' => 0, 'message' => 'Like removed.'], json_encode($r));
$r = $controller->handleLike('POST', $ok + ['post_id' => 999999]);
assertSC('Like: missing post -> Could not update like',
    $r['body'] === ['success' => false, 'message' => 'Could not update like.'], json_encode($r));

echo "--- FOLLOW ---" . PHP_EOL;
$r = $controller->handleFollow('POST', $ok + ['user_id' => $caraUser['id']]);
assertSC('Follow: self-follow -> Invalid user', $r['body'] === ['success' => false, 'message' => 'Invalid user.']);
$r = $controller->handleFollow('POST', $ok + ['user_id' => 0]);
assertSC('Follow: invalid id -> Invalid user', $r['body'] === ['success' => false, 'message' => 'Invalid user.']);
$r = $controller->handleFollow('POST', $ok + ['user_id' => $danId]);
assertSC('Follow: response keys unchanged',
    array_keys($r['body']) === ['success', 'following', 'followers_count', 'viewer_id', 'viewer_following_count', 'message'], json_encode($r));
assertSC('Follow: first toggle follows',
    $r === ['status' => 200, 'body' => ['success' => true, 'following' => true, 'followers_count' => 1,
        'viewer_id' => $caraUser['id'], 'viewer_following_count' => 1, 'message' => 'Following.']], json_encode($r));
$r = $controller->handleFollow('POST', $ok + ['user_id' => $danId]);
assertSC('Follow: second toggle unfollows',
    $r['body']['following'] === false && $r['body']['followers_count'] === 0
        && $r['body']['viewer_following_count'] === 0 && $r['body']['message'] === 'Unfollowed.', json_encode($r));

echo "--- BOOKMARK ---" . PHP_EOL;
$r = $controller->handleBookmark('POST', $ok + ['post_id' => -3]);
assertSC('Bookmark: invalid post id', $r['body'] === ['success' => false, 'message' => 'Invalid post.']);
$r = $controller->handleBookmark('POST', $ok + ['post_id' => $postId]);
assertSC('Bookmark: first toggle saves (keys unchanged)',
    $r === ['status' => 200, 'body' => ['success' => true, 'bookmarked' => true, 'message' => 'Post saved.']], json_encode($r));
$r = $controller->handleBookmark('POST', $ok + ['post_id' => $postId]);
assertSC('Bookmark: second toggle removes',
    $r['body'] === ['success' => true, 'bookmarked' => false, 'message' => 'Bookmark removed.'], json_encode($r));

echo "--- REPLY (Post/Redirect/Get) ---" . PHP_EOL;
$r = $guest->handleReply('POST', $ok + ['action' => 'create', 'post_id' => $postId, 'content' => 'hi']);
assertSC('Reply: logged out -> login.php, no flash', $r === ['result' => null, 'redirect' => 'login.php']);
$r = $controller->handleReply('GET', []);
assertSC('Reply: GET -> Invalid request, back to index',
    $r === ['result' => ['success' => false, 'message' => 'Invalid request.'], 'redirect' => 'index.php']);
$r = $controller->handleReply('POST', ['csrf_token' => 'x', 'action' => 'create', 'post_id' => $postId, 'content' => 'hi']);
assertSC('Reply: bad CSRF -> token expired, back to the post',
    $r === ['result' => ['success' => false, 'message' => 'Security token expired. Please try again.'],
        'redirect' => 'post.php?id=' . $postId]);
$r = $controller->handleReply('POST', $ok + ['action' => 'create', 'post_id' => $postId, 'content' => '']);
assertSC('Reply: empty content is rejected', $r['result']['success'] === false && $r['redirect'] === 'post.php?id=' . $postId);
$r = $controller->handleReply('POST', $ok + ['action' => 'create', 'post_id' => $postId, 'content' => "Reply {$uniq}"]);
assertSC('Reply: create succeeds', $r === ['result' => ['success' => true, 'message' => 'Reply posted!'], 'redirect' => 'post.php?id=' . $postId]);
$replyId = (int)$db->query("SELECT id FROM replies WHERE post_id = {$postId} ORDER BY id DESC LIMIT 1")->fetchColumn();
$danCtrl = SocialController::fromConnection($db, ['id' => $danId, 'role' => 'student']);
$r = $danCtrl->handleReply('POST', $ok + ['action' => 'delete', 'reply_id' => $replyId, 'return_post_id' => $postId]);
assertSC("Reply: student cannot delete someone else's reply",
    $r === ['result' => ['success' => false, 'message' => 'Permission denied or reply not found.'], 'redirect' => 'post.php?id=' . $postId]);
$r = $controller->handleReply('POST', $ok + ['action' => 'delete', 'reply_id' => $replyId, 'return_post_id' => $postId]);
assertSC('Reply: author deletes own reply', $r['result'] === ['success' => true, 'message' => 'Reply deleted.']);
$controller->handleReply('POST', $ok + ['action' => 'create', 'post_id' => $postId, 'content' => "Second {$uniq}"]);
$replyId2 = (int)$db->query("SELECT id FROM replies WHERE post_id = {$postId} AND deleted_at IS NULL ORDER BY id DESC LIMIT 1")->fetchColumn();
$adminCtrl = SocialController::fromConnection($db, ['id' => $danId, 'role' => 'system_admin']);
$r = $adminCtrl->handleReply('POST', $ok + ['action' => 'delete', 'reply_id' => $replyId2, 'return_post_id' => $postId]);
assertSC("Reply: system_admin may delete any reply (reply.delete_any)", $r['result'] === ['success' => true, 'message' => 'Reply deleted.']);
$r = $controller->handleReply('POST', $ok + ['action' => 'delete', 'reply_id' => 0]);
assertSC('Reply: invalid reply id -> index', $r === ['result' => ['success' => false, 'message' => 'Invalid reply.'], 'redirect' => 'index.php']);
$r = $controller->handleReply('POST', $ok + ['action' => 'nope']);
assertSC('Reply: unknown action', $r['result'] === ['success' => false, 'message' => 'Unknown action.']);

echo "--- PAGES DELEGATE ---" . PHP_EOL;
$root = __DIR__ . '/..';
$pages = ['like_action.php' => 'handleLike', 'follow_action.php' => 'handleFollow',
          'bookmark_action.php' => 'handleBookmark', 'reply_action.php' => 'handleReply'];
foreach ($pages as $page => $method) {
    $src = file_get_contents("{$root}/{$page}");
    assertSC("{$page} delegates to SocialController::{$method}()",
        strpos($src, "->{$method}(\$_SERVER['REQUEST_METHOD'], \$_POST)") !== false
            && preg_match('/new (Like|Follow|Bookmark|Reply)\(|Csrf::|Auth::|switch\s*\(/', $src) === 0);
}
$postSrc = file_get_contents("{$root}/post_action.php");
assertSC('post_action.php delegates to PostController',
    strpos($postSrc, 'new PostController(') !== false && preg_match('/new (Like|Follow|Bookmark|Reply)\(/', $postSrc) === 0);
$ctrlSrc = file_get_contents("{$root}/classes/SocialController.php");
assertSC('SocialController uses only injected models (no raw SQL)',
    preg_match('/->(prepare|query|exec)\(/', $ctrlSrc) === 0);

// Write the session while the throwaway DB still exists (bootstrap deletes it on shutdown).
session_write_close();

echo PHP_EOL . "Result: {$passed}/{$total} passed" . PHP_EOL;
exit($passed === $total ? 0 : 1);
