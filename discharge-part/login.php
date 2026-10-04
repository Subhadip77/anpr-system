<?php
declare(strict_types=1);
require_once 'database.php';
require_once 'helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($_SESSION['discharge_user_id'])) {
    header('Location: history.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statement = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
    $statement->execute([trim((string) ($_POST['username'] ?? ''))]);
    $user = $statement->fetch();
    if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['discharge_user_id'] = $user['id'];
        $_SESSION['discharge_username'] = $user['username'];
        $_SESSION['discharge_role'] = $user['role'];
        header('Location: history.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
$clinic = nursingHomeSettings($pdo);
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Discharge sign in | <?= e($clinic['nursing_home_name']) ?></title><link rel="stylesheet" href="style.css"><style>.login-card{max-width:430px;margin:12vh auto;background:#fff;border:1px solid var(--line);border-radius:7px;padding:30px;box-shadow:0 3px 14px #193d4d12}.login-card label{display:block;margin-top:16px;color:var(--muted);font:600 13px Arial,sans-serif}.login-card button{width:100%;margin-top:20px}.login-error{border-left:3px solid var(--danger);background:#fff2f0;color:var(--danger);padding:10px;font-family:Arial,sans-serif;font-size:13px}</style></head>
<body class="discharge-app">
<main class="login-card">
  <h1>Discharge records</h1>
  <p class="muted"><?= e($clinic['nursing_home_name']) ?> | Doctor and admin access</p>
  <?php if ($error): ?><p class="login-error"><?= e($error) ?></p><?php endif; ?>
  <form method="post" autocomplete="on">
    <label>Username<input name="username" required autofocus></label>
    <label>Password<input name="password" type="password" required></label>
    <button type="submit">Sign in</button>
  </form>
</main>
</body>
</html>
