<?php
declare(strict_types=1);
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

$invoiceNo = filter_input(INPUT_POST, 'invoice_no', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$patientName = nullableString($_POST, 'patient_name');
$billDate = nullableDate($_POST, 'bill_date');
$billTime = nullableTime($_POST, 'bill_time');
if (!$invoiceNo || !$patientName || !$billDate || !$billTime) {
    exit('Invoice number, bill date, bill time and patient name are required.');
}

$items = [];
foreach (($_POST['medicine_name'] ?? []) as $index => $name) {
    $name = trim((string) $name);
    if ($name === '') continue;
    $rate = money($_POST['medicine_rate'][$index] ?? 0);
    $quantity = money($_POST['medicine_qty'][$index] ?? 0);
    $items[] = ['item_type' => 'Medicine', 'particular' => $name, 'batch_no' => nullableString(['v' => $_POST['medicine_batch'][$index] ?? ''], 'v'), 'expiry_date' => nullableDate(['v' => $_POST['medicine_expiry'][$index] ?? ''], 'v'), 'rate' => $rate, 'quantity' => $quantity, 'amount' => round($rate * $quantity, 2)];
}
foreach (($_POST['particular'] ?? []) as $index => $particular) {
    $particular = trim((string) $particular);
    if ($particular === '') continue;
    $rate = money($_POST['rate'][$index] ?? 0);
    $quantity = money($_POST['quantity'][$index] ?? 0);
    $items[] = ['item_type' => trim((string) ($_POST['item_type'][$index] ?? 'Other')) ?: 'Other', 'particular' => $particular, 'batch_no' => null, 'expiry_date' => null, 'rate' => $rate, 'quantity' => $quantity, 'amount' => round($rate * $quantity, 2)];
}

$medicineTotal = array_sum(array_column(array_filter($items, fn(array $item) => $item['item_type'] === 'Medicine'), 'amount'));
$otherTotal = array_sum(array_column(array_filter($items, fn(array $item) => $item['item_type'] !== 'Medicine'), 'amount'));
$outsideMedicine = money($_POST['outside_medicine_amount'] ?? 0);
$gross = round($medicineTotal + $otherTotal + $outsideMedicine, 2);
$discount = min(money($_POST['discount'] ?? 0), $gross);
$net = round($gross - $discount, 2);
$paid = min(money($_POST['paid_amount'] ?? 0), $net);

try {
    $pdo->beginTransaction();
    $statement = $pdo->prepare('INSERT INTO bills (bill_number, invoice_no, bill_date, bill_time, patient_name, patient_registration_no, uhid, guardian_name, age, gender, mobile, village, post_office, police_station, district, block, gp_municipality, admission_date, admission_time, discharge_date, discharge_time, case_type, treated_by_doctor, reference_doctor, diagnosis_summary, treated_operations, baby_details, gross_amount, discount_amount, net_amount, paid_amount, balance_amount, payment_mode, outside_medicine_amount, outside_medicine_remarks, remarks) VALUES (:bill_number, :invoice_no, :bill_date, :bill_time, :patient_name, :patient_registration_no, :uhid, :guardian_name, :age, :gender, :mobile, :village, :post_office, :police_station, :district, :block, :gp_municipality, :admission_date, :admission_time, :discharge_date, :discharge_time, :case_type, :treated_by_doctor, :reference_doctor, :diagnosis_summary, :treated_operations, :baby_details, :gross_amount, :discount_amount, :net_amount, :paid_amount, :balance_amount, :payment_mode, :outside_medicine_amount, :outside_medicine_remarks, :remarks)');
    $data = $_POST;
    foreach (['patient_registration_no','uhid','guardian_name','age','gender','mobile','village','post_office','police_station','district','block','gp_municipality','case_type','treated_by_doctor','reference_doctor','diagnosis_summary','treated_operations','baby_details','payment_mode','outside_medicine_remarks','remarks'] as $field) $data[$field] = nullableString($data, $field);
    foreach (['admission_date','discharge_date'] as $field) $data[$field] = nullableDate($data, $field);
    foreach (['admission_time','discharge_time'] as $field) $data[$field] = nullableTime($data, $field);
    $data += compact('invoiceNo', 'patientName', 'billDate', 'billTime', 'gross', 'discount', 'net', 'paid', 'outsideMedicine');
    $statement->execute(['bill_number' => nextBillNumber($pdo), 'invoice_no' => $invoiceNo, 'bill_date' => $billDate, 'bill_time' => $billTime, 'patient_name' => $patientName, 'gross_amount' => $gross, 'discount_amount' => $discount, 'net_amount' => $net, 'paid_amount' => $paid, 'balance_amount' => $net - $paid, 'outside_medicine_amount' => $outsideMedicine] + array_intersect_key($data, array_flip(['patient_registration_no','uhid','guardian_name','age','gender','mobile','village','post_office','police_station','district','block','gp_municipality','admission_date','admission_time','discharge_date','discharge_time','case_type','treated_by_doctor','reference_doctor','diagnosis_summary','treated_operations','baby_details','payment_mode','outside_medicine_remarks','remarks'])));
    $billId = (int) $pdo->lastInsertId();
    if ($data['diagnosis_summary']) $pdo->prepare('INSERT INTO bill_diagnoses (bill_id, diagnosis, treatment) VALUES (?, ?, ?)')->execute([$billId, $data['diagnosis_summary'], $data['treated_operations']]);
    $itemStatement = $pdo->prepare('INSERT INTO bill_items (bill_id, item_type, particular, batch_no, expiry_date, rate, quantity, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($items as $item) $itemStatement->execute([$billId, ...array_values($item)]);
    $summaryStatement = $pdo->prepare('INSERT INTO bill_summary_items (bill_id, charge_name, amount) VALUES (?, ?, ?)');
    foreach ($items as $item) {
        if ($item['item_type'] !== 'Medicine') {
            $summaryStatement->execute([$billId, $item['particular'], $item['amount']]);
        }
    }
    if ($medicineTotal > 0) {
        $summaryStatement->execute([$billId, 'Medicine charge', $medicineTotal]);
    }
    if ($outsideMedicine > 0) {
        $summaryStatement->execute([$billId, 'Outside medicine' . ($data['outside_medicine_remarks'] ? ' - ' . $data['outside_medicine_remarks'] : ''), $outsideMedicine]);
    }
    $pdo->commit();
    header('Location: print.php?id=' . $billId);
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    exit('Bill could not be saved. ' . e($exception->getMessage()));
}