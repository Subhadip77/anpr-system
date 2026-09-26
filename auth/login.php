<?php
declare(strict_types=1);
require_once '../config/database.php';
require_once '../includes/helpers.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statement = $pdo->prepare('SELECT id, username, password FROM users WHERE username = ?');
    $statement->execute([trim((string) ($_POST['username'] ?? ''))]);
    $user = $statement->fetch();
    if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: ../index.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in | New Life Nursing Home</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="login-page"><main class="login-card"><h1>New Life Nursing Home</h1><p>Billing system</p><?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?><form method="post"><label>Username<input name="username" required autofocus></label><label>Password<input name="password" type="password" required></label><button>Sign in</button></form></main></body></html>