ALTER TABLE warehouse_transactions
    ADD COLUMN cost_method
        VARCHAR(30) NULL
        AFTER quantity;


UPDATE warehouse_transactions
SET cost_method = 'weighted_average'
WHERE cost_method IS NULL
AND unit_cost IS NOT NULL;