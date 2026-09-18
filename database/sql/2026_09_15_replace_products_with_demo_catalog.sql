-- Replace the current product catalog with demonstration data.
-- Target: MySQL 8.x / MariaDB 10.6+
-- IMPORTANT: This permanently deletes all rows from products, product_units,
-- and product_families. Sales-request records and their saved product names remain.
-- Run only after 2026_09_15_product_catalog_hierarchy.sql.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
START TRANSACTION;

-- Existing sales_request_items references become NULL through ON DELETE SET NULL.
DELETE FROM `product_units`;
DELETE FROM `products`;
DELETE FROM `product_families`;

-- Category: Cucumber
INSERT INTO `product_families`
    (`name`, `description`, `image_path`, `is_active`, `created_at`, `updated_at`)
VALUES
    ('خيار', 'أصناف خيار طازجة للبيع المحلي والتصدير.', 'demo-products/cucumber.svg', 1, NOW(), NOW());
SET @cucumber_category_id = LAST_INSERT_ID();

INSERT INTO `products`
    (`product_family_id`, `name`, `sku`, `description`, `image_path`, `pdf_url`, `google_drive_url`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@cucumber_category_id, 'خيار شامي', 'CUC-SHAMI', 'ثمار متوسطة مقرمشة ومناسبة للسلطات.', 'demo-products/cucumber.svg', 'https://example.com/catalog/CUC-SHAMI.pdf', 'https://drive.google.com/drive/folders/DEMO-CUC-SHAMI', 1, NOW(), NOW());
SET @cucumber_shami_id = LAST_INSERT_ID();

INSERT INTO `product_units`
    (`product_id`, `label`, `unit_type`, `unit_value`, `price`, `needs_price_review`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@cucumber_shami_id, 'كيلوغرام', 'kg', 1.000, 0.75, 0, 1, NOW(), NOW()),
    (@cucumber_shami_id, 'صندوق 5 كغم', 'kg', 5.000, 3.50, 0, 1, NOW(), NOW());

INSERT INTO `products`
    (`product_family_id`, `name`, `sku`, `description`, `image_path`, `pdf_url`, `google_drive_url`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@cucumber_category_id, 'خيار إنجليزي', 'CUC-ENGLISH', 'ثمار طويلة ناعمة القشرة وقليلة البذور.', 'demo-products/cucumber.svg', 'https://example.com/catalog/CUC-ENGLISH.pdf', 'https://drive.google.com/drive/folders/DEMO-CUC-ENGLISH', 1, NOW(), NOW());
SET @cucumber_english_id = LAST_INSERT_ID();

INSERT INTO `product_units`
    (`product_id`, `label`, `unit_type`, `unit_value`, `price`, `needs_price_review`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@cucumber_english_id, 'كيلوغرام', 'kg', 1.000, 1.10, 0, 1, NOW(), NOW()),
    (@cucumber_english_id, 'صندوق 5 كغم', 'kg', 5.000, 5.00, 0, 1, NOW(), NOW());

-- Category: Pepper
INSERT INTO `product_families`
    (`name`, `description`, `image_path`, `is_active`, `created_at`, `updated_at`)
VALUES
    ('فلفل', 'تشكيلة من الفلفل الحلو والحار بأحجام مختلفة.', 'demo-products/pepper.svg', 1, NOW(), NOW());
SET @pepper_category_id = LAST_INSERT_ID();

INSERT INTO `products`
    (`product_family_id`, `name`, `sku`, `description`, `image_path`, `pdf_url`, `google_drive_url`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@pepper_category_id, 'فلفل حلو أخضر', 'PEP-GREEN', 'ثمار متماسكة بلون أخضر لامع.', 'demo-products/pepper.svg', 'https://example.com/catalog/PEP-GREEN.pdf', 'https://drive.google.com/drive/folders/DEMO-PEP-GREEN', 1, NOW(), NOW());
SET @pepper_green_id = LAST_INSERT_ID();

INSERT INTO `product_units`
    (`product_id`, `label`, `unit_type`, `unit_value`, `price`, `needs_price_review`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@pepper_green_id, 'كيلوغرام', 'kg', 1.000, 1.25, 0, 1, NOW(), NOW()),
    (@pepper_green_id, 'صندوق 5 كغم', 'kg', 5.000, 5.75, 0, 1, NOW(), NOW());

INSERT INTO `products`
    (`product_family_id`, `name`, `sku`, `description`, `image_path`, `pdf_url`, `google_drive_url`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@pepper_category_id, 'فلفل حار أحمر', 'PEP-HOT-RED', 'فلفل أحمر حار مناسب للطبخ والمخللات.', 'demo-products/pepper.svg', 'https://example.com/catalog/PEP-HOT-RED.pdf', 'https://drive.google.com/drive/folders/DEMO-PEP-HOT-RED', 1, NOW(), NOW());
SET @pepper_hot_id = LAST_INSERT_ID();

INSERT INTO `product_units`
    (`product_id`, `label`, `unit_type`, `unit_value`, `price`, `needs_price_review`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@pepper_hot_id, 'كيلوغرام', 'kg', 1.000, 1.50, 0, 1, NOW(), NOW()),
    (@pepper_hot_id, 'صندوق 5 كغم', 'kg', 5.000, 7.00, 0, 1, NOW(), NOW());

-- Category: Eggplant
INSERT INTO `product_families`
    (`name`, `description`, `image_path`, `is_active`, `created_at`, `updated_at`)
VALUES
    ('باذنجان', 'أصناف باذنجان منتقاة مناسبة للطبخ والتسويق.', 'demo-products/eggplant.svg', 1, NOW(), NOW());
SET @eggplant_category_id = LAST_INSERT_ID();

INSERT INTO `products`
    (`product_family_id`, `name`, `sku`, `description`, `image_path`, `pdf_url`, `google_drive_url`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@eggplant_category_id, 'باذنجان بلدي', 'EGG-LOCAL', 'ثمار كبيرة بلون بنفسجي داكن ولب متماسك.', 'demo-products/eggplant.svg', 'https://example.com/catalog/EGG-LOCAL.pdf', 'https://drive.google.com/drive/folders/DEMO-EGG-LOCAL', 1, NOW(), NOW());
SET @eggplant_local_id = LAST_INSERT_ID();

INSERT INTO `product_units`
    (`product_id`, `label`, `unit_type`, `unit_value`, `price`, `needs_price_review`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@eggplant_local_id, 'كيلوغرام', 'kg', 1.000, 0.90, 0, 1, NOW(), NOW()),
    (@eggplant_local_id, 'صندوق 5 كغم', 'kg', 5.000, 4.00, 0, 1, NOW(), NOW());

INSERT INTO `products`
    (`product_family_id`, `name`, `sku`, `description`, `image_path`, `pdf_url`, `google_drive_url`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@eggplant_category_id, 'باذنجان طويل', 'EGG-LONG', 'ثمار طويلة متجانسة ومناسبة للشوي.', 'demo-products/eggplant.svg', 'https://example.com/catalog/EGG-LONG.pdf', 'https://drive.google.com/drive/folders/DEMO-EGG-LONG', 1, NOW(), NOW());
SET @eggplant_long_id = LAST_INSERT_ID();

INSERT INTO `product_units`
    (`product_id`, `label`, `unit_type`, `unit_value`, `price`, `needs_price_review`, `is_active`, `created_at`, `updated_at`)
VALUES
    (@eggplant_long_id, 'كيلوغرام', 'kg', 1.000, 1.00, 0, 1, NOW(), NOW()),
    (@eggplant_long_id, 'صندوق 5 كغم', 'kg', 5.000, 4.50, 0, 1, NOW(), NOW());

COMMIT;

-- Expected result: 3 categories, 6 products, and 12 selling units.
SELECT
    (SELECT COUNT(*) FROM `product_families`) AS `categories_count`,
    (SELECT COUNT(*) FROM `products`) AS `products_count`,
    (SELECT COUNT(*) FROM `product_units`) AS `selling_units_count`;
