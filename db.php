<?php

try {
    $dbHost = '127.0.0.1';
    $dbName = 'login_ws1';
    $dbUser = 'root';
    $dbPass = '';

    $serverPdo = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass);
    $serverPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $serverPdo->exec("
        CREATE DATABASE IF NOT EXISTS {$dbName}
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_unicode_ci
    ");

    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'teacher', 'student') NOT NULL,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(150) NOT NULL DEFAULT '',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_history (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            username VARCHAR(80) NOT NULL,
            role ENUM('admin', 'teacher', 'student') NULL,
            status VARCHAR(20) NOT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS grades (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED NOT NULL,
            teacher_id INT UNSIGNED NULL,
            subject VARCHAR(120) NOT NULL,
            assessment VARCHAR(150) NOT NULL,
            score DECIMAL(5,2) UNSIGNED NOT NULL,
            feedback TEXT NULL,
            graded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_grades_student_date (student_id, graded_at),
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    seed_default_admin($pdo);
    seed_demo_accounts($pdo);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function seed_default_admin($pdo)
{
    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

    if ($adminCount === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password, role, name, email)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'admin',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin',
            'System Admin',
            'admin@school.local'
        ]);
    }
}

function demo_accounts()
{
    return [
        [
            'username' => 'demo_admin',
            'password' => 'demo123',
            'role' => 'admin',
            'name' => 'Demo Administrator',
            'email' => 'demo.admin@school.local',
            'label' => 'Admin'
        ],
        [
            'username' => 'demo_teacher',
            'password' => 'demo123',
            'role' => 'teacher',
            'name' => 'Demo Teacher',
            'email' => 'demo.teacher@school.local',
            'label' => 'Teacher'
        ],
        [
            'username' => 'demo_student',
            'password' => 'demo123',
            'role' => 'student',
            'name' => 'Demo Student',
            'email' => 'demo.student@school.local',
            'label' => 'Student'
        ]
    ];
}

function seed_demo_accounts($pdo)
{
    $exists = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $insert = $pdo->prepare("\n        INSERT INTO users (username, password, role, name, email)\n        VALUES (?, ?, ?, ?, ?)\n    ");

    foreach (demo_accounts() as $account) {
        $exists->execute([$account['username']]);

        if (!$exists->fetchColumn()) {
            $insert->execute([
                $account['username'],
                password_hash($account['password'], PASSWORD_DEFAULT),
                $account['role'],
                $account['name'],
                $account['email']
            ]);
        }
    }
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dashboard_path($role)
{
    switch ($role) {
        case 'admin':
            return 'admin/dashboard.php';
        case 'teacher':
            return 'teacher/dashboard.php';
        case 'student':
            return 'student/dashboard.php';
        default:
            return 'index.php';
    }
}

function current_user($pdo)
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT id, username, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function require_role($pdo, $role, $loginPath = '../index.php')
{
    $user = current_user($pdo);

    if (!$user || $user['role'] !== $role) {
        header("Location: " . $loginPath);
        exit();
    }

    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    return $user;
}

function record_login($pdo, $userId, $username, $role, $status)
{
    $stmt = $pdo->prepare("
        INSERT INTO login_history (user_id, username, role, status)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $username, $role, $status]);
}
?>
