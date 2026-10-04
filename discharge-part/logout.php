<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
unset($_SESSION['discharge_user_id'], $_SESSION['discharge_username'], $_SESSION['discharge_role']);
header('Location: login.php');
exit;
