<?php
declare(strict_types=1);
require_once 'auth.php';
require_once 'database.php';
require_once 'helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$statement = $pdo->prepare('SELECT * FROM discharge_records WHERE id = ?');
$statement->execute([$id]);
$record = $statement->fetch();
if (!$record) { http_response_code(404); exit('Discharge record not found.'); }
$medicineStatement = $pdo->prepare('SELECT * FROM discharge_record_medicines WHERE discharge_record_id = ? ORDER BY id');
$medicineStatement->execute([$id]);
$medicines = $medicineStatement->fetchAll();
$clinic = nursingHomeSettings($pdo);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($record['record_number']) ?> | Discharge summary</title><link rel="stylesheet" href="style.css"></head>
<body class="print-page">
<article class="print-sheet">
  <header class="print-header"><h1><?= e($clinic['nursing_home_name']) ?></h1><p><?= e($clinic['establishment_type'] ?? '') ?><?= !empty($clinic['registration_no']) ? ' | Regd. ' . e($clinic['registration_no']) : '' ?><br><?= e($clinic['address'] ?? '') ?><br><?= e($clinic['phone'] ?? '') ?><?= !empty($clinic['email']) ? ' | ' . e($clinic['email']) : '' ?></p><h2>Discharge Summary</h2></header>
  <div class="print-meta"><p><b>Record no.:</b> <?= e($record['record_number']) ?><br><b>Patient registration no.:</b> <?= e($record['patient_registration_no']) ?></p><p><b>Discharge date:</b> <?= e(displayDate($record['discharge_date'])) ?><br><b>Discharge time:</b> <?= e($record['discharge_time']) ?></p></div>
  <section class="print-grid"><p><b>Patient name</b><?= e($record['patient_name']) ?></p><p><b>Age / Gender</b><?= e(trim($record['age'] . ' / ' . $record['gender'], ' /')) ?></p><p><b>Guardian name</b><?= e($record['guardian_name']) ?></p><p><b>Mobile</b><?= e($record['mobile']) ?></p><p class="print-full"><b>Address</b><?= e(formattedAddress($record)) ?></p><p><b>Admission</b><?= e(displayDate($record['admission_date'])) ?> <?= e($record['admission_time']) ?></p><p><b>Case type</b><?= e($record['case_type']) ?></p><p><b>Treated by doctor</b><?= e($record['treated_by_doctor']) ?></p><p><b>Reference doctor</b><?= e($record['reference_doctor']) ?></p><p class="print-full"><b>Diagnosis</b><?= nl2br(e($record['diagnosis_summary'])) ?></p><p class="print-full"><b>Other details</b><?= nl2br(e($record['other_details'])) ?></p></section>
  <section class="print-section"><h3>Discharge medicines</h3><table class="print-table"><thead><tr><th>#</th><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr></thead><tbody><?php foreach ($medicines as $index => $medicine): ?><tr><td><?= $index + 1 ?></td><td><?= e($medicine['medicine_name']) ?></td><td><?= e($medicine['dosage']) ?></td><td><?= e($medicine['frequency']) ?></td><td><?= e($medicine['duration']) ?></td><td><?= e($medicine['instructions']) ?></td></tr><?php endforeach; ?><?php if (!$medicines): ?><tr><td colspan="6">No medicines recorded.</td></tr><?php endif; ?></tbody></table></section>
  <div class="signature">Doctor's signature</div>
</article>
<script>window.print()</script>
</body></html>
