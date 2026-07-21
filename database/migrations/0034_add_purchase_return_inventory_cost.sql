ALTER TABLE purchase_return_items
    ADD COLUMN inventory_unit_cost
        DECIMAL(14,4) NULL
        AFTER unit_cost;