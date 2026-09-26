const $ = (selector, parent = document) => parent.querySelector(selector);
const $$ = (selector, parent = document) => [...parent.querySelectorAll(selector)];
const money = value => Math.max(0, Number.parseFloat(value) || 0);

function updateTotals() {
  const medicine = $$('.medicine-amount').reduce((sum, input) => sum + money(input.value), 0);
  const charges = $$('.charge-amount').reduce((sum, input) => sum + money(input.value), 0);
  $('#medicineTotal').value = medicine.toFixed(2);
  $('#medicineBillAmount').value = medicine.toFixed(2);
  const gross = medicine + charges + money($('#outsideMedicineAmount').value);
  $('#grossAmount').value = gross.toFixed(2);
  const net = Math.max(0, gross - money($('#discount').value));
  $('#netAmount').value = net.toFixed(2);
  $('#balanceAmount').value = Math.max(0, net - money($('#paidAmount').value)).toFixed(2);
}
function addRow(kind) {
  const table = kind === 'medicine' ? $('#medicineTable') : $('#itemsTable');
  const row = document.createElement('tr');
  row.innerHTML = kind === 'medicine'
    ? `<td></td><td><input name="medicine_qty[]" class="quantity" type="number" min="0" step="1" value="1"></td><td><input name="medicine_name[]" required></td><td><input name="medicine_batch[]"></td><td><input name="medicine_expiry[]" type="date"></td><td><input name="medicine_rate[]" class="rate" type="number" min="0" step=".01" value="0"></td><td><input class="medicine-amount" readonly></td><td><button type="button" class="remove">x</button></td>`
    : `<td></td><td><input name="item_type[]" list="chargeTypes" required></td><td><input name="particular[]" required></td><td><input name="rate[]" class="rate" type="number" min="0" step=".01" value="0"></td><td><input name="quantity[]" class="quantity" type="number" min="0" step=".01" value="1"></td><td><input class="charge-amount" readonly></td><td><button type="button" class="remove">x</button></td>`;
  $('tbody', table).append(row); renumber(table); calculateRow(row, kind);
}
function renumber(table) { $$('tbody tr', table).forEach((row, index) => $('td', row).textContent = index + 1); }
function calculateRow(row, kind) { $('.' + (kind === 'medicine' ? 'medicine-amount' : 'charge-amount'), row).value = (money($('.rate', row).value) * money($('.quantity', row).value)).toFixed(2); updateTotals(); }
document.addEventListener('click', event => { const next = event.target.dataset.next, back = event.target.dataset.back; if (next) { const field = $('#' + event.target.closest('fieldset').id + ' :invalid'); if (field) return field.reportValidity(); show(next); } if (back) show(back); if (event.target.id === 'addMedicine') addRow('medicine'); if (event.target.id === 'addItem') addRow('charge'); if (event.target.classList.contains('remove')) { const table = event.target.closest('table'); event.target.closest('tr').remove(); renumber(table); updateTotals(); } });
document.addEventListener('input', event => { if (event.target.matches('.rate,.quantity')) calculateRow(event.target.closest('tr'), event.target.closest('#medicineTable') ? 'medicine' : 'charge'); if (event.target.matches('#outsideMedicineAmount,#discount,#paidAmount')) updateTotals(); });
function show(id) { $$('fieldset').forEach(fieldset => fieldset.hidden = fieldset.id !== id); $$('.stepper span').forEach((step, index) => step.classList.toggle('active', index === ['tab1','tab2','tab3'].indexOf(id))); window.scrollTo({top: 0, behavior: 'smooth'}); }
document.body.insertAdjacentHTML('beforeend', '<datalist id="chargeTypes"><option>Room</option><option>Doctor</option><option>Nursing</option><option>Delivery</option><option>Investigation</option><option>Procedure</option><option>Admission Fee</option><option>OT Charge</option></datalist>'); addRow('medicine'); addRow('charge'); updateTotals();