<?php
/**
 * AuthController & ProfileController suite (intent 003, unit 003-controllers-auth, EF-3a).
 *
 * Proves that login, registration, logout, profile edit and password change
 * request handling lives in AuthController and ProfileController, that both
 * extend Model, that login.php / signup.php / logout.php / profile_action.php
 * are thin entry points that delegate, and that CSRF, validation, rate-limit
 * lockouts, redirects and session revocation on password change behave
 * identically to the pre-refactor pages.
 *
 * Run via CLI: php tests/AuthProfileControllerTest.php
 */
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Model.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Csrf.php';
require_once __DIR__ . '/../classes/LoginRateLimiter.php';
require_once __DIR__ . '/../classes/DbSessionHandler.php';
require_once __DIR__ . '/../classes/AuthController.php';
require_once __DIR__ . '/../classes/ProfileController.php';

// Start the session BEFORE any output so Csrf and Auth can manage $_SESSION.
$csrfHelper = new Csrf();
$authHelper = new Auth(null, $csrfHelper);
$csrf = $csrfHelper->token();

$passed = 0;
$total = 0;

function assertAPC(string $name, bool $condition, ?string $detail = null): void {
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
$uniq = time() . '_' . rand(1000, 9999);

echo "--- CLASS HIERARCHY ---" . PHP_EOL;
assertAPC('AuthController extends Model', is_subclass_of(AuthController::class, Model::class));
assertAPC('ProfileController extends Model', is_subclass_of(ProfileController::class, Model::class));

// Disable usleep delay in unit tests so failure tests execute instantaneously.
$authCtrl = AuthController::fromConnection($db, null, false);

echo "--- REGISTRATION (AuthController::handleRegister) ---" . PHP_EOL;

// Logged-in user on signup page redirects to index.php
$loggedInAuthCtrl = AuthController::fromConnection($db, ['id' => 1, 'name' => 'Existing', 'role' => 'student'], false);
$r = $loggedInAuthCtrl->handleRegister('GET', []);
assertAPC('Register: logged-in user redirects to index.php',
    $r === ['redirect' => 'index.php', 'error' => '']);

// GET request renders form with no redirect and no error
$r = $authCtrl->handleRegister('GET', []);
assertAPC('Register: GET returns no redirect and empty error',
    $r === ['redirect' => null, 'error' => '']);

// Bad CSRF token
$r = $authCtrl->handleRegister('POST', [
    'csrf_token' => 'bad_token',
    'name' => 'Test Student',
    'email' => "reg_{$uniq}@nu.edu.pk",
    'password' => 'StrongPass123!'
]);
assertAPC('Register: bad CSRF rejected',
    $r === ['redirect' => null, 'error' => 'Security token expired. Please try again.']);

// Empty fields
$r = $authCtrl->handleRegister('POST', [
    'csrf_token' => $csrf,
    'name' => '   ',
    'email' => "reg_{$uniq}@nu.edu.pk",
    'password' => 'StrongPass123!'
]);
assertAPC('Register: empty fields rejected',
    $r === ['redirect' => null, 'error' => 'All fields are required.']);

// Non-campus email
$r = $authCtrl->handleRegister('POST', [
    'csrf_token' => $csrf,
    'name' => 'Test Student',
    'email' => "reg_{$uniq}@gmail.com",
    'password' => 'StrongPass123!'
]);
assertAPC('Register: non-campus email rejected',
    $r === ['redirect' => null, 'error' => 'Access restricted: Registration is exclusively for FAST-NUCES campus emails (@nu.edu.pk).']);

// Weak password
$r = $authCtrl->handleRegister('POST', [
    'csrf_token' => $csrf,
    'name' => 'Test Student',
    'email' => "reg_{$uniq}@nu.edu.pk",
    'password' => 'weak'
]);
assertAPC('Register: weak password rejected',
    $r === ['redirect' => null, 'error' => 'Password must be 8–128 chars, with 1 uppercase, 1 lowercase, 1 number, and 1 special char.']);

// Valid registration
$regEmail = "reg_{$uniq}@nu.edu.pk";
$r = $authCtrl->handleRegister('POST', [
    'csrf_token' => $csrf,
    'name' => "Auth Student {$uniq}",
    'email' => $regEmail,
    'password' => 'StrongPass123!'
]);
assertAPC('Register: valid submission redirects to login.php?registered=1',
    $r === ['redirect' => 'login.php?registered=1', 'error' => '']);

// Duplicate registration fails with non-enumerable message
$r = $authCtrl->handleRegister('POST', [
    'csrf_token' => $csrf,
    'name' => "Auth Student {$uniq}",
    'email' => $regEmail,
    'password' => 'StrongPass123!'
]);
assertAPC('Register: duplicate email returns generic failure message',
    $r === ['redirect' => null, 'error' => 'Registration failed. Please check your details and try again.']);

echo "--- LOGIN (AuthController::handleLogin) ---" . PHP_EOL;

// Logged-in user on login page redirects to index.php
$r = $loggedInAuthCtrl->handleLogin('GET', []);
assertAPC('Login: logged-in user redirects to index.php',
    $r['redirect'] === 'index.php');

// GET with ?registered=1 and ?logged_out=1 banners
$r = $authCtrl->handleLogin('GET', [], ['registered' => '1']);
assertAPC('Login: GET ?registered=1 sets registration success banner',
    $r['redirect'] === null && $r['success'] === 'Registration successful! Please login with your credentials.');

$r = $authCtrl->handleLogin('GET', [], ['logged_out' => '1']);
assertAPC('Login: GET ?logged_out=1 sets logout success banner',
    $r['redirect'] === null && $r['success'] === 'You have been safely logged out.');

// Bad CSRF token
$r = $authCtrl->handleLogin('POST', [
    'csrf_token' => 'wrong',
    'email' => $regEmail,
    'password' => 'StrongPass123!'
]);
assertAPC('Login: bad CSRF rejected',
    $r['redirect'] === null && $r['error'] === 'Security token expired. Please try again.');

// Empty fields
$r = $authCtrl->handleLogin('POST', [
    'csrf_token' => $csrf,
    'email' => '',
    'password' => 'StrongPass123!'
]);
assertAPC('Login: empty fields rejected',
    $r['redirect'] === null && $r['error'] === 'Please fill in all fields.');

// Non-campus email
$r = $authCtrl->handleLogin('POST', [
    'csrf_token' => $csrf,
    'email' => 'someone@gmail.com',
    'password' => 'StrongPass123!'
]);
assertAPC('Login: non-campus email rejected',
    $r['redirect'] === null && $r['error'] === 'Access restricted: Please enter your official FAST-NUCES campus email (@nu.edu.pk).');

// Wrong password increments limiter and returns invalid credentials error
$r = $authCtrl->handleLogin('POST', [
    'csrf_token' => $csrf,
    'email' => $regEmail,
    'password' => 'WrongPass123!'
]);
assertAPC('Login: wrong password returns invalid credentials error (status 200)',
    $r['redirect'] === null && $r['status'] === 200 && $r['error'] === 'Invalid email or password. Please try again.');

// Valid login succeeds, logs user into session, rotates CSRF token, redirects to index.php
$r = $authCtrl->handleLogin('POST', [
    'csrf_token' => $csrf,
    'email' => $regEmail,
    'password' => 'StrongPass123!'
]);
$csrf = $csrfHelper->token(); // rotated on login
$loggedInUser = $authHelper->user();
assertAPC('Login: valid credentials authenticate user and redirect to index.php',
    $r['redirect'] === 'index.php' && $r['status'] === 200 && $loggedInUser !== null && $loggedInUser['email'] === $regEmail);

// Lockout check: 5 consecutive failures on a dedicated email trigger HTTP 429
$lockEmail = "lock_{$uniq}@nu.edu.pk";
$guestLoginCtrl = AuthController::fromConnection($db, [], false);
for ($i = 1; $i <= 4; $i++) {
    $guestLoginCtrl->handleLogin('POST', ['csrf_token' => $csrf, 'email' => $lockEmail, 'password' => 'Wrong123!']);
}
$r5 = $guestLoginCtrl->handleLogin('POST', ['csrf_token' => $csrf, 'email' => $lockEmail, 'password' => 'Wrong123!']);
assertAPC('Login: 5th consecutive failed attempt locks account with HTTP 429',
    $r5['status'] === 429 && strpos($r5['error'], 'Too many failed login attempts.') === 0);

echo "--- PROFILE & PASSWORD (ProfileController::handleAction) ---" . PHP_EOL;

$userId = (int)$loggedInUser['id'];
// Unauthenticated call when no user is present (pass empty user array without destroying CLI session)
$unauthCtrl = ProfileController::fromConnection($db, []);
$r = $unauthCtrl->handleAction('POST', ['csrf_token' => $csrf, 'name' => 'Test']);
assertAPC('Profile: logged out redirects to login.php with null flash',
    $r === ['result' => null, 'redirect' => 'login.php']);

$profileCtrl = ProfileController::fromConnection($db, $loggedInUser);

// GET request returns Invalid request
$r = $profileCtrl->handleAction('GET', []);
assertAPC('Profile: GET -> Invalid request, redirects to profile.php',
    $r === [
        'result' => ['success' => false, 'message' => 'Invalid request.'],
        'redirect' => 'profile.php?id=' . $userId
    ]);

// Bad CSRF token
$r = $profileCtrl->handleAction('POST', ['csrf_token' => 'wrong', 'action' => 'update_profile', 'name' => 'New Name']);
assertAPC('Profile: bad CSRF rejected',
    $r['result'] === ['success' => false, 'message' => 'Security token expired. Please try again.']
    && $r['redirect'] === 'profile.php?id=' . $userId);

// Unknown action
$r = $profileCtrl->handleAction('POST', ['csrf_token' => $csrf, 'action' => 'delete_account']);
assertAPC('Profile: unknown action rejected',
    $r['result'] === ['success' => false, 'message' => 'Unknown action.']);

// Invalid profile update (empty name)
$r = $profileCtrl->handleAction('POST', [
    'csrf_token' => $csrf,
    'action' => 'update_profile',
    'name' => '   ',
    'bio' => 'Valid bio'
]);
assertAPC('Profile: empty name rejected with validation error',
    $r['result']['success'] === false
    && $r['result']['message'] === 'Could not update profile. Name is required and bio must be under 500 characters.');

// Valid profile update (name, bio, cover_style) + session name sync
$r = $profileCtrl->handleAction('POST', [
    'csrf_token' => $csrf,
    'action' => 'update_profile',
    'name' => "Updated Faiz {$uniq}",
    'bio' => 'OOP Controllers & AI-DLC',
    'cover_style' => 'cobalt'
]);
$updatedRow = $userModel->getById($userId);
assertAPC('Profile: valid update persists name, bio, cover_style and updates $_SESSION[user_name]',
    $r['result'] === ['success' => true, 'message' => 'Profile updated!']
    && $updatedRow['name'] === "Updated Faiz {$uniq}"
    && $updatedRow['bio'] === 'OOP Controllers & AI-DLC'
    && $updatedRow['cover_style'] === 'cobalt'
    && ($_SESSION['user_name'] ?? '') === "Updated Faiz {$uniq}");

// Password change: mismatched confirmation
$r = $profileCtrl->handleAction('POST', [
    'csrf_token' => $csrf,
    'action' => 'change_password',
    'current_password' => 'StrongPass123!',
    'new_password' => 'NewStrong123!',
    'confirm_password' => 'Different123!'
]);
assertAPC('Password: mismatched new/confirm rejected',
    $r['result'] === ['success' => false, 'message' => 'New passwords do not match.']);

// Password change: weak new password
$r = $profileCtrl->handleAction('POST', [
    'csrf_token' => $csrf,
    'action' => 'change_password',
    'current_password' => 'StrongPass123!',
    'new_password' => 'short',
    'confirm_password' => 'short'
]);
assertAPC('Password: weak new password rejected',
    $r['result'] === ['success' => false, 'message' => 'New password must be 8–128 chars, with 1 uppercase, 1 lowercase, 1 number, and 1 special char.']);

// Password change: wrong current password
$r = $profileCtrl->handleAction('POST', [
    'csrf_token' => $csrf,
    'action' => 'change_password',
    'current_password' => 'WrongCurrent123!',
    'new_password' => 'NewStrong123!',
    'confirm_password' => 'NewStrong123!'
]);
assertAPC('Password: wrong current password rejected',
    $r['result'] === ['success' => false, 'message' => 'Current password is incorrect, or the new password is the same as the current one.']);

// Seed two sessions in the `sessions` table for this user: current + other device
$sessHandler = new DbSessionHandler($db);
$currentSid = 'sess_current_' . $uniq;
$otherSid = 'sess_other_' . $uniq;
$sessHandler->write($currentSid, 'user_data_current');
$sessHandler->setUserId($currentSid, $userId);
$sessHandler->write($otherSid, 'user_data_other');
$sessHandler->setUserId($otherSid, $userId);

// Valid password change revokes other session while keeping current session alive
$r = $profileCtrl->handleAction('POST', [
    'csrf_token' => $csrf,
    'action' => 'change_password',
    'current_password' => 'StrongPass123!',
    'new_password' => 'NewStrong123!',
    'confirm_password' => 'NewStrong123!'
], $currentSid);
assertAPC('Password: valid change succeeds, revokes other sessions, preserves current session',
    $r['result'] === ['success' => true, 'message' => 'Password changed. Other sessions have been signed out.']
    && $sessHandler->read($currentSid) === 'user_data_current'
    && $sessHandler->read($otherSid) === ''
    && $userModel->verifyPassword($regEmail, 'NewStrong123!') !== false
    && $userModel->verifyPassword($regEmail, 'StrongPass123!') === false);

echo "--- LOGOUT (AuthController::handleLogout) ---" . PHP_EOL;

// GET logout does not log the user out
$r = $authCtrl->handleLogout('GET', ['csrf_token' => $csrf]);
assertAPC('Logout: GET redirects to index.php without logging out',
    $r === ['redirect' => 'index.php', 'logged_out' => false] && $authHelper->isLoggedIn());

// POST logout with bad CSRF does not log the user out
$r = $authCtrl->handleLogout('POST', ['csrf_token' => 'bad']);
assertAPC('Logout: bad CSRF redirects to index.php without logging out',
    $r === ['redirect' => 'index.php', 'logged_out' => false] && $authHelper->isLoggedIn());

// POST logout with valid CSRF logs the user out and redirects to login.php?logged_out=1
$r = $authCtrl->handleLogout('POST', ['csrf_token' => $csrf]);
assertAPC('Logout: valid POST logs out and redirects to login.php?logged_out=1',
    $r === ['redirect' => 'login.php?logged_out=1', 'logged_out' => true] && !$authHelper->isLoggedIn());

echo "--- PAGES DELEGATE TO CONTROLLERS ---" . PHP_EOL;
$root = __DIR__ . '/..';
$loginSrc = file_get_contents("{$root}/login.php");
assertAPC('login.php delegates to AuthController::handleLogin()',
    strpos($loginSrc, '->handleLogin(') !== false
    && preg_match('/new (User|LoginRateLimiter)\(|verifyPassword\(|recordFailure\(/', $loginSrc) === 0);

$signupSrc = file_get_contents("{$root}/signup.php");
assertAPC('signup.php delegates to AuthController::handleRegister()',
    strpos($signupSrc, '->handleRegister(') !== false
    && preg_match('/new User\(|->register\(|isValidPassword\(/', $signupSrc) === 0);

$logoutSrc = file_get_contents("{$root}/logout.php");
assertAPC('logout.php delegates to AuthController::handleLogout()',
    strpos($logoutSrc, '->handleLogout(') !== false
    && preg_match('/Auth::logout\(|Csrf::validate/', $logoutSrc) === 0);

$profileActionSrc = file_get_contents("{$root}/profile_action.php");
assertAPC('profile_action.php delegates to ProfileController::handleAction()',
    strpos($profileActionSrc, '->handleAction(') !== false
    && preg_match('/new (User|DbSessionHandler)\(|destroySessionsForUser\(|changePassword\(/', $profileActionSrc) === 0);

session_write_close();

echo PHP_EOL . "Result: {$passed}/{$total} passed" . PHP_EOL;
exit($passed === $total ? 0 : 1);
