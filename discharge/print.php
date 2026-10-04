<?php
declare(strict_types=1);
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/helpers.php';

$names = $_POST['medicine_name'] ?? [];
$dosages = $_POST['dosage'] ?? [];
$frequencies = $_POST['frequency'] ?? [];
$durations = $_POST['duration'] ?? [];
$instructions = $_POST['instructions'] ?? [];
$advice = trim((string) ($_POST['advice'] ?? ''));
$items = [];
foreach ($names as $index => $name) {
    $name = trim((string) $name);
    if ($name === '') continue;
    $items[] = [
        'name' => $name,
        'dosage' => trim((string) ($dosages[$index] ?? '')),
        'frequency' => trim((string) ($frequencies[$index] ?? '')),
        'duration' => trim((string) ($durations[$index] ?? '')),
        'instructions' => trim((string) ($instructions[$index] ?? '')),
    ];
}
if (!$items) {
    header('Location: create.php');
    exit;
}
$clinic = nursingHomeSettings($pdo);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Discharge Medicine | <?= e($clinic['nursing_home_name']) ?></title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="print-page">
<article class="discharge-print-sheet">
  <header class="print-header"><h1><?= e($clinic['nursing_home_name']) ?></h1><p><?= e($clinic['establishment_type'] ?? '') ?><?= !empty($clinic['registration_no']) ? ' | Regd. ' . e($clinic['registration_no']) : '' ?><br><?= e($clinic['address'] ?? '') ?><br><?= e($clinic['phone'] ?? '') ?><?= !empty($clinic['email']) ? ' | ' . e($clinic['email']) : '' ?></p><h2>Discharge Medicine</h2></header>
  <p class="discharge-print-date"><b>Date:</b> <?= e(date('d/m/Y')) ?></p>
  <table class="print-table discharge-print-table"><thead><tr><th>#</th><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr></thead><tbody><?php foreach ($items as $index => $item): ?><tr><td><?= $index + 1 ?></td><td><?= e($item['name']) ?></td><td><?= e($item['dosage']) ?></td><td><?= e($item['frequency']) ?></td><td><?= e($item['duration']) ?></td><td><?= e($item['instructions']) ?></td></tr><?php endforeach; ?></tbody></table>
    <div class="discharge-advice"><b>Advice:</b><?php if ($advice !== ''): ?><p><?= nl2br(e($advice)) ?></p><?php endif; ?></div>
  <footer class="print-footer">Discharge medicine | Authorized signature</footer>
</article>
<script>window.print()</script>
</body></html>
