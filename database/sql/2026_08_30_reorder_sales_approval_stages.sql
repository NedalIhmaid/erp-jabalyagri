-- Al-Jabali ERP
-- Reorder unfinished sales approvals to:
-- Warehouse -> Financial -> Purchasing -> Sales (final approval)
--
-- Safe to import once through phpMyAdmin. Re-running it is a no-op after the
-- migration row has been recorded. Completed/cancelled requests are untouched.

START TRANSACTION;

SET @migration_name = '2026_08_30_000000_reorder_active_sales_approval_stages';
SET @should_apply = (
    SELECT CASE WHEN EXISTS (
        SELECT 1
        FROM migrations
        WHERE migration = @migration_name
    ) THEN 0 ELSE 1 END
);

SET @warehouse_approver_id = (
    SELECT u.id
    FROM users AS u
    INNER JOIN model_has_roles AS mr
        ON mr.model_id = u.id
       AND mr.model_type = CONCAT('App', CHAR(92), 'Models', CHAR(92), 'User')
    INNER JOIN roles AS r ON r.id = mr.role_id
    WHERE r.name = 'warehouse_keeper'
      AND u.is_active = 1
    ORDER BY u.id
    LIMIT 1
);

SET @financial_approver_id = (
    SELECT u.id
    FROM users AS u
    INNER JOIN model_has_roles AS mr
        ON mr.model_id = u.id
       AND mr.model_type = CONCAT('App', CHAR(92), 'Models', CHAR(92), 'User')
    INNER JOIN roles AS r ON r.id = mr.role_id
    WHERE r.name = 'financial_manager'
      AND u.is_active = 1
    ORDER BY u.id
    LIMIT 1
);

SET @purchasing_approver_id = (
    SELECT u.id
    FROM users AS u
    INNER JOIN model_has_roles AS mr
        ON mr.model_id = u.id
       AND mr.model_type = CONCAT('App', CHAR(92), 'Models', CHAR(92), 'User')
    INNER JOIN roles AS r ON r.id = mr.role_id
    WHERE r.name = 'purchasing_manager'
      AND u.is_active = 1
    ORDER BY u.id
    LIMIT 1
);

SET @sales_approver_id = (
    SELECT u.id
    FROM users AS u
    INNER JOIN model_has_roles AS mr
        ON mr.model_id = u.id
       AND mr.model_type = CONCAT('App', CHAR(92), 'Models', CHAR(92), 'User')
    INNER JOIN roles AS r ON r.id = mr.role_id
    WHERE r.name = 'sales_manager'
      AND u.is_active = 1
    ORDER BY u.id
    LIMIT 1
);

-- Ensure unfinished requests have their warehouse stage.
INSERT INTO approval_stages (
    sales_approval_request_id,
    stage_number,
    role,
    approver_id,
    action,
    created_at,
    updated_at
)
SELECT
    sar.id,
    1,
    'warehouse_keeper',
    @warehouse_approver_id,
    'pending',
    NOW(),
    NOW()
FROM sales_approval_requests AS sar
WHERE @should_apply = 1
  AND sar.status IN ('pending', 'in_progress')
  AND NOT EXISTS (
      SELECT 1
      FROM approval_stages AS existing_stage
      WHERE existing_stage.sales_approval_request_id = sar.id
        AND existing_stage.stage_number = 1
  );

-- Preserve stage 1 and rebuild only the unfinished downstream chain.
DELETE approval_stage
FROM approval_stages AS approval_stage
INNER JOIN sales_approval_requests AS sar
    ON sar.id = approval_stage.sales_approval_request_id
WHERE @should_apply = 1
  AND sar.status IN ('pending', 'in_progress')
  AND approval_stage.stage_number >= 2;

INSERT INTO approval_stages (
    sales_approval_request_id,
    stage_number,
    role,
    approver_id,
    action,
    created_at,
    updated_at
)
SELECT sar.id, 2, 'financial_manager', @financial_approver_id, 'pending', NOW(), NOW()
FROM sales_approval_requests AS sar
WHERE @should_apply = 1
  AND sar.status IN ('pending', 'in_progress');

INSERT INTO approval_stages (
    sales_approval_request_id,
    stage_number,
    role,
    approver_id,
    action,
    created_at,
    updated_at
)
SELECT sar.id, 3, 'purchasing_manager', @purchasing_approver_id, 'pending', NOW(), NOW()
FROM sales_approval_requests AS sar
WHERE @should_apply = 1
  AND sar.status IN ('pending', 'in_progress');

INSERT INTO approval_stages (
    sales_approval_request_id,
    stage_number,
    role,
    approver_id,
    action,
    created_at,
    updated_at
)
SELECT sar.id, 4, 'sales_manager', @sales_approver_id, 'pending', NOW(), NOW()
FROM sales_approval_requests AS sar
WHERE @should_apply = 1
  AND sar.status IN ('pending', 'in_progress');

-- Requests that already passed warehouse resume at the new financial stage.
UPDATE sales_approval_requests
SET current_stage = 2,
    updated_at = NOW()
WHERE @should_apply = 1
  AND status IN ('pending', 'in_progress')
  AND current_stage > 1;

SET @migration_batch = (
    SELECT COALESCE(MAX(batch), 0) + 1
    FROM migrations
);

INSERT INTO migrations (migration, batch)
SELECT @migration_name, @migration_batch
WHERE @should_apply = 1;

COMMIT;
