<?php 
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';

$error = "";
$success = "";

if (isset($_GET['registered'])) {
    $success = "Registration successful! Please login with your credentials.";
}
if (isset($_GET['logged_out'])) {
    $success = "You have been safely logged out.";
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if(empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $db = Database::getInstance()->getConnection();
        $user = new User($db);
        $userData = $user->verifyPassword($email, $password);
        
        if($userData) {
            Auth::login($userData);
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password. Please try again.";
        }
    }
}

require_once 'includes/header.php'; 
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon-circle">🔐</div>
            <h2 class="auth-title">Welcome Back</h2>
            <p class="auth-subtitle">Sign in to access your campus announcements</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-error">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <span>✅</span>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label class="form-label" for="password" style="margin-bottom: 0;">Password</label>
                </div>
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
                <span>Sign In</span>
                <span>&rarr;</span>
            </button>
        </form>

        <div class="auth-footer-text">
            Don't have an account yet? <a href="signup.php">Create Account</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>