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

        <form method="POST" action="signup.php" autocomplete="off" id="signup-form">
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
                <div id="email-hint" style="font-size: 0.8rem; margin-top: 6px; color: #64748b;"></div>
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
                <ul id="password-rules" style="list-style: none; margin-top: 8px; font-size: 0.8rem; color: #64748b; line-height: 1.7;">
                    <li data-rule="length">○ At least 8 characters</li>
                    <li data-rule="upper">○ One uppercase letter (A-Z)</li>
                    <li data-rule="lower">○ One lowercase letter (a-z)</li>
                    <li data-rule="number">○ One number (0-9)</li>
                    <li data-rule="special">○ One special character (!@#$ etc.)</li>
                </ul>
            </div>

            <div id="signup-js-error" style="display: none; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 10px; padding: 10px 12px; font-size: 0.85rem; font-weight: 500; margin-bottom: 1rem;"></div>

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
                li.style.color = passed ? '#059669' : '#64748b';
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
                emailHint.style.color = '#059669';
            } else {
                emailHint.textContent = 'Please enter a valid email address (e.g. student@nu.edu.pk)';
                emailHint.style.color = '#dc2626';
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