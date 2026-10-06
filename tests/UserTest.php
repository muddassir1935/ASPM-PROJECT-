<?php
/**
 * Quick Unit Test for User Class
 * Run via CLI: php tests/UserTest.php
 */
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';

echo "Running Unit Tests...\n";

// Use a separate test database or mock PDO in real scenarios
// For this demo, we ensure the class syntax is correct.
$db = Database::getInstance()->getConnection();
$user = new User($db);

$testEmail = "test_" . time() . "@nu.edu.pk";
$testPassword = "password123";

// Test 1: Registration
$isRegistered = $user->register("Test User", $testEmail, $testPassword);
echo $isRegistered ? "[PASS] User Registration\n" : "[FAIL] User Registration\n";

// Test 2: Verify Password
$isValid = $user->verifyPassword($testEmail, $testPassword);
echo $isValid ? "[PASS] Verify Password\n" : "[FAIL] Verify Password\n";

// Test 3: Invalid Password
$isInvalid = $user->verifyPassword($testEmail, "wrongpassword");
echo !$isInvalid ? "[PASS] Invalid Password Rejection\n" : "[FAIL] Invalid Password Rejection\n";