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
foreach (($_POST['particular'] ?? []) as $index => $particular) {
    $particular = trim((string) $particular);
    if ($particular === '') continue;
    $rate = money($_POST['rate'][$index] ?? 0);
    $quantity = money($_POST['quantity'][$index] ?? 0);
    $items[] = ['item_type' => trim((string) ($_POST['item_type'][$index] ?? 'Other')) ?: 'Other', 'particular' => $particular, 'batch_no' => null, 'expiry_date' => null, 'rate' => $rate, 'quantity' => $quantity, 'amount' => round($rate * $quantity, 2)];
}

$medicineTotal = money($_POST['outside_medicine_amount'] ?? 0);
$otherTotal = array_sum(array_column(array_filter($items, fn(array $item) => $item['item_type'] !== 'Medicine'), 'amount'));
$otherCharges = money($_POST['other_charges_amount'] ?? 0);
$gstMedicineCharges = money($_POST['gst_medicine_charges'] ?? 0);
$consumablesCharges = money($_POST['consumables_charges'] ?? 0);
$gross = round($medicineTotal + $otherTotal + $gstMedicineCharges + $otherCharges + $consumablesCharges, 2);
$discountPercentage = min(max((float) ($_POST['discount'] ?? 0), 0), 100);
$discount = round($gross * $discountPercentage / 100, 2);
$net = round($gross - $discount, 2);
$paid = min(money($_POST['paid_amount'] ?? 0), $net);
$billId = filter_input(INPUT_POST, 'bill_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (isset($_POST['bill_id']) && !$billId) {
    http_response_code(400);
    exit('A valid bill is required for an update.');
}

try {
    $pdo->beginTransaction();
    $data = $_POST;
    foreach (['patient_registration_no','guardian_name','age','gender','mobile','pin','village','post_office','police_station','district','block','gp_municipality','case_type','treated_by_doctor','reference_doctor','diagnosis_summary','payment_mode','remarks'] as $field) $data[$field] = nullableString($data, $field);
    $data['address'] = trim((string) ($data['address'] ?? ''));
    foreach (['admission_date','discharge_date'] as $field) $data[$field] = nullableDate($data, $field);
    foreach (['admission_time','discharge_time'] as $field) $data[$field] = nullableTime($data, $field);
    $data += compact('invoiceNo', 'patientName', 'billDate', 'billTime', 'gross', 'discount', 'net', 'paid', 'medicineTotal', 'gstMedicineCharges', 'otherCharges', 'consumablesCharges');
    $parameters = ['invoice_no' => $invoiceNo, 'bill_date' => $billDate, 'bill_time' => $billTime, 'patient_name' => $patientName, 'gross_amount' => $gross, 'discount_amount' => $discount, 'net_amount' => $net, 'paid_amount' => $paid, 'balance_amount' => $net - $paid, 'outside_medicine_amount' => $medicineTotal, 'gst_medicine_charges' => $gstMedicineCharges, 'other_charges_amount' => $otherCharges, 'consumables_charges' => $consumablesCharges] + array_intersect_key($data, array_flip(['patient_registration_no','guardian_name','age','gender','mobile','address','pin','village','post_office','police_station','district','block','gp_municipality','admission_date','admission_time','discharge_date','discharge_time','case_type','treated_by_doctor','reference_doctor','diagnosis_summary','payment_mode','remarks']));
    if ($billId) {
        $statement = $pdo->prepare('UPDATE bills SET invoice_no = :invoice_no, bill_date = :bill_date, bill_time = :bill_time, patient_registration_no = :patient_registration_no, patient_name = :patient_name, guardian_name = :guardian_name, age = :age, gender = :gender, mobile = :mobile, address = :address, pin = :pin, village = :village, post_office = :post_office, police_station = :police_station, district = :district, block = :block, gp_municipality = :gp_municipality, admission_date = :admission_date, admission_time = :admission_time, discharge_date = :discharge_date, discharge_time = :discharge_time, case_type = :case_type, treated_by_doctor = :treated_by_doctor, reference_doctor = :reference_doctor, diagnosis_summary = :diagnosis_summary, gross_amount = :gross_amount, discount_amount = :discount_amount, net_amount = :net_amount, paid_amount = :paid_amount, balance_amount = :balance_amount, payment_mode = :payment_mode, outside_medicine_amount = :outside_medicine_amount, gst_medicine_charges = :gst_medicine_charges, other_charges_amount = :other_charges_amount, consumables_charges = :consumables_charges, remarks = :remarks WHERE id = :id');
        $parameters['id'] = $billId;
        $statement->execute($parameters);
        if ($statement->rowCount() === 0) {
            $exists = $pdo->prepare('SELECT 1 FROM bills WHERE id = ?');
            $exists->execute([$billId]);
            if (!$exists->fetchColumn()) throw new RuntimeException('Bill not found.');
        }
        foreach (['bill_diagnoses', 'bill_items', 'bill_summary_items'] as $table) $pdo->prepare("DELETE FROM {$table} WHERE bill_id = ?")->execute([$billId]);
    } else {
        $statement = $pdo->prepare('INSERT INTO bills (bill_number, invoice_no, bill_date, bill_time, patient_registration_no, patient_name, guardian_name, age, gender, mobile, address, pin, village, post_office, police_station, district, block, gp_municipality, admission_date, admission_time, discharge_date, discharge_time, case_type, treated_by_doctor, reference_doctor, diagnosis_summary, gross_amount, discount_amount, net_amount, paid_amount, balance_amount, payment_mode, outside_medicine_amount, gst_medicine_charges, other_charges_amount, consumables_charges, remarks) VALUES (:bill_number, :invoice_no, :bill_date, :bill_time, :patient_registration_no, :patient_name, :guardian_name, :age, :gender, :mobile, :address, :pin, :village, :post_office, :police_station, :district, :block, :gp_municipality, :admission_date, :admission_time, :discharge_date, :discharge_time, :case_type, :treated_by_doctor, :reference_doctor, :diagnosis_summary, :gross_amount, :discount_amount, :net_amount, :paid_amount, :balance_amount, :payment_mode, :outside_medicine_amount, :gst_medicine_charges, :other_charges_amount, :consumables_charges, :remarks)');
        $statement->execute(['bill_number' => nextBillNumber($pdo)] + $parameters);
        $billId = (int) $pdo->lastInsertId();
    }
    if ($data['diagnosis_summary']) $pdo->prepare('INSERT INTO bill_diagnoses (bill_id, diagnosis, treatment) VALUES (?, ?, ?)')->execute([$billId, $data['diagnosis_summary'], null]);
    $itemStatement = $pdo->prepare('INSERT INTO bill_items (bill_id, item_type, particular, batch_no, expiry_date, rate, quantity, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($items as $item) $itemStatement->execute([$billId, ...array_values($item)]);
    $summaryStatement = $pdo->prepare('INSERT INTO bill_summary_items (bill_id, charge_name, amount) VALUES (?, ?, ?)');
    foreach ($items as $item) {
        if ($item['item_type'] !== 'Medicine') {
            $summaryStatement->execute([$billId, $item['particular'], $item['amount']]);
        }
    }
    if ($medicineTotal > 0) $summaryStatement->execute([$billId, 'Other Medicine Charges', $medicineTotal]);
    if ($gstMedicineCharges > 0) $summaryStatement->execute([$billId, 'GST Medicine Charges', $gstMedicineCharges]);
    if ($otherCharges > 0) $summaryStatement->execute([$billId, 'Other charges', $otherCharges]);
    if ($consumablesCharges > 0) $summaryStatement->execute([$billId, 'Consumables Charges', $consumablesCharges]);
    $pdo->commit();
    header('Location: ' . (($_POST['action'] ?? '') === 'save_print' ? 'print.php?id=' . $billId : 'history.php'));
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    exit('Bill could not be saved. ' . e($exception->getMessage()));
}