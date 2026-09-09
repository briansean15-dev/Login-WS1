<?php
// Initialize variables and error/success message holders
$error = '';
$success = '';
$full_name = '';
$username = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and capture input values
    $full_name = trim($_POST['full-name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';

    // Backend Validation
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash password securely before saving
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Save user to database here...

        // Example redirect to login page upon success
        header("Location: index.php?registered=success");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <form class="input-container" id="register-form" action="newaccount.php" method="POST" autocomplete="off">
        <h1>Create Account</h1>

        <?php if (!empty($error)): ?>
            <div class="error-banner"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <label for="full-name">Full Name</label>
        <input type="text" id="full-name" name="full-name" value="<?php echo htmlspecialchars($full_name); ?>" placeholder="Enter Full Name" required>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter Username" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter Email" required>

        <label for="password">New Password</label>
        <input type="password" id="password" name="password" placeholder="Enter New Password" required minlength="6">

        <label for="confirm-password">Confirm Password</label>
        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm Password" required minlength="6">

        <button type="submit" id="register">Register</button>
        <p class="account-link">Already have an account? <a href="index.php">Login</a></p>
    </form>

    <script src="register.js"></script>
</body>
</html>