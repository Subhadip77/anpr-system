<?php
require_once __DIR__ . '/helpers.php';
$clinic = nursingHomeSettings($pdo);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'Discharge Records') ?> | <?= e($clinic['nursing_home_name']) ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="discharge-app">
<header class="discharge-header">
  <div><strong><?= e($clinic['nursing_home_name']) ?></strong><small>Doctor discharge records</small></div>
  <nav><a href="history.php">Records</a><a href="create.php">New discharge</a><a href="logout.php">Logout</a></nav>
</header>
<main class="discharge-wrap">
