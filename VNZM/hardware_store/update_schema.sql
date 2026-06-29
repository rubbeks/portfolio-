USE hardware_store;

-- Add notes and status to transactions
ALTER TABLE transactions
    ADD COLUMN notes VARCHAR(255) DEFAULT NULL AFTER customer_name,
    ADD COLUMN status ENUM('active','voided') NOT NULL DEFAULT 'active' AFTER notes,
    ADD COLUMN receipt_number VARCHAR(20) DEFAULT NULL AFTER id;

-- Generate receipt numbers for existing transactions
UPDATE transactions SET receipt_number = CONCAT('VNZM-', LPAD(id, 4, '0')) WHERE receipt_number IS NULL;
