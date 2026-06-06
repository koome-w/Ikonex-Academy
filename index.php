<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please provide username and password.';
    } else {
        $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid credentials. Please try again.';
    }
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Ikonex Academy — Login</title>
  <link rel="stylesheet" href="css/styles.css">
  <style>
    body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#eef5ff,#f8fbff);}
    .login-box{width:100%;max-width:420px;background:white;padding:30px;border-radius:18px;box-shadow:0 18px 40px rgba(34,60,80,.12);}
    .login-box h1{margin:0 0 18px;font-size:1.7rem;color:#1f2937;}
    .login-box .muted{margin-bottom:20px;display:block;color:#64748b;}
    .login-box form{display:grid;gap:14px;}
    .login-box input{width:100%;}
    .login-box .btn.primary{width:100%;padding:12px;}
    .login-box .error{color:#b91c1c;background:#fee2e2;padding:10px;border-radius:10px;font-size:0.95rem;}
    .login-footer{margin-top:16px;font-size:0.92rem;color:#64748b;text-align:center;}
  </style>
</head>
<body>
  <div class="login-box">
    <h1>Ikonex Academy Login</h1>
    <p class="muted">Secure access to the Student Management System.</p>
    <?php if ($error): ?>
      <div class="error"><?php echo sanitize($error); ?></div>
    <?php endif; ?>
    <form method="post" action="index.php">
      <label>Username</label>
      <input type="text" name="username" autocomplete="username" required>
      <label>Password</label>
      <input type="password" name="password" autocomplete="current-password" required>
      <button type="submit" class="btn primary">Sign In</button>
    </form>
    <div class="login-footer">Default admin user: <strong>admin</strong> / <strong>Admin@123</strong></div>
  </div>
</body>
</html>
