<?php 
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';

$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if(empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif(strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
        $error = "Password must be at least 8 chars, with 1 uppercase, 1 lowercase, 1 number, and 1 special char.";
    } else {
        $db = Database::getInstance()->getConnection();
        $user = new User($db);

        if($user->register($name, $email, $password)) {
            header("Location: login.php?registered=1");
            exit;
        } else {
            $error = "Registration failed. This email is already registered.";
        }
    }
}

require_once 'includes/header.php'; 
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon-circle">🎓</div>
            <h2 class="auth-title">Create Account</h2>
            <p class="auth-subtitle">Join the CampusConnect community</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-error">
                <span>⚠️</span>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php" autocomplete="off">
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-control" 
                    placeholder="e.g. Muddassir Ahmed" 
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                    required
                >
            </div>

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
                    placeholder="Min 8 chars, 1 uppercase, 1 special char" 
                    required
                >
            </div>

            <button type="submit" class="btn-submit">
                <span>Create Student Account</span>
                <span>&rarr;</span>
            </button>
        </form>

        <div class="auth-footer-text">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>