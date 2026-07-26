-- Rename existing BNPL loan columns (MySQL 8+)
-- Run this once on databases that already have old column names.

ALTER TABLE `bnpl_item_loans`
    CHANGE COLUMN `upfront_amount` `initial_paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'paid instantly',
    CHANGE COLUMN `financed_amount` `final_amount_to_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'moved to credit',
    CHANGE COLUMN `outstanding_amount` `remaining_due_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'remaining due';
