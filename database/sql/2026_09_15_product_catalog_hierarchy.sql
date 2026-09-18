-- Product catalog hierarchy upgrade
-- Target: MySQL 8.x / MariaDB 10.6+
-- Run once from phpMyAdmin after uploading the updated application files.
-- This script preserves existing products and sales-request history.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

CREATE TABLE IF NOT EXISTS `product_families` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `image_path` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `product_families_name_index` (`name`),
    INDEX `product_families_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `products`
    ADD COLUMN `product_family_id` BIGINT UNSIGNED NULL AFTER `id`,
    ADD COLUMN `description` TEXT NULL AFTER `sku`,
    ADD COLUMN `image_path` VARCHAR(255) NULL AFTER `description`,
    ADD COLUMN `pdf_url` VARCHAR(2048) NULL AFTER `image_path`,
    ADD COLUMN `google_drive_url` VARCHAR(2048) NULL AFTER `pdf_url`,
    ADD INDEX `products_product_family_id_is_active_index` (`product_family_id`, `is_active`),
    ADD CONSTRAINT `products_product_family_id_foreign`
        FOREIGN KEY (`product_family_id`) REFERENCES `product_families` (`id`)
        ON DELETE SET NULL;

-- Preserve existing products by placing them in one temporary category.
INSERT INTO `product_families`
    (`name`, `description`, `image_path`, `is_active`, `created_at`, `updated_at`)
SELECT
    'غير مصنف',
    'منتجات سابقة تحتاج إلى ربطها بتصنيف.',
    NULL,
    1,
    NOW(),
    NOW()
WHERE EXISTS (
    SELECT 1 FROM `products` WHERE `product_family_id` IS NULL
)
AND NOT EXISTS (
    SELECT 1 FROM `product_families` WHERE `name` = 'غير مصنف'
);

UPDATE `products`
SET `product_family_id` = (
    SELECT `id`
    FROM `product_families`
    WHERE `name` = 'غير مصنف'
    ORDER BY `id`
    LIMIT 1
)
WHERE `product_family_id` IS NULL;

-- Register both Laravel migrations so a later terminal deployment will not rerun them.
SET @migration_batch = (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_15_000000_create_product_families_and_add_catalog_fields', @migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_15_000000_create_product_families_and_add_catalog_fields'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_15_000001_group_existing_products', @migration_batch
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_15_000001_group_existing_products'
);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- Verification query: every product should have a category.
SELECT
    (SELECT COUNT(*) FROM `product_families`) AS `categories_count`,
    (SELECT COUNT(*) FROM `products`) AS `products_count`,
    (SELECT COUNT(*) FROM `products` WHERE `product_family_id` IS NULL) AS `uncategorized_products_count`;
