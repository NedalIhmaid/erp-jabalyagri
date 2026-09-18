-- Create an active administrator and assign the general_manager role.
-- Target: MySQL 8.x / MariaDB 10.6+
-- Run once from phpMyAdmin after taking a database backup.
--
-- Temporary login:
-- Email:    admin@jabalyagri.com
-- Password: Admin@2026!
-- IMPORTANT: Change the password immediately after the first login.

-- Match the collation used by the Laravel tables. This prevents phpMyAdmin's
-- connection collation from conflicting with users.email and roles.name.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
START TRANSACTION;

SET @admin_name = 'مدير النظام';
SET @admin_email = 'admin@jabalyagri.com';
SET @admin_password_hash = '$2y$12$0BLPFCP8sqOZ1W/wiXmJ2eNKU.p3XkBO.ITC/Uc5t0kjKsgzD4pY6';

-- Create the administrator only when the email does not already exist.
INSERT INTO `users` (
    `name`,
    `email`,
    `email_verified_at`,
    `password`,
    `phone`,
    `manager_id`,
    `is_active`,
    `locale`,
    `hire_date`,
    `gender`,
    `remember_token`,
    `created_at`,
    `updated_at`
)
SELECT
    @admin_name,
    @admin_email,
    NOW(),
    @admin_password_hash,
    NULL,
    NULL,
    1,
    'ar',
    NULL,
    'male',
    NULL,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `users` WHERE `email` = @admin_email
);

-- If the account already exists, activate it without replacing its password.
UPDATE `users`
SET
    `is_active` = 1,
    `email_verified_at` = COALESCE(`email_verified_at`, NOW()),
    `updated_at` = NOW()
WHERE `email` = @admin_email;

-- Ensure the application's administrator role exists.
INSERT INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'general_manager', 'web', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1
    FROM `roles`
    WHERE `name` = 'general_manager' AND `guard_name` = 'web'
);

SET @admin_user_id = (
    SELECT `id` FROM `users` WHERE `email` = @admin_email LIMIT 1
);

SET @general_manager_role_id = (
    SELECT `id`
    FROM `roles`
    WHERE `name` = 'general_manager' AND `guard_name` = 'web'
    LIMIT 1
);

-- Assign the role without duplicating an existing assignment.
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`)
SELECT @general_manager_role_id, 'App\\Models\\User', @admin_user_id
WHERE @admin_user_id IS NOT NULL
  AND @general_manager_role_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM `model_has_roles`
      WHERE `role_id` = @general_manager_role_id
        AND `model_type` = 'App\\Models\\User'
        AND `model_id` = @admin_user_id
  );

COMMIT;

-- Verification: this should return one active general_manager account.
SELECT
    `users`.`id`,
    `users`.`name`,
    `users`.`email`,
    `users`.`is_active`,
    `roles`.`name` AS `role`
FROM `users`
INNER JOIN `model_has_roles`
    ON `model_has_roles`.`model_id` = `users`.`id`
   AND `model_has_roles`.`model_type` = 'App\\Models\\User'
INNER JOIN `roles`
    ON `roles`.`id` = `model_has_roles`.`role_id`
WHERE `users`.`email` = @admin_email
  AND `roles`.`name` = 'general_manager';
