<?php
session_start();

// Redirect to login if user is not authenticated
if (!isset($_SESSION['user'])) {
    header("Location: index.php");
    exit();
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

<div id="login-screen">
  <div class="login-wrap">
    <div class="role-tabs">
      <button class="role-tab active" data-role="admin" type="button">Admin</button>
      <button class="role-tab" data-role="teacher" type="button">Teacher</button>
      <button class="role-tab" data-role="student" type="button">Student</button>
    </div>
    <div class="login-card">
      <span class="eyebrow">User LMS</span>
      <h1>Sign in</h1>
      <p class="sub" id="role-sub">Access the administrator console.</p>
      <p class="login-error" id="login-error">Enter a name.</p>
      <form id="login-form">
        <div class="field">
          <label for="name-input">Username</label>
          <input id="name-input" type="text" placeholder="e.g. Jordan Reyes" autocomplete="off">
        </div>
        <div class="field">
          <label for="pass-input">Password</label>
          <input id="pass-input" type="password" placeholder="Password" autocomplete="off">
        </div>
        <button type="submit" class="login-btn" id="login-btn">Enter admin console</button>
      </form>
      <div class="login-hint">demo - sample name &amp; password </div>
    </div>
  </div>
</div>

<div id="app">
  <aside class="sidebar">
    <div class="brand">
      <div class="display">User</div>
      <span class="role-chip" id="sidebar-role-chip"><?php echo htmlspecialchars($user['role'] ?? 'Admin'); ?></span>
    </div>
    <nav class="nav" id="nav-list"></nav>
    <a href="logout.php" class="logout-btn" id="logout-btn" style="text-decoration:none; display:flex; align-items:center; justify-content:center;"><span class="ico">⎋</span> Log out</a>
  </aside>

  <div class="main">
    <div class="topbar">
      <div>
        <h1 id="topbar-title">Overview</h1>
        <div class="date" id="topbar-date"></div>
      </div>
      <div class="user-pill">
        <div class="avatar" id="user-avatar"><?php echo htmlspecialchars(strtoupper(substr($user['username'] ?? 'U', 0, 1))); ?></div>
        <div>
          <div id="user-name" style="font-weight:600;"><?php echo htmlspecialchars($user['username'] ?? 'Username'); ?></div>
          <div id="user-role-label" style="color:var(--ink-soft);font-size:11.5px;"><?php echo htmlspecialchars($user['role_label'] ?? 'Administrator'); ?></div>
        </div>
      </div>
    </div>
    <div class="content" id="content"></div>
  </div>
</div>

<script src="dashboard.js"></script>
</body>
</html>