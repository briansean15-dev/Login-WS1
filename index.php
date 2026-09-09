<?php
// Initialize variables and error message holder
$error = '';
$username = '';
$last_name = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and capture input values
    $username = trim($_POST['username'] ?? '');
    $last_name = trim($_POST['last-name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember-me']);

    // Basic Validation
    if (empty($username) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Authenticate user against database here...
        
        // Example redirect on successful authentication
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <form class="input-container" action="index.php" method="POST" autocomplete="off">
        <h1>Login</h1>

        <?php if (!empty($error)): ?>
            <p style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <label for="username">First Name</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter First Name" required>

        <label for="last-name">Last Name</label>
        <input type="text" id="last-name" name="last-name" value="<?php echo htmlspecialchars($last_name); ?>" placeholder="Enter Last Name" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter Email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter Password" required>

        <label class="remember-me">
            <input type="checkbox" id="remember-me" name="remember-me">
            Remember Me
        </label>
        
        <button type="submit" id="login">Login</button>

        <p style="font-size: 14px; text-align: center;">Don't have an account? <a href="newaccount.php">New Account</a></p>
        <p style="font-size: 14px; text-align: center;">Forgot Password? <a href="forgotpassword.php">Reset Password</a></p>
    </form>
</body>
</html>