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
$pageTitle = $record['record_number'];
require_once 'layout.php';
?>
<section class="discharge-card">
  <div class="title-row"><div><h1><?= e($record['record_number']) ?></h1><p class="muted">Discharge date: <?= e(displayDate($record['discharge_date'])) ?> <?= e($record['discharge_time']) ?></p></div><div class="actions"><a class="button outline" href="create.php?id=<?= $record['id'] ?>">Edit</a><a class="button" target="_blank" href="print.php?id=<?= $record['id'] ?>">Print</a></div></div>
  <div class="details-grid"><p><b>Patient registration no.</b><?= e($record['patient_registration_no']) ?></p><p><b>Patient name</b><?= e($record['patient_name']) ?></p><p><b>Age / Gender</b><?= e(trim($record['age'] . ' / ' . $record['gender'], ' /')) ?></p><p><b>Guardian name</b><?= e($record['guardian_name']) ?></p><p><b>Mobile</b><?= e($record['mobile']) ?></p><p><b>Address</b><?= e(formattedAddress($record)) ?></p><p><b>Admission</b><?= e(displayDate($record['admission_date'])) ?> <?= e($record['admission_time']) ?></p><p><b>Discharge</b><?= e(displayDate($record['discharge_date'])) ?> <?= e($record['discharge_time']) ?></p><p><b>Case type</b><?= e($record['case_type']) ?></p><p><b>Treated by doctor</b><?= e($record['treated_by_doctor']) ?></p><p><b>Reference doctor</b><?= e($record['reference_doctor']) ?></p><p class="full"><b>Diagnosis</b><?= nl2br(e($record['diagnosis_summary'])) ?></p><p class="full"><b>Other details</b><?= nl2br(e($record['other_details'])) ?></p></div>
  <h2>Discharge medicines</h2>
  <div class="table-scroll"><table><thead><tr><th>#</th><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr></thead><tbody><?php foreach ($medicines as $index => $medicine): ?><tr><td><?= $index + 1 ?></td><td><?= e($medicine['medicine_name']) ?></td><td><?= e($medicine['dosage']) ?></td><td><?= e($medicine['frequency']) ?></td><td><?= e($medicine['duration']) ?></td><td><?= e($medicine['instructions']) ?></td></tr><?php endforeach; ?><?php if (!$medicines): ?><tr><td colspan="6">No medicines recorded.</td></tr><?php endif; ?></tbody></table></div>
</section>
<?php require_once 'footer.php'; ?>
