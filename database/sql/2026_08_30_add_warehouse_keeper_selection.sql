-- Al-Jabali ERP
-- Allow the engineer to select the warehouse keeper for a sales request.
-- Import once through phpMyAdmin after uploading the matching PHP files.

START TRANSACTION;

ALTER TABLE sales_approval_requests
    ADD COLUMN warehouse_keeper_id BIGINT UNSIGNED NULL AFTER user_id,
    ADD INDEX sales_approval_requests_warehouse_keeper_id_index (warehouse_keeper_id),
    ADD CONSTRAINT sales_approval_requests_warehouse_keeper_id_foreign
        FOREIGN KEY (warehouse_keeper_id) REFERENCES users (id)
        ON DELETE SET NULL;

SET @fallback_warehouse_keeper_id = (
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

UPDATE sales_approval_requests AS sar
LEFT JOIN approval_stages AS stage_one
    ON stage_one.sales_approval_request_id = sar.id
   AND stage_one.stage_number = 1
SET sar.warehouse_keeper_id = COALESCE(stage_one.approver_id, @fallback_warehouse_keeper_id)
WHERE sar.warehouse_keeper_id IS NULL;

SET @migration_batch = (
    SELECT COALESCE(MAX(batch), 0) + 1
    FROM migrations
);

INSERT INTO migrations (migration, batch)
VALUES ('2026_08_30_010000_add_warehouse_keeper_to_sales_requests', @migration_batch);

COMMIT;
