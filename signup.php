<?php 
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';
require_once 'classes/Csrf.php';

$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sprint 5 CSRF: the signup form carries a token too.
    if (!Csrf::validatePost()) {
        $error = "Security token expired. Please try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        // Passwords are never trimmed: a leading/trailing space is part of
        // the password (must match login.php, which does not trim either).
        $password = (string)($_POST['password'] ?? '');

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
                // Sprint 5 final fix (M6): register() fails for bad input
                // AND for a duplicate email — claiming "already registered"
                // for every failure was both wrong and enumerable.
                $error = "Registration failed. Please check your details and try again.";
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
            <h2 class="auth-title">Create Account</h2>
            <p class="auth-subtitle">Join the CampusConnect community</p>
        </div>

        <?php if(!empty($error)): ?>
            <div class="alert alert-error">
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php" autocomplete="off" id="signup-form">
            <?= Csrf::field() ?>
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
                <div id="email-hint" class="field-hint"></div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Min 8 characters"
                    required
                >
                <ul id="password-rules" class="pw-rules">
                    <li data-rule="length">○ At least 8 characters</li>
                    <li data-rule="upper">○ One uppercase letter (A-Z)</li>
                    <li data-rule="lower">○ One lowercase letter (a-z)</li>
                    <li data-rule="number">○ One number (0-9)</li>
                    <li data-rule="special">○ One special character (!@#$ etc.)</li>
                </ul>
            </div>

            <div id="signup-js-error" class="js-error" style="display: none;"></div>

            <button type="submit" class="btn-submit">
                Create Student Account
            </button>
        </form>

        <div class="auth-footer-text">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
</div>

<script>
    // Live signup validation (WBS 1.4.1) — server-side checks remain in place.
    (function () {
        var form = document.getElementById('signup-form');
        var nameInput = document.getElementById('name');
        var emailInput = document.getElementById('email');
        var passwordInput = document.getElementById('password');
        var emailHint = document.getElementById('email-hint');
        var jsError = document.getElementById('signup-js-error');
        var ruleItems = document.querySelectorAll('#password-rules li');

        var checks = {
            length: function (v) { return v.length >= 8; },
            upper: function (v) { return /[A-Z]/.test(v); },
            lower: function (v) { return /[a-z]/.test(v); },
            number: function (v) { return /[0-9]/.test(v); },
            special: function (v) { return /[\W_]/.test(v); }
        };
        var labels = {
            length: 'At least 8 characters',
            upper: 'One uppercase letter (A-Z)',
            lower: 'One lowercase letter (a-z)',
            number: 'One number (0-9)',
            special: 'One special character (!@#$ etc.)'
        };

        function passwordValid() {
            var v = passwordInput.value;
            var ok = true;
            ruleItems.forEach(function (li) {
                var rule = li.getAttribute('data-rule');
                var passed = checks[rule](v);
                li.textContent = (passed ? '✓ ' : '○ ') + labels[rule];
                li.style.color = passed ? '#2B3FE0' : '#536471';
                li.style.fontWeight = passed ? '600' : '400';
                if (!passed) { ok = false; }
            });
            return ok;
        }

        function emailValid() {
            var v = emailInput.value.trim();
            var ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
            if (v === '') {
                emailHint.textContent = '';
            } else if (ok) {
                emailHint.textContent = '✓ Looks like a valid email';
                emailHint.style.color = '#2B3FE0';
            } else {
                emailHint.textContent = 'Please enter a valid email address (e.g. student@nu.edu.pk)';
                emailHint.style.color = '#D92D20';
            }
            return ok;
        }

        passwordInput.addEventListener('input', passwordValid);
        emailInput.addEventListener('input', emailValid);
        passwordValid();
        emailValid();

        form.addEventListener('submit', function (e) {
            var problems = [];
            if (nameInput.value.trim() === '') { problems.push('Please enter your full name.'); }
            if (!emailValid()) { problems.push('Please enter a valid email address.'); }
            if (!passwordValid()) { problems.push('Your password does not meet all the rules yet.'); }
            if (problems.length > 0) {
                e.preventDefault();
                jsError.textContent = problems.join(' ');
                jsError.style.display = 'block';
            } else {
                jsError.style.display = 'none';
            }
        });
    })();
</script>

<?php require_once 'includes/footer.php'; ?>