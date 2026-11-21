<?php 
/**
 * Signup Page (Thin Entry Point — intent 003, unit 003-controllers-auth).
 *
 * Delegates registration handling to AuthController::handleRegister() and
 * renders the signup view.
 */
require_once 'config/Database.php';
require_once 'classes/AuthController.php';

$controller = AuthController::fromConnection(Database::getInstance()->getConnection());
$response = $controller->handleRegister($_SERVER['REQUEST_METHOD'], $_POST);

if ($response['redirect'] !== null) {
    header('Location: ' . $response['redirect']);
    exit;
}

$error = $response['error'];

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
            <?= $csrf->field() ?>
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
                <div class="password-input-wrap">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Min 8 characters"
                        required
                    >
                    <button type="button" class="btn-toggle-pw" data-toggle-pw aria-label="Toggle password visibility">
                        <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </button>
                </div>
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
                li.style.color = passed ? '#10B981' : '#71767B';
                li.style.fontWeight = passed ? '600' : '400';
                if (!passed) { ok = false; }
            });
            return ok;
        }

        function emailValid() {
            var v = emailInput.value.trim();
            var isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
            var isCampus = /^[a-zA-Z0-9._%+-]+@([a-zA-Z0-9.-]+\.)?nu\.edu\.pk$/i.test(v);
            if (v === '') {
                emailHint.textContent = '';
                return false;
            } else if (isCampus) {
                emailHint.textContent = '✓ Valid FAST-NUCES campus email';
                emailHint.style.color = '#10B981';
                return true;
            } else if (isEmail) {
                emailHint.textContent = '⚠ Registration is restricted to university emails (@nu.edu.pk)';
                emailHint.style.color = '#EF4444';
                return false;
            } else {
                emailHint.textContent = 'Please enter your campus email (e.g. student@nu.edu.pk)';
                emailHint.style.color = '#EF4444';
                return false;
            }
        }

        passwordInput.addEventListener('input', passwordValid);
        emailInput.addEventListener('input', emailValid);
        passwordValid();
        emailValid();

        form.addEventListener('submit', function (e) {
            var problems = [];
            if (nameInput.value.trim() === '') { problems.push('Please enter your full name.'); }
            if (!emailValid()) { problems.push('Please enter a valid @nu.edu.pk campus email.'); }
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