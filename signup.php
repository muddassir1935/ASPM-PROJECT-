<?php 
require_once 'config/Database.php';
require_once 'classes/User.php';
$message = "";

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = Database::getInstance()->getConnection();
    $user = new User($db);
    if($user->register($_POST['name'], $_POST['email'], $_POST['password'])) {
        $message = "Registration successful! You can now login.";
    } else {
        $message = "Registration failed. Email might already exist.";
    }
}
require_once 'includes/header.php'; 
?>
<h2>Sign Up</h2>
<p style="color:red;"><?php echo $message; ?></p>
<form method="POST">
    <input type="text" name="name" placeholder="Full Name" required><br><br>
    <input type="email" name="email" placeholder="Email Address" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Register</button>
</form>
<?php require_once 'includes/footer.php'; ?>