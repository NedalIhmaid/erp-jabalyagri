-- Al-Jabali ERP
-- Remove approval stage 4 (sales manager).
-- The sales manager keeps view-only access in the application.
-- Stage 3 (purchasing manager) becomes the final approval.
--
-- Safe to import through phpMyAdmin after uploading the matching PHP files.
-- Re-running this file is a no-op once its migration row is recorded.

START TRANSACTION;

SET @migration_name = '2026_09_03_000000_remove_sales_manager_approval_stage';
SET @should_apply = (
    SELECT CASE WHEN EXISTS (
        SELECT 1
        FROM migrations
        WHERE migration = @migration_name
    ) THEN 0 ELSE 1 END
);

-- These requests already passed the three remaining approval stages.
-- Finalize them instead of leaving them waiting for the removed stage.
UPDATE sales_approval_requests
SET status = 'approved',
    current_stage = 3,
    updated_at = NOW()
WHERE @should_apply = 1
  AND status = 'in_progress'
  AND current_stage = 4;

-- Normalize historical requests so reports never show a stage above 3.
UPDATE sales_approval_requests
SET current_stage = 3
WHERE @should_apply = 1
  AND current_stage > 3;

-- Remove every sales-manager approval-stage record.
DELETE FROM approval_stages
WHERE @should_apply = 1
  AND stage_number = 4;

-- Mark the matching Laravel migration as complete.
SET @migration_batch = (
    SELECT COALESCE(MAX(batch), 0) + 1
    FROM migrations
);

INSERT INTO migrations (migration, batch)
SELECT @migration_name, @migration_batch
WHERE @should_apply = 1;

COMMIT;

-- Verification: both results must be 0.
SELECT COUNT(*) AS remaining_stage_four_records
FROM approval_stages
WHERE stage_number = 4;

SELECT COUNT(*) AS requests_above_stage_three
FROM sales_approval_requests
WHERE current_stage > 3;
