<?php
declare(strict_types=1);
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/helpers.php';
$medicines = $pdo->query('SELECT medicine_name FROM discharge_medicines ORDER BY medicine_name')->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = 'Discharge Medicine';
require_once '../includes/header.php';
?>
<section class="card discharge-card">
  <div class="title-row"><div><h1>Discharge medicine</h1><p>Select medicines and add the instructions needed for this sheet.</p></div><a class="outline-link" href="../medicines/index.php">Manage library</a></div>
  <form method="post" action="print.php" target="_blank" id="dischargeForm">
    <table id="dischargeTable"><thead><tr><th>#</th><th>Medicine</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th><th></th></tr></thead><tbody></tbody></table>
    <label class="discharge-advice-field">Advice<textarea name="advice" rows="4" placeholder="Add dressing, follow-up, diet, rest, or other discharge instructions"></textarea></label>
    <div class="actions"><button type="button" class="outline" id="addDischargeMedicine">+ Add medicine</button><button type="submit" class="success">Print discharge medicine</button></div>
  </form>
</section>
<datalist id="medicineLibrary"><?php foreach ($medicines as $medicine): ?><option value="<?= e($medicine) ?>"><?php endforeach; ?></datalist>
<script>
const dischargeBody = document.querySelector('#dischargeTable tbody');
function addDischargeRow() {
  const row = document.createElement('tr');
  row.innerHTML = '<td></td><td><input name="medicine_name[]" list="medicineLibrary" required placeholder="Medicine name"></td><td><input name="dosage[]" placeholder="Optional"></td><td><input name="frequency[]" placeholder="Optional"></td><td><input name="duration[]" placeholder="Optional"></td><td><input name="instructions[]" placeholder="Optional"></td><td><button type="button" class="remove">Remove</button></td>';
  dischargeBody.append(row);
  renumberDischargeRows();
}
function renumberDischargeRows() { [...dischargeBody.rows].forEach((row, index) => row.cells[0].textContent = index + 1); }
document.querySelector('#addDischargeMedicine').addEventListener('click', addDischargeRow);
dischargeBody.addEventListener('click', event => { if (event.target.classList.contains('remove')) { event.target.closest('tr').remove(); renumberDischargeRows(); } });
addDischargeRow();
</script>
<?php require_once '../includes/footer.php'; ?>
