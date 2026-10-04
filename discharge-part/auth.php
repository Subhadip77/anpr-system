<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['discharge_user_id'])) {
    header('Location: login.php');
    exit;
}
