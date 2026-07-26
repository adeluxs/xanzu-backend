-- Courier module + physical order delivery fields bootstrap (MySQL 8+)

-- 1) Courier partners master table for admin management
CREATE TABLE IF NOT EXISTS `courier_partners` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `admin_note` TEXT DEFAULT NULL,
    `short_description` VARCHAR(255) DEFAULT NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `courier_partners_name_unique` (`name`),
    UNIQUE KEY `courier_partners_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Extend orders table with courier and ETA fields
ALTER TABLE `orders`
    ADD COLUMN `estimated_delivery_from` DATETIME NULL AFTER `shipping_address`,
    ADD COLUMN `estimated_delivery_to` DATETIME NULL AFTER `estimated_delivery_from`,
    ADD COLUMN `courier_partner_id` BIGINT UNSIGNED NULL AFTER `estimated_delivery_to`,
    ADD COLUMN `tracking_number` VARCHAR(100) NULL AFTER `courier_partner_id`,
    ADD COLUMN `tracking_link` VARCHAR(500) NULL AFTER `tracking_number`,
    ADD COLUMN `delivery_note` TEXT NULL AFTER `tracking_link`;

ALTER TABLE `orders`
    ADD INDEX `orders_courier_partner_id_index` (`courier_partner_id`),
    ADD CONSTRAINT `orders_courier_partner_id_foreign`
        FOREIGN KEY (`courier_partner_id`) REFERENCES `courier_partners` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- 3) Insert admin permissions for courier partner module
INSERT INTO `permissions` (`name`, `category`, `guard_name`, `created_at`, `updated_at`)
SELECT 'courier-list', 'Courier Partner', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'courier-list');

INSERT INTO `permissions` (`name`, `category`, `guard_name`, `created_at`, `updated_at`)
SELECT 'courier-create', 'Courier Partner', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'courier-create');

INSERT INTO `permissions` (`name`, `category`, `guard_name`, `created_at`, `updated_at`)
SELECT 'courier-edit', 'Courier Partner', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'courier-edit');

INSERT INTO `permissions` (`name`, `category`, `guard_name`, `created_at`, `updated_at`)
SELECT 'courier-delete', 'Courier Partner', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'courier-delete');


-- Rollback SQL (run manually if required)
-- ALTER TABLE `orders` DROP FOREIGN KEY `orders_courier_partner_id_foreign`;
-- ALTER TABLE `orders` DROP INDEX `orders_courier_partner_id_index`;
-- ALTER TABLE `orders` DROP COLUMN `delivery_note`;
-- ALTER TABLE `orders` DROP COLUMN `tracking_link`;
-- ALTER TABLE `orders` DROP COLUMN `tracking_number`;
-- ALTER TABLE `orders` DROP COLUMN `courier_partner_id`;
-- ALTER TABLE `orders` DROP COLUMN `estimated_delivery_to`;
-- ALTER TABLE `orders` DROP COLUMN `estimated_delivery_from`;
-- DROP TABLE IF EXISTS `courier_partners`;
-- DELETE FROM `permissions` WHERE `name` IN ('courier-list', 'courier-create', 'courier-edit', 'courier-delete');
