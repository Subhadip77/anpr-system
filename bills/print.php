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
$chargeItems = array_values(array_filter($items, fn(array $item) => $item['item_type'] !== 'Medicine'));
$additionalCharges = [];
$medicineTotal = (float) $bill['outside_medicine_amount'] ?: array_sum(array_column($medicineItems, 'amount'));
if ($medicineTotal > 0) $additionalCharges[] = ['charge_name' => 'Other Medicine Charges', 'amount' => $medicineTotal];
if ((float) $bill['gst_medicine_charges'] > 0) $additionalCharges[] = ['charge_name' => 'GST Medicine Charges', 'amount' => $bill['gst_medicine_charges']];
if ((float) $bill['other_charges_amount'] > 0) $additionalCharges[] = ['charge_name' => 'Other charges', 'amount' => $bill['other_charges_amount']];
if ((float) $bill['consumables_charges'] > 0) $additionalCharges[] = ['charge_name' => 'Consumables Charges', 'amount' => $bill['consumables_charges']];
$clinic = nursingHomeSettings($pdo);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>Bill <?= e($bill['bill_number']) ?></title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="print-page">
<?php function printHeader(array $clinic, array $bill, string $documentTitle): void { ?>
	<header class="print-header"><h1><?= e($clinic['nursing_home_name']) ?></h1><p><?= e($clinic['establishment_type']) ?><?= $clinic['registration_no'] ? ' | Regd. ' . e($clinic['registration_no']) : '' ?><br><?= e($clinic['address']) ?><br><?= e($clinic['phone']) ?> | <?= e($clinic['email']) ?></p><h2><?= e($documentTitle) ?></h2></header>
	<div class="print-meta"><p><b>Bill no.:</b> <?= e($bill['bill_number']) ?><br><b>Invoice no.:</b> <?= e($bill['invoice_no']) ?></p><p><b>Date:</b> <?= e(displayDate($bill['bill_date'])) ?><br><b>Time:</b> <?= e($bill['bill_time']) ?></p></div>
<?php } ?>

<article class="print-sheet">
	<?php printHeader($clinic, $bill, 'Patient Details'); ?>
	<section class="patient-print-grid"><p><b>Patient registration no.:</b><br><?= e($bill['patient_registration_no']) ?></p><p><b>Patient name:</b><br><?= e($bill['patient_name']) ?></p><p><b>Guardian name:</b><br><?= e($bill['guardian_name']) ?></p><p><b>Age:</b><br><?= e($bill['age']) ?></p><p><b>Gender:</b><br><?= e($bill['gender']) ?></p><p><b>Mobile:</b><br><?= e($bill['mobile']) ?></p><p><b>Address:</b><br><?= e($bill['address']) ?></p><p><b>Village:</b><br><?= e($bill['village']) ?></p><p><b>Post office:</b><br><?= e($bill['post_office']) ?></p><p><b>Police station:</b><br><?= e($bill['police_station']) ?></p><p><b>District:</b><br><?= e($bill['district']) ?></p><p><b>Block:</b><br><?= e($bill['block']) ?></p><p><b>GP / Municipality:</b><br><?= e($bill['gp_municipality']) ?></p><p><b>PIN:</b><br><?= e($bill['pin']) ?></p><p><b>Admission:</b><br><?= e(displayDate($bill['admission_date'])) ?> <?= e($bill['admission_time']) ?></p><p><b>Discharge:</b><br><?= e(displayDate($bill['discharge_date'])) ?> <?= e($bill['discharge_time']) ?></p><p><b>Case type:</b><br><?= e($bill['case_type']) ?></p><p><b>Treated by doctor:</b><br><?= e($bill['treated_by_doctor']) ?></p><p><b>Reference doctor:</b><br><?= e($bill['reference_doctor']) ?></p><p class="print-full"><b>Diagnosis:</b><br><?= nl2br(e($bill['diagnosis_summary'])) ?></p></section>
	<footer class="print-footer">Patient details | New Life Nursing Home</footer>
</article>

<article class="print-sheet">
	<?php printHeader($clinic, $bill, 'Final Bill'); ?>
	<p class="print-intro">Final charges for patient: <b><?= e($bill['patient_name']) ?></b></p>
	<table class="print-table summary-table"><thead><tr><th>#</th><th>Type</th><th>Particular</th><th>Rate</th><th>Qty</th><th>Amount</th></tr></thead><tbody><?php foreach ($chargeItems as $index => $item): ?><tr><td><?= $index + 1 ?></td><td><?= e($item['item_type']) ?></td><td><?= e($item['particular']) ?></td><td><?= number_format((float) $item['rate'], 2) ?></td><td><?= e($item['quantity']) ?></td><td><?= number_format((float) $item['amount'], 2) ?></td></tr><?php endforeach; ?><?php if (!$chargeItems): ?><tr><td colspan="6">No other charges recorded.</td></tr><?php endif; ?></tbody></table>
	<?php if ($additionalCharges): ?><table class="print-table summary-table"><thead><tr><th>#</th><th>Charge particulars</th><th>Amount</th></tr></thead><tbody><?php foreach ($additionalCharges as $index => $charge): ?><tr><td><?= $index + 1 ?></td><td><?= e($charge['charge_name']) ?></td><td><?= number_format((float) $charge['amount'], 2) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
	<section class="print-totals"><div><span>Gross amount</span><strong><?= number_format((float) $bill['gross_amount'], 2) ?></strong></div><div><span>Discount(%)</span><strong><?= number_format((float) $bill['discount_amount'], 2) ?></strong></div><div class="total-emphasis"><span>Net amount</span><strong><?= number_format((float) $bill['net_amount'], 2) ?></strong></div><div><span>Paid</span><strong><?= number_format((float) $bill['paid_amount'], 2) ?></strong></div><div class="total-emphasis due"><span>Due</span><strong><?= number_format((float) $bill['balance_amount'], 2) ?></strong></div><div class="payment-mode"><span>Payment mode</span><strong><?= e($bill['payment_mode']) ?></strong></div></section>
	<p><b>Remarks:</b> <?= nl2br(e($bill['remarks'])) ?></p><footer class="print-footer">Final bill | Authorized signature</footer>
</article>
<script>window.print()</script></body></html>