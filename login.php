<?php 
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';
require_once 'classes/Csrf.php';
require_once 'classes/LoginRateLimiter.php';

// Sprint 5 final fix (N-10): a logged-in user has no business on the login
// form — send them to the feed instead of showing a second login.
if (Auth::isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

if (isset($_GET['registered'])) {
    $success = "Registration successful! Please login with your credentials.";
}
if (isset($_GET['logged_out'])) {
    $success = "You have been safely logged out.";
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sprint 5 CSRF: the login form carries a token too.
    if (!Csrf::validatePost()) {
        $error = "Security token expired. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        // Passwords are never trimmed: a leading/trailing space is part of
        // the password, and trimming here but not at signup would lock users out.
        $password = (string)($_POST['password'] ?? '');

        if(empty($email) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            $db = Database::getInstance()->getConnection();
            $user = new User($db);
            // Sprint 5 rate-limit: 5 failed attempts per (email, IP) in
            // 15 minutes locks logins for that pair for 15 minutes.
            $limiter = new LoginRateLimiter($db);
            if ($limiter->isLocked($email)) {
                $minutes = (int)ceil($limiter->lockRemainingSeconds($email) / 60);
                $error = "Too many failed login attempts. Please try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . ".";
            } else {
                $userData = $user->verifyPassword($email, $password);

                if($userData) {
                    $limiter->recordSuccess($email);
                    Auth::login($userData);
                    header("Location: index.php");
                    exit;
                } else {
                    $limiter->recordFailure($email);
                    $error = "Invalid email or password. Please try again.";
                }
            }
        }
    }
}

require_once 'includes/header.php'; 
?>

<div class="auth-shell">
    <aside class="auth-brand">
        <div class="auth-brand-top">
            <?= brandMarkSvg('on-dark') ?>
            <span class="auth-brand-name">CampusConnect</span>
        </div>
        <div class="auth-brand-bottom">
            <p class="auth-tagline">Your campus, in one feed.</p>
            <ul class="auth-points">
                <li>Announcements from your departments and societies</li>
                <li>Follow the classmates and teachers you know</li>
                <li>Save posts and pick them up later</li>
            </ul>
        </div>
    </aside>
    <div class="auth-card">
        <div class="auth-header">
            <h2 class="auth-title">Welcome Back</h2>
            <p class="auth-subtitle">Sign in to access your campus announcements</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-error">
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <div><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <?= Csrf::field() ?>
            <div class="form-group">
                <label class="form-label" for="email">Campus Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="student@nu.edu.pk" 
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <button type="submit" class="btn-submit">
                Sign In
            </button>
        </form>

        <div class="auth-footer-text">
            Don't have an account yet? <a href="signup.php">Create Account</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>