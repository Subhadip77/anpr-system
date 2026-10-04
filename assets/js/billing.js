const $ = (selector, parent = document) => parent?.querySelector(selector) ?? null;
const $$ = (selector, parent = document) => parent ? [...parent.querySelectorAll(selector)] : [];
const money = value => Math.max(0, Number.parseFloat(value) || 0);
const amount = selector => money($(selector)?.value);

function updateTotals() {
  if (!$('#billForm')) return;
  const medicine = amount('#medicineBillAmount');
  const charges = $$('#itemsTable tbody tr').reduce((sum, row) => {
    const rowAmount = money($('.rate', row)?.value) * money($('.quantity', row)?.value);
    const amountField = $('.charge-amount', row);
    if (amountField) amountField.value = rowAmount.toFixed(2);
    return sum + rowAmount;
  }, 0);
  const otherCharges = amount('#otherChargesAmount');
  const gstMedicineCharges = amount('#gstMedicineCharges');
  const consumablesCharges = amount('#consumablesCharges');
  const gross = medicine + charges + gstMedicineCharges + otherCharges + consumablesCharges;
  const grossField = $('#grossAmount');
  const netField = $('#netAmount');
  const balanceField = $('#balanceAmount');
  if (!grossField || !netField || !balanceField) return;
  grossField.value = gross.toFixed(2);
  const discountPercentage = Math.min(100, amount('#discount'));
  const discount = gross * discountPercentage / 100;
  const net = Math.max(0, gross - discount);
  netField.value = net.toFixed(2);
  balanceField.value = Math.max(0, net - amount('#paidAmount')).toFixed(2);
}
function addRow(kind, item = null) {
  const table = $('#itemsTable');
  if (!table) return;
  const row = document.createElement('tr');
  row.innerHTML = `<td></td><td><input name="item_type[]" list="chargeTypes" required></td><td><input name="particular[]" required></td><td><input name="rate[]" class="rate" type="number" min="0" step=".01" value="0"></td><td><input name="quantity[]" class="quantity" type="number" min="0" step=".01" value="1"></td><td><input class="charge-amount" readonly></td><td><button type="button" class="remove">x</button></td>`;
  $('tbody', table).append(row);
  if (item) {
    const values = { 'item_type[]': item.item_type, 'particular[]': item.particular, 'rate[]': item.rate, 'quantity[]': item.quantity };
    $$('input', row).forEach(input => { if (Object.hasOwn(values, input.name)) input.value = values[input.name] ?? ''; });
  }
  renumber(table); calculateRow(row);
}
function renumber(table) { $$('tbody tr', table).forEach((row, index) => $('td', row).textContent = index + 1); }
function calculateRow(row) { if (row) updateTotals(); }
document.addEventListener('click', event => { const next = event.target.dataset.next, back = event.target.dataset.back; if (next) { const fieldset = event.target.closest('fieldset'); const field = fieldset ? $('#' + fieldset.id + ' :invalid') : null; if (field) return field.reportValidity(); show(next); } if (back) show(back); if (event.target.id === 'addItem') addRow('charge'); if (event.target.classList.contains('remove')) { const table = event.target.closest('table'); event.target.closest('tr')?.remove(); if (table) renumber(table); updateTotals(); } });
document.addEventListener('input', event => { if (event.target.matches('.rate,.quantity,#medicineBillAmount,#gstMedicineCharges,#otherChargesAmount,#consumablesCharges,#discount,#paidAmount')) updateTotals(); });
document.addEventListener('change', event => { if (event.target.matches('#medicineBillAmount,#gstMedicineCharges,#otherChargesAmount,#consumablesCharges,#discount,#paidAmount')) updateTotals(); });
function show(id) { $$('fieldset').forEach(fieldset => fieldset.hidden = fieldset.id !== id); $$('.stepper span').forEach((step, index) => step.classList.toggle('active', index === ['tab1','tab2'].indexOf(id))); window.scrollTo({top: 0, behavior: 'smooth'}); }
document.body.insertAdjacentHTML('beforeend', '<datalist id="chargeTypes"><option>Room</option><option>Doctor</option><option>Nursing</option><option>Delivery</option><option>Investigation</option><option>Procedure</option><option>Admission Fee</option><option>OT Charge</option></datalist>');
if (window.existingBillItems?.length) window.existingBillItems.filter(item => item.item_type !== 'Medicine').forEach(item => addRow('charge', item));
else addRow('charge');
updateTotals();
