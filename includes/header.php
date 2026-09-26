<?php
require_once __DIR__ . '/helpers.php';
$clinic = nursingHomeSettings($pdo);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($pageTitle ?? 'Billing') ?> | <?= e($clinic['nursing_home_name']) ?></title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><header class="masthead"><div><strong><?= e($clinic['nursing_home_name']) ?></strong><small><?= e($clinic['establishment_type'] ?? '') ?><?= !empty($clinic['registration_no']) ? ' | Regd. ' . e($clinic['registration_no']) : '' ?></small></div><nav><a href="create.php">New Bill</a><a href="history.php">History</a><a href="../auth/logout.php">Logout</a></nav></header><main class="page-wrap">