<?php
$error = '';
$success = '';
$username = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash password and update database here...
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Success feedback redirect or message
        $success = "Password has been successfully reset.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="forgotpassword.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <form class="input-container" id="forgot-password-form" action="forgotpassword.php" method="POST" autocomplete="off">
        <h1>Forgot Password</h1>

        <?php if (!empty($error)): ?>
            <div class="error-banner"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="error-banner" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter Username" required>

        <label for="password">New Password</label>
        <input type="password" id="password" name="password" placeholder="Enter New Password" required minlength="6">

        <label for="confirm-password">Confirm Password</label>
        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm Password" required minlength="6">

        <button type="submit" id="reset-password">Reset Password</button>
        <p class="account-link">Remembered your password? <a href="index.php">Login</a></p>
    </form>

    <script src="forgotpassword.js"></script>
</body>
</html>