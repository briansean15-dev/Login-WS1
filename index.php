<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

$error = '';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($_SESSION['user_id'])) {
    $loggedInUser = current_user($pdo);

    if ($loggedInUser) {
        header("Location: " . dashboard_path($loggedInUser['role']));
        exit();
    }

    session_unset();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            record_login($pdo, $user['id'], $user['username'], $user['role'], 'success');

            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    exit();
                case 'teacher':
                    header("Location: teacher/dashboard.php");
                    exit();
                case 'student':
                    header("Location: student/dashboard.php");
                    exit();
                default:
                    $error = "Unknown user role.";
            }
        } else {
            record_login($pdo, null, $username, null, 'failed');
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
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
    <form class="input-container" method="POST" autocomplete="off">
        <h1>Login</h1>

        <?php if (!empty($error)): ?>
            <p class="error-banner"><?php echo e($error); ?></p>
        <?php endif; ?>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" placeholder="Enter username" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter password" required>

        <button type="submit" id="login">Login</button>

        <p class="account-link"><a href="forgotpassword.php">Forgot password?</a></p>
        <p class="account-link">No account yet? <a href="newaccount.php">Create account</a></p>

        <section class="demo-accounts" aria-labelledby="demo-accounts-title">
            <h2 id="demo-accounts-title">Demo accounts</h2>
            <p class="demo-accounts-intro">Choose an account to try each role.</p>

            <?php foreach (demo_accounts() as $demoAccount): ?>
                <div class="demo-account">
                    <div class="demo-account-heading">
                        <strong><?php echo e($demoAccount['label']); ?></strong>
                        <button
                            type="button"
                            class="demo-use-button"
                            data-username="<?php echo e($demoAccount['username']); ?>"
                            data-password="<?php echo e($demoAccount['password']); ?>"
                        >Use account</button>
                    </div>
                    <p><span>Username</span><code><?php echo e($demoAccount['username']); ?></code></p>
                    <p><span>Password</span><code><?php echo e($demoAccount['password']); ?></code></p>
                </div>
            <?php endforeach; ?>
        </section>
    </form>

    <script>
        document.querySelectorAll('.demo-use-button').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById('username').value = button.dataset.username;
                document.getElementById('password').value = button.dataset.password;
                document.getElementById('password').focus();
            });
        });
    </script>
</body>
</html>
