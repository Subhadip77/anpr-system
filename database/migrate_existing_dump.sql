-- Run this only when you already imported the original supplied SQL dump.
ALTER TABLE bills
  ADD COLUMN reference_doctor VARCHAR(150) NULL AFTER treated_by_doctor,
  ADD COLUMN outside_medicine_remarks VARCHAR(255) NULL AFTER outside_medicine_amount;