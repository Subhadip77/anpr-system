<?php
declare(strict_types=1);
require_once 'auth.php';
require_once 'database.php';
require_once 'helpers.php';

$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$record = null;
$medicines = [];
if ($editId) {
    $statement = $pdo->prepare('SELECT * FROM discharge_records WHERE id = ?');
    $statement->execute([$editId]);
    $record = $statement->fetch();
    if (!$record) { http_response_code(404); exit('Discharge record not found.'); }
    $medicineStatement = $pdo->prepare('SELECT * FROM discharge_record_medicines WHERE discharge_record_id = ? ORDER BY id');
    $medicineStatement->execute([$editId]);
    $medicines = $medicineStatement->fetchAll();
}
$value = static fn(string $field, string $default = ''): string => e((string) ($record[$field] ?? $default));
$pageTitle = $record ? 'Edit ' . $record['record_number'] : 'New Discharge Record';
require_once 'layout.php';
?>
<form id="dischargeRecordForm" action="save.php" method="post" autocomplete="off">
  <?php if ($record): ?><input type="hidden" name="record_id" value="<?= $record['id'] ?>"><?php endif; ?>
  <section class="discharge-card">
    <div class="stepper"><span class="active">1. Patient details</span><span>2. Discharge medicines</span></div>
    <fieldset id="patientStep">
      <h1><?= $record ? 'Edit discharge record' : 'New discharge record' ?></h1>
      <p class="muted">Complete the patient and clinical details before adding medicines.</p>
      <h2>Patient information</h2>
      <div class="form-grid">
        <label>Patient registration no.<input name="patient_registration_no" value="<?= $value('patient_registration_no') ?>"></label>
        <label>Patient name*<input name="patient_name" value="<?= $value('patient_name') ?>" required></label>
        <label>Age<input name="age" value="<?= $value('age') ?>" placeholder="e.g. 28y / 2d"></label>
        <label>Gender<select name="gender"><option value="">Select</option><option<?= $value('gender') === 'Male' ? ' selected' : '' ?>>Male</option><option<?= $value('gender') === 'Female' ? ' selected' : '' ?>>Female</option><option<?= $value('gender') === 'Other' ? ' selected' : '' ?>>Other</option></select></label>
        <label>Guardian name<input name="guardian_name" value="<?= $value('guardian_name') ?>"></label>
        <label>Mobile number<input name="mobile" value="<?= $value('mobile') ?>" inputmode="tel"></label>
      </div>
      <h2>Address</h2>
      <div class="form-grid">
        <label class="wide">Address<textarea name="address"><?= $value('address') ?></textarea></label>
        <label>Village<input name="village" value="<?= $value('village') ?>"></label>
        <label>Post office<input name="post_office" value="<?= $value('post_office') ?>"></label>
        <label>Police station<input name="police_station" value="<?= $value('police_station') ?>"></label>
        <label>District<input name="district" value="<?= $value('district') ?>"></label>
        <label>Block<input name="block" value="<?= $value('block') ?>"></label>
        <label>GP / Municipality<input name="gp_municipality" value="<?= $value('gp_municipality') ?>"></label>
        <label>PIN<input name="pin" value="<?= $value('pin') ?>" inputmode="numeric"></label>
      </div>
      <h2>Admission and discharge</h2>
      <div class="form-grid">
        <label>Admission date<input name="admission_date" type="date" value="<?= $value('admission_date') ?>"></label>
        <label>Admission time<input name="admission_time" type="time" value="<?= $value('admission_time') ?>"></label>
        <label>Discharge Date*<input name="discharge_date" type="date" value="<?= $value('discharge_date', date('Y-m-d')) ?>" required></label>
        <label>Discharge Time*<input name="discharge_time" type="time" value="<?= $value('discharge_time', date('H:i')) ?>" required></label>
      </div>
      <h2>Case and treatment</h2>
      <div class="form-grid">
        <label>Case type<input name="case_type" value="<?= $value('case_type') ?>" list="caseTypeList"></label>
        <label>Treated by doctor<input name="treated_by_doctor" value="<?= $value('treated_by_doctor') ?>"></label>
        <label>Reference doctor<input name="reference_doctor" value="<?= $value('reference_doctor') ?>"></label>
        <label>Diagnosis<textarea name="diagnosis_summary"><?= $value('diagnosis_summary') ?></textarea></label>
        <label class="wide">Other Details<textarea name="other_details" placeholder="Treatment summary, condition at discharge, follow-up, advice, or other details"><?= $value('other_details') ?></textarea></label>
      </div>
      <datalist id="caseTypeList"><option value="Maternity"><option value="Baby / Pediatric"><option value="General"><option value="Surgery"><option value="Orthopedic"><option value="Gynecology"><option value="Other"></datalist>
      <div class="actions"><button type="button" data-next="medicineStep">Next: Discharge medicines</button></div>
    </fieldset>
    <fieldset id="medicineStep" hidden>
      <h1>Discharge medicines</h1>
      <p class="muted">Add the medicines and instructions for this patient. You can remove any row before saving.</p>
      <div class="table-scroll"><table class="medicine-table" id="medicineTable"><thead><tr><th>#</th><th>Medicine*</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th><th></th></tr></thead><tbody></tbody></table></div>
      <button type="button" class="outline" id="addMedicine">+ Add medicine</button>
      <div class="actions"><button type="button" class="outline" data-back="patientStep">Back</button><button name="action" value="save" type="submit"><?= $record ? 'Update record' : 'Save record' ?></button><button name="action" value="save_print" type="submit" formtarget="_blank"><?= $record ? 'Update and print' : 'Save and print' ?></button></div>
    </fieldset>
  </section>
</form>
<script>window.existingDischargeMedicines = <?= json_encode($medicines, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) ?>;</script>
<script src="script.js"></script>
<?php require_once 'footer.php'; ?>
