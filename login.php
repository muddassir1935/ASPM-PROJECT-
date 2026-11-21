<?php 
/**
 * Login Page (Thin Entry Point — intent 003, unit 003-controllers-auth).
 *
 * Delegates request handling to AuthController::handleLogin() and renders
 * the login view.
 */
require_once 'config/Database.php';
require_once 'classes/AuthController.php';

$controller = AuthController::fromConnection(Database::getInstance()->getConnection());
$response = $controller->handleLogin($_SERVER['REQUEST_METHOD'], $_POST, $_GET);

if ($response['redirect'] !== null) {
    header('Location: ' . $response['redirect']);
    exit;
}
if ($response['status'] !== 200) {
    http_response_code($response['status']);
}

$error = $response['error'];
$success = $response['success'];

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
            <?= $csrf->field() ?>
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
                <div class="password-input-wrap">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••" 
                        required
                    >
                    <button type="button" class="btn-toggle-pw" data-toggle-pw aria-label="Toggle password visibility">
                        <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
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
