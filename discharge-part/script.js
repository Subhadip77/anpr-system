const medicineBody = document.querySelector('#medicineTable tbody');
const existingMedicines = window.existingDischargeMedicines || [];

function addMedicineRow(item = {}) {
  const row = document.createElement('tr');
  row.innerHTML = `<td></td><td><input name="medicine_name[]" value="${escapeHtml(item.medicine_name || '')}" required placeholder="Medicine name"></td><td><input name="dosage[]" value="${escapeHtml(item.dosage || '')}" placeholder="Optional"></td><td><input name="frequency[]" value="${escapeHtml(item.frequency || '')}" placeholder="Optional"></td><td><input name="duration[]" value="${escapeHtml(item.duration || '')}" placeholder="Optional"></td><td><input name="instructions[]" value="${escapeHtml(item.instructions || '')}" placeholder="Optional"></td><td><button type="button" class="danger remove-medicine">Remove</button></td>`;
  medicineBody.append(row);
  renumberMedicines();
}

function escapeHtml(value) {
  return String(value).replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]));
}

function renumberMedicines() {
  [...medicineBody.rows].forEach((row, index) => { row.cells[0].textContent = index + 1; });
}

document.querySelector('#addMedicine').addEventListener('click', () => addMedicineRow());
medicineBody.addEventListener('click', event => {
  if (event.target.classList.contains('remove-medicine')) {
    event.target.closest('tr').remove();
    renumberMedicines();
  }
});
document.querySelectorAll('[data-next]').forEach(button => button.addEventListener('click', () => {
  document.querySelector('#patientStep').hidden = true;
  document.querySelector('#medicineStep').hidden = false;
  document.querySelector('.stepper span:first-child').classList.remove('active');
  document.querySelector('.stepper span:last-child').classList.add('active');
}));
document.querySelectorAll('[data-back]').forEach(button => button.addEventListener('click', () => {
  document.querySelector('#medicineStep').hidden = true;
  document.querySelector('#patientStep').hidden = false;
  document.querySelector('.stepper span:last-child').classList.remove('active');
  document.querySelector('.stepper span:first-child').classList.add('active');
}));
(existingMedicines.length ? existingMedicines : [{}]).forEach(addMedicineRow);
