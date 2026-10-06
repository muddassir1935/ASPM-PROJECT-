<?php 
require_once 'config/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';
$message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = Database::getInstance()->getConnection();
    $user = new User($db);
    $userData = $user->verifyPassword($_POST['email'], $_POST['password']);
    
    if($userData) {
        Auth::login($userData);
        $message = "Login successful!";
        // header("Location: dashboard.php");
    } else {
        $message = "Invalid email or password.";
    }
}
require_once 'includes/header.php'; 
?>
<h2>Login</h2>
<p style="color:red;"><?php echo $message; ?></p>
<form method="POST">
    <input type="email" name="email" placeholder="Email Address" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>
<?php require_once 'includes/footer.php'; ?>