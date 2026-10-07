<?php
/**
 * Comprehensive Unit Testing Suite for User Class (Sprint 1)
 * Agile SPM - CampusConnect
 * Run via CLI: php tests/UserTest.php
 */
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';

echo "=================================================\n";
echo "   CampusConnect - User Authentication Test Suite\n";
echo "=================================================\n\n";

$db = Database::getInstance()->getConnection();
$user = new User($db);

$totalTests = 0;
$passedTests = 0;

function runTest($testName, $actualResult, $expectedResult) {
    global $totalTests, $passedTests;
    $totalTests++;
    
    // Evaluate result strictly
    $passed = ($actualResult === $expectedResult);
    
    if ($passed) {
        $passedTests++;
        echo "✅ [PASS] " . $testName . "\n";
    } else {
        echo "❌ [FAIL] " . $testName . "\n";
        echo "    Expected: " . var_export($expectedResult, true) . "\n";
        echo "    Actual:   " . var_export($actualResult, true) . "\n";
    }
}

// ---------------------------------------------------------
// SECTION 1: SIGNUP (REGISTRATION) TESTS
// ---------------------------------------------------------
echo "--- SIGNUP TESTS ---\n";

$validEmail = "test_user_" . time() . "@nu.edu.pk";
$validPass = "securePass123";

// Test 1: Successful Registration
runTest("Test 1: Valid User Registration", 
    $user->register("Ali Khan", $validEmail, $validPass), 
    true
);

// Test 2: Duplicate Email Registration
runTest("Test 2: Duplicate Email Rejection", 
    $user->register("Ali Duplicate", $validEmail, "anotherPass"), 
    false
);

// Test 3: Empty Name
runTest("Test 3: Empty Name Rejection", 
    $user->register("", "new@nu.edu.pk", "pass123"), 
    false
);

// Test 4: Empty Email
runTest("Test 4: Empty Email Rejection", 
    $user->register("Ahmed", "", "pass123"), 
    false
);

// Test 5: Empty Password
runTest("Test 5: Empty Password Rejection", 
    $user->register("Ahmed", "ahmed@nu.edu.pk", ""), 
    false
);

// Test 6: SQL Injection attempt in Name field
// It should sanitize via Prepared Statements and register safely without crashing DB
$sqliEmail = "sqli_" . time() . "@nu.edu.pk";
runTest("Test 6: SQL Injection immunity in Registration", 
    $user->register("Robert'); DROP TABLE users;--", $sqliEmail, "pass123"), 
    true
);


// ---------------------------------------------------------
// SECTION 2: LOGIN (VERIFICATION) TESTS
// ---------------------------------------------------------
echo "\n--- LOGIN TESTS ---\n";

// Test 7: Valid Login Verification
$loginData = $user->verifyPassword($validEmail, $validPass);
runTest("Test 7: Valid Login (Returns User Data)", 
    is_array($loginData) && $loginData['email'] === $validEmail, 
    true
);

// Test 8: Login with Incorrect Password
runTest("Test 8: Login with Wrong Password Rejection", 
    $user->verifyPassword($validEmail, "wrongPass456"), 
    false
);

// Test 9: Login with Unregistered Email
runTest("Test 9: Login with Non-existent Email Rejection", 
    $user->verifyPassword("doesnotexist@nu.edu.pk", "pass123"), 
    false
);

// Test 10: Empty Password during Login
runTest("Test 10: Login with Empty Password Rejection", 
    $user->verifyPassword($validEmail, ""), 
    false
);

// Test 11: SQL Injection attempt in Login Email
// "admin@nu.edu.pk' OR '1'='1" -> Since we use PDO bindParam, it treats it as literal string and rejects login
runTest("Test 11: SQL Injection Immunity in Login", 
    $user->verifyPassword("admin@nu.edu.pk' OR '1'='1", "password"), 
    false
);

// Test 12: Password Hashing Format Check
// Verify the database stored a proper cryptographic hash, not plain text
$stmt = $db->prepare("SELECT password_hash FROM users WHERE email = :email");
$stmt->execute([':email' => $validEmail]);
$hash = $stmt->fetchColumn();
// Should start with $argon2i or $2y$ (bcrypt fallback)
$isHashedProperly = (strpos($hash, '$argon2i') === 0 || strpos($hash, '$2y$') === 0);
runTest("Test 12: Cryptographic Password Hash Verification", 
    $isHashedProperly, 
    true
);

// ---------------------------------------------------------
echo "\n=================================================\n";
echo "TEST SUMMARY: $passedTests / $totalTests Passed\n";
echo "=================================================\n";