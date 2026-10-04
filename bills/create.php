<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/helpers.php';

$editId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$bill = null;
$existingItems = [];
if ($editId) {
  $billStatement = $pdo->prepare('SELECT * FROM bills WHERE id = ?');
  $billStatement->execute([$editId]);
  $bill = $billStatement->fetch();
  if (!$bill) { http_response_code(404); exit('Bill not found.'); }
  $itemStatement = $pdo->prepare('SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id');
  $itemStatement->execute([$editId]);
  $existingItems = $itemStatement->fetchAll();
}
$value = fn(string $field, string $default = ''): string => e((string) ($bill[$field] ?? $default));
$pageTitle = $bill ? 'Edit Bill ' . $bill['bill_number'] : 'Create Bill';
require_once '../includes/header.php';
?>
<form id="billForm" action="save.php" method="post" autocomplete="off">
  <?php if ($bill): ?><input type="hidden" name="bill_id" value="<?= $bill['id'] ?>"><?php endif; ?>
  <section class="card">
    <div class="stepper"><span class="active">1. Patient</span><span>2. Charges & payment</span></div>
    <fieldset id="tab1">
      <h1><?= $bill ? 'Edit patient bill' : 'Create patient bill' ?></h1>
      <div class="form-grid">
        <label>Invoice no.*<input name="invoice_no" type="number" min="1" value="<?= $value('invoice_no') ?>" required></label>
        <label>Bill date*<input name="bill_date" type="date" value="<?= $value('bill_date', date('Y-m-d')) ?>" required></label>
        <label>Bill time*<input name="bill_time" type="time" value="<?= $value('bill_time', date('H:i')) ?>" required></label>
      </div>
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
        <label>Address<textarea name="address"><?= $value('address') ?></textarea></label>
        <label>Village<input name="village" value="<?= $value('village') ?>"></label>
        <label>Post office<input name="post_office" value="<?= $value('post_office') ?>"></label>
        <label>Police station<input name="police_station" value="<?= $value('police_station') ?>"></label>
        <label>District<input name="district" value="<?= $value('district') ?>"></label>
        <label>Block<input name="block" value="<?= $value('block') ?>"></label>
        <label>GP / Municipality<input name="gp_municipality" value="<?= $value('gp_municipality') ?>"></label>
        <label>PIN<input name="pin" value="<?= $value('pin') ?>" inputmode="numeric"></label>
      </div>
      <h2>Admission / discharge</h2>
      <div class="form-grid">
        <label>Admission date<input name="admission_date" type="date" value="<?= $value('admission_date') ?>"></label>
        <label>Admission time<input name="admission_time" type="time" value="<?= $value('admission_time') ?>"></label>
        <label>Discharge date<input name="discharge_date" type="date" value="<?= $value('discharge_date') ?>"></label>
        <label>Discharge time<input name="discharge_time" type="time" value="<?= $value('discharge_time') ?>"></label>
      </div>
      <h2>Case & treatment</h2>
      <div class="form-grid">
        <label>Case type<input name="case_type" value="<?= $value('case_type') ?>" list="caseTypeList" placeholder="Type or choose a case type"></label>
        <label>Treated by doctor<input name="treated_by_doctor" value="<?= $value('treated_by_doctor') ?>"></label>
        <label>Reference doctor<input name="reference_doctor" value="<?= $value('reference_doctor') ?>"></label>
        <label>Diagnosis<textarea name="diagnosis_summary"><?= $value('diagnosis_summary') ?></textarea></label>
      </div>
      <datalist id="caseTypeList"><option value="Maternity"><option value="Baby / Pediatric"><option value="General"><option value="Surgery"><option value="Orthopedic"><option value="Gynecology"><option value="Other"></datalist>
      <div class="actions"><button type="button" data-next="tab2">Next: Charges & payment</button></div>
    </fieldset>
    <fieldset id="tab2" hidden>
      <h1>Other charges & payment</h1>
      <table id="itemsTable"><thead><tr><th>#</th><th>Type</th><th>Particular</th><th>Rate</th><th>Qty</th><th>Amount</th><th></th></tr></thead><tbody></tbody></table>
      <button type="button" class="outline" id="addItem">+ Add charge</button>
      <div class="form-grid totals">
        <label>Other Medicine Charges<input name="outside_medicine_amount" id="medicineBillAmount" type="number" step=".01" min="0" value="<?= $value('outside_medicine_amount') ?>"></label>
        <label>GST Medicine Charges<input name="gst_medicine_charges" id="gstMedicineCharges" type="number" step=".01" min="0" value="<?= $value('gst_medicine_charges', '0') ?>"></label>
        <label>Consumables Charges<input name="consumables_charges" id="consumablesCharges" type="number" step=".01" min="0" value="<?= $value('consumables_charges', '0') ?>"></label>
        <label>Other charges<input name="other_charges_amount" id="otherChargesAmount" type="number" step=".01" min="0" value="<?= $value('other_charges_amount', '0') ?>"></label>
        <label>Gross amount<input id="grossAmount" readonly></label>
        <label>Discount (%)<input name="discount" id="discount" type="number" step=".01" min="0" max="100" value="<?= $bill && (float) $bill['gross_amount'] > 0 ? e(number_format((float) $bill['discount_amount'] / (float) $bill['gross_amount'] * 100, 2, '.', '')) : '0' ?>"></label>
        <label>Net amount<input id="netAmount" readonly></label>
        <label>Paid amount<input name="paid_amount" id="paidAmount" type="number" step=".01" min="0" value="<?= $value('paid_amount', '0') ?>"></label>
        <label>Balance / due<input id="balanceAmount" readonly></label>
        <label>Payment mode<select name="payment_mode"><option<?= $value('payment_mode') === 'Cash' ? ' selected' : '' ?>>Cash</option><option<?= $value('payment_mode') === 'UPI' ? ' selected' : '' ?>>UPI</option><option<?= $value('payment_mode') === 'Card' ? ' selected' : '' ?>>Card</option><option<?= $value('payment_mode') === 'Bank Transfer' ? ' selected' : '' ?>>Bank Transfer</option><option<?= $value('payment_mode') === 'Other' ? ' selected' : '' ?>>Other</option></select></label>
        <label>Remarks<textarea name="remarks"><?= $value('remarks') ?></textarea></label>
      </div>
      <div class="actions"><button type="button" class="outline" data-back="tab1">Back</button><button name="action" value="save" type="submit"><?= $bill ? 'Update bill' : 'Save bill' ?></button><button name="action" value="save_print" type="submit" formtarget="_blank" class="success"><?= $bill ? 'Update & print' : 'Save & print' ?></button></div>
    </fieldset>
  </section>
</form>
<script>window.existingBillItems = <?= json_encode($existingItems, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) ?>;</script>
<script src="../assets/js/billing.js?v=3"></script>
<?php require_once '../includes/footer.php'; ?>
