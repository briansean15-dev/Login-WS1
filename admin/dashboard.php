<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db.php';

$currentUser = require_role($pdo, 'admin', '../index.php');

try {
    $usersStmt = $pdo->query("SELECT id, username, name, role, email, created_at FROM users ORDER BY id ASC");
    $allUsers = $usersStmt->fetchAll();

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <p class="eyebrow">Admin Dashboard</p>
                <h1>Welcome, <?php echo e($currentUser['name']); ?></h1>
            </div>
            <a class="logout-btn" href="../index.php?logout=true">Logout</a>
        </div>

        <hr>

        <h2>Registered Users</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Created</th>
                </tr>
                <?php foreach ($allUsers as $u): ?>
                <tr>
                    <td><?php echo e($u['id']); ?></td>
                    <td><?php echo e($u['username']); ?></td>
                    <td><?php echo e($u['name']); ?></td>
                    <td><?php echo e($u['role']); ?></td>
                    <td><?php echo e($u['email']); ?></td>
                    <td><?php echo e($u['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>
</body>
</html>
