<?php
declare(strict_types=1);
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/helpers.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statement = $pdo->prepare('SELECT * FROM bills WHERE id = ?');
$statement->execute([$id]);
$bill = $statement->fetch();
if (!$bill) { http_response_code(404); exit('Bill not found.'); }
$itemStatement = $pdo->prepare('SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id');
$itemStatement->execute([$id]);
$items = $itemStatement->fetchAll();
$medicineItems = array_values(array_filter($items, fn(array $item) => $item['item_type'] === 'Medicine'));
$summaryStatement = $pdo->prepare('SELECT * FROM bill_summary_items WHERE bill_id = ? ORDER BY id');
$summaryStatement->execute([$id]);
$summaryItems = $summaryStatement->fetchAll();
// Supports bills saved before bill_summary_items was populated.
if (!$summaryItems) {
		foreach ($items as $item) {
				if ($item['item_type'] !== 'Medicine') $summaryItems[] = ['charge_name' => $item['particular'], 'amount' => $item['amount']];
		}
		$medicineTotal = array_sum(array_column($medicineItems, 'amount'));
		if ($medicineTotal > 0) $summaryItems[] = ['charge_name' => 'Medicine charge', 'amount' => $medicineTotal];
		if ((float) $bill['outside_medicine_amount'] > 0) $summaryItems[] = ['charge_name' => 'Outside medicine' . ($bill['outside_medicine_remarks'] ? ' - ' . $bill['outside_medicine_remarks'] : ''), 'amount' => $bill['outside_medicine_amount']];
}
$clinic = nursingHomeSettings($pdo);
$address = implode(', ', array_filter([$bill['village'], $bill['post_office'], $bill['police_station'], $bill['district'], $bill['block'], $bill['gp_municipality']]));
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Bill <?= e($bill['bill_number']) ?></title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="print-page">
<?php function printHeader(array $clinic, array $bill, string $documentTitle): void { ?>
	<header class="print-header"><h1><?= e($clinic['nursing_home_name']) ?></h1><p><?= e($clinic['establishment_type']) ?><?= $clinic['registration_no'] ? ' | Regd. ' . e($clinic['registration_no']) : '' ?><br><?= e($clinic['address']) ?><br><?= e($clinic['phone']) ?> | <?= e($clinic['email']) ?></p><h2><?= e($documentTitle) ?></h2></header>
	<div class="print-meta"><p><b>Bill no.:</b> <?= e($bill['bill_number']) ?><br><b>Invoice no.:</b> <?= e($bill['invoice_no']) ?></p><p><b>Date:</b> <?= e($bill['bill_date']) ?><br><b>Time:</b> <?= e($bill['bill_time']) ?></p></div>
<?php } ?>

<article class="print-sheet">
	<?php printHeader($clinic, $bill, 'Patient Details'); ?>
	<section class="patient-print-grid"><p><b>Patient name:</b><br><?= e($bill['patient_name']) ?></p><p><b>Patient registration no.:</b><br><?= e($bill['patient_registration_no']) ?></p><p><b>UHID:</b><br><?= e($bill['uhid']) ?></p><p><b>Guardian name:</b><br><?= e($bill['guardian_name']) ?></p><p><b>Age / Gender:</b><br><?= e(trim(($bill['age'] ?? '') . ' ' . ($bill['gender'] ?? ''))) ?></p><p><b>Mobile:</b><br><?= e($bill['mobile']) ?></p><p class="print-full"><b>Address:</b><br><?= e($address) ?></p><p><b>Admission:</b><br><?= e($bill['admission_date']) ?> <?= e($bill['admission_time']) ?></p><p><b>Discharge:</b><br><?= e($bill['discharge_date']) ?> <?= e($bill['discharge_time']) ?></p><p><b>Case type:</b><br><?= e($bill['case_type']) ?></p><p><b>Treated by doctor:</b><br><?= e($bill['treated_by_doctor']) ?></p><p><b>Reference doctor:</b><br><?= e($bill['reference_doctor']) ?></p><p class="print-full"><b>Diagnosis:</b><br><?= nl2br(e($bill['diagnosis_summary'])) ?></p><p class="print-full"><b>Treatment / operation:</b><br><?= nl2br(e($bill['treated_operations'])) ?></p><p class="print-full"><b>Baby details:</b><br><?= nl2br(e($bill['baby_details'])) ?></p></section>
	<footer class="print-footer">Patient details | New Life Nursing Home</footer>
</article>

<article class="print-sheet">
	<?php printHeader($clinic, $bill, 'Medicine Bill'); ?>
	<p class="print-intro">Medicine required for patient: <b><?= e($bill['patient_name']) ?></b></p>
	<table class="print-table"><thead><tr><th>#</th><th>Qty</th><th>Medicine bill</th><th>Batch no.</th><th>Date of exp.</th><th>Rate</th><th>Amount</th></tr></thead><tbody><?php foreach ($medicineItems as $index => $item): ?><tr><td><?= $index + 1 ?></td><td><?= e($item['quantity']) ?></td><td><?= e($item['particular']) ?></td><td><?= e($item['batch_no']) ?></td><td><?= e($item['expiry_date']) ?></td><td><?= number_format((float) $item['rate'], 2) ?></td><td><?= number_format((float) $item['amount'], 2) ?></td></tr><?php endforeach; ?><?php if (!$medicineItems): ?><tr><td colspan="7">No medicine items recorded.</td></tr><?php endif; ?></tbody></table>
	<p class="print-total"><b>Total medicine bill: <?= number_format(array_sum(array_column($medicineItems, 'amount')), 2) ?></b></p>
	<footer class="print-footer">Medicine bill | Authorized signature</footer>
</article>

<article class="print-sheet">
	<?php printHeader($clinic, $bill, 'Final Bill'); ?>
	<p class="print-intro">Final charges for patient: <b><?= e($bill['patient_name']) ?></b></p>
	<table class="print-table summary-table"><thead><tr><th>#</th><th>Charge particulars</th><th>Amount</th></tr></thead><tbody><?php foreach ($summaryItems as $index => $item): ?><tr><td><?= $index + 1 ?></td><td><?= e($item['charge_name']) ?></td><td><?= number_format((float) $item['amount'], 2) ?></td></tr><?php endforeach; ?></tbody></table>
	<section class="print-totals"><p>Gross amount: <?= number_format((float) $bill['gross_amount'], 2) ?></p><p>Discount: <?= number_format((float) $bill['discount_amount'], 2) ?></p><p><b>Net amount: <?= number_format((float) $bill['net_amount'], 2) ?></b></p><p>Paid: <?= number_format((float) $bill['paid_amount'], 2) ?></p><p><b>Due: <?= number_format((float) $bill['balance_amount'], 2) ?></b></p><p>Payment mode: <?= e($bill['payment_mode']) ?></p></section>
	<p><b>Remarks:</b> <?= nl2br(e($bill['remarks'])) ?></p><footer class="print-footer">Final bill | Authorized signature</footer>
</article>
<script>window.print()</script></body></html>