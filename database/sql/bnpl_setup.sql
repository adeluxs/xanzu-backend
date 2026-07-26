-- BNPL schema + settings bootstrap (MySQL 8+)
-- If table already exists without 'processing' in installment status enum, run:
-- ALTER TABLE `bnpl_installments`
--   MODIFY COLUMN `status` ENUM('pending','processing','paid','partial','overdue','cancelled') NOT NULL DEFAULT 'pending';

-- 1) Add BNPL markers to orders
ALTER TABLE `orders`
    ADD COLUMN `is_bnpl` TINYINT(1) NOT NULL DEFAULT 0 AFTER `order_date`,
    ADD COLUMN `bnpl_upfront_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `is_bnpl`;

-- 2) BNPL loan per purchased item
CREATE TABLE IF NOT EXISTS `bnpl_item_loans` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `order_item_id` BIGINT UNSIGNED NOT NULL,
    `credit_limit_split_id` BIGINT UNSIGNED NOT NULL,
    `total_item_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'item total',
    `initial_paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'paid instantly',
    `final_amount_to_pay` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'moved to credit',
    `remaining_due_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'remaining due',
    `total_split` INT UNSIGNED NOT NULL DEFAULT 1,
    `payment_interval_amount` INT UNSIGNED NOT NULL DEFAULT 1,
    `payment_interval_type` ENUM('day','week','month') NOT NULL DEFAULT 'month',
    `interest_rate_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `interest_rate_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'fixed',
    `delay_fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `delay_fine_type` ENUM('percentage','fixed') NOT NULL DEFAULT 'fixed',
    `status` ENUM('pending','partially_paid','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Installment schedule per BNPL loan
CREATE TABLE IF NOT EXISTS `bnpl_installments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bnpl_item_loan_id` BIGINT UNSIGNED NOT NULL,
    `installment_no` INT UNSIGNED NOT NULL,
    `principal_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `interest_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `late_fee_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_due_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `due_at` DATETIME NOT NULL,
    `paid_at` DATETIME NULL,
    `status` ENUM('pending','processing','paid','partial','overdue','cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Setting: admin can choose whether to collect initial BNPL installment at purchase
-- Update if exists:
UPDATE `settings`
SET `val` = '0', `type` = 'boolean'
WHERE `name` = 'bnpl_take_initial_installment';

-- Insert if not exists:
INSERT INTO `settings` (`name`, `val`, `type`, `created_at`, `updated_at`)
SELECT 'bnpl_take_initial_installment', '0', 'boolean', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `settings` WHERE `name` = 'bnpl_take_initial_installment'
);
