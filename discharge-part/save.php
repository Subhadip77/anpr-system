<?php
declare(strict_types=1);
require_once 'auth.php';
require_once 'database.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: history.php');
    exit;
}

$patientName = nullableString($_POST, 'patient_name');
$dischargeDate = nullableDate($_POST, 'discharge_date');
$dischargeTime = nullableTime($_POST, 'discharge_time');
if (!$patientName || !$dischargeDate || !$dischargeTime) {
    exit('Patient name, discharge date and discharge time are required.');
}

$recordId = filter_input(INPUT_POST, 'record_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (isset($_POST['record_id']) && !$recordId) {
    http_response_code(400);
    exit('A valid discharge record is required for an update.');
}

$fields = ['patient_registration_no', 'age', 'gender', 'guardian_name', 'mobile', 'address', 'village', 'post_office', 'police_station', 'district', 'block', 'gp_municipality', 'pin', 'case_type', 'treated_by_doctor', 'reference_doctor', 'diagnosis_summary', 'other_details'];
$data = [];
foreach ($fields as $field) $data[$field] = nullableString($_POST, $field);
$data['admission_date'] = nullableDate($_POST, 'admission_date');
$data['admission_time'] = nullableTime($_POST, 'admission_time');
$data['patient_name'] = $patientName;
$data['discharge_date'] = $dischargeDate;
$data['discharge_time'] = $dischargeTime;

$medicineItems = [];
$names = $_POST['medicine_name'] ?? [];
foreach ($names as $index => $name) {
    $name = trim((string) $name);
    if ($name === '') continue;
    $medicineItems[] = [
        'medicine_name' => $name,
        'dosage' => nullableString($_POST['dosage'] ?? [], (string) $index),
        'frequency' => nullableString($_POST['frequency'] ?? [], (string) $index),
        'duration' => nullableString($_POST['duration'] ?? [], (string) $index),
        'instructions' => nullableString($_POST['instructions'] ?? [], (string) $index),
    ];
}

try {
    $pdo->beginTransaction();
    if ($recordId) {
        $data['id'] = $recordId;
        $statement = $pdo->prepare('UPDATE discharge_records SET patient_registration_no = :patient_registration_no, patient_name = :patient_name, age = :age, gender = :gender, guardian_name = :guardian_name, mobile = :mobile, address = :address, village = :village, post_office = :post_office, police_station = :police_station, district = :district, block = :block, gp_municipality = :gp_municipality, pin = :pin, admission_date = :admission_date, admission_time = :admission_time, discharge_date = :discharge_date, discharge_time = :discharge_time, case_type = :case_type, treated_by_doctor = :treated_by_doctor, reference_doctor = :reference_doctor, diagnosis_summary = :diagnosis_summary, other_details = :other_details WHERE id = :id');
        $statement->execute($data);
        $exists = $pdo->prepare('SELECT 1 FROM discharge_records WHERE id = ?');
        $exists->execute([$recordId]);
        if (!$exists->fetchColumn()) throw new RuntimeException('Discharge record not found.');
        $pdo->prepare('DELETE FROM discharge_record_medicines WHERE discharge_record_id = ?')->execute([$recordId]);
    } else {
        $numberStatement = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX(record_number, '-', -1) AS UNSIGNED)) FROM discharge_records WHERE record_number LIKE 'NLD-" . date('Y') . "-%'");
        $nextNumber = ((int) $numberStatement->fetchColumn()) + 1;
        $recordNumber = 'NLD-' . date('Y') . '-' . str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
        $statement = $pdo->prepare('INSERT INTO discharge_records (record_number, patient_registration_no, patient_name, age, gender, guardian_name, mobile, address, village, post_office, police_station, district, block, gp_municipality, pin, admission_date, admission_time, discharge_date, discharge_time, case_type, treated_by_doctor, reference_doctor, diagnosis_summary, other_details, created_by) VALUES (:record_number, :patient_registration_no, :patient_name, :age, :gender, :guardian_name, :mobile, :address, :village, :post_office, :police_station, :district, :block, :gp_municipality, :pin, :admission_date, :admission_time, :discharge_date, :discharge_time, :case_type, :treated_by_doctor, :reference_doctor, :diagnosis_summary, :other_details, :created_by)');
        $statement->execute(['record_number' => $recordNumber, 'created_by' => $_SESSION['discharge_user_id'] ?? null] + $data);
        $recordId = (int) $pdo->lastInsertId();
    }

    $medicineStatement = $pdo->prepare('INSERT INTO discharge_record_medicines (discharge_record_id, medicine_name, dosage, frequency, duration, instructions) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($medicineItems as $item) $medicineStatement->execute([$recordId, $item['medicine_name'], $item['dosage'], $item['frequency'], $item['duration'], $item['instructions']]);
    $pdo->commit();
    header('Location: ' . (($_POST['action'] ?? '') === 'save_print' ? 'print.php?id=' . $recordId : 'history.php'));
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    exit('Discharge record could not be saved. ' . e($exception->getMessage()));
}
