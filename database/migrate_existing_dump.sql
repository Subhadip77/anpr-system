-- Run this only when you already imported the original supplied SQL dump.
ALTER TABLE bills
  ADD COLUMN reference_doctor VARCHAR(150) NULL AFTER treated_by_doctor,
  ADD COLUMN outside_medicine_remarks VARCHAR(255) NULL AFTER outside_medicine_amount;
ALTER TABLE bills
  ADD COLUMN other_charges_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER outside_medicine_remarks,
  ADD COLUMN consumables_charges DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER other_charges_amount,
  ADD COLUMN gst_medicine_charges DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER consumables_charges;
ALTER TABLE bills
  ADD COLUMN address TEXT NULL AFTER mobile,
  ADD COLUMN pin VARCHAR(20) NULL AFTER address;
UPDATE bills
SET other_charges_amount = outside_medicine_amount
WHERE outside_medicine_amount > 0;