ALTER TABLE products
    ADD COLUMN last_purchase_cost
        DECIMAL(14,4) NULL
        AFTER purchase_price,

    ADD COLUMN last_purchase_at
        DATETIME NULL
        AFTER last_purchase_cost;


ALTER TABLE stock_levels
    ADD COLUMN average_unit_cost
        DECIMAL(14,4)
        NOT NULL DEFAULT 0.0000
        AFTER quantity,

    ADD COLUMN inventory_value
        DECIMAL(18,4)
        NOT NULL DEFAULT 0.0000
        AFTER average_unit_cost;


ALTER TABLE warehouse_transactions
    ADD COLUMN unit_cost
        DECIMAL(14,4) NULL
        AFTER quantity,

    ADD COLUMN total_cost
        DECIMAL(18,4) NULL
        AFTER unit_cost,

    ADD COLUMN from_quantity_before
        DECIMAL(14,3) NULL
        AFTER total_cost,

    ADD COLUMN from_quantity_after
        DECIMAL(14,3) NULL
        AFTER from_quantity_before,

    ADD COLUMN to_quantity_before
        DECIMAL(14,3) NULL
        AFTER from_quantity_after,

    ADD COLUMN to_quantity_after
        DECIMAL(14,3) NULL
        AFTER to_quantity_before,

    ADD COLUMN from_average_cost_before
        DECIMAL(14,4) NULL
        AFTER to_quantity_after,

    ADD COLUMN from_average_cost_after
        DECIMAL(14,4) NULL
        AFTER from_average_cost_before,

    ADD COLUMN to_average_cost_before
        DECIMAL(14,4) NULL
        AFTER from_average_cost_after,

    ADD COLUMN to_average_cost_after
        DECIMAL(14,4) NULL
        AFTER to_average_cost_before,

    ADD COLUMN from_inventory_value_before
        DECIMAL(18,4) NULL
        AFTER to_average_cost_after,

    ADD COLUMN from_inventory_value_after
        DECIMAL(18,4) NULL
        AFTER from_inventory_value_before,

    ADD COLUMN to_inventory_value_before
        DECIMAL(18,4) NULL
        AFTER from_inventory_value_after,

    ADD COLUMN to_inventory_value_after
        DECIMAL(18,4) NULL
        AFTER to_inventory_value_before;


ALTER TABLE purchase_items
    ADD COLUMN inventory_unit_cost
        DECIMAL(14,4) NULL
        AFTER unit_cost,

    ADD COLUMN inventory_total_cost
        DECIMAL(18,4) NULL
        AFTER inventory_unit_cost;


ALTER TABLE sale_items
    ADD COLUMN unit_cost
        DECIMAL(14,4) NULL
        AFTER unit_price,

    ADD COLUMN total_cost
        DECIMAL(18,4) NULL
        AFTER unit_cost,

    ADD COLUMN gross_profit
        DECIMAL(18,2) NULL
        AFTER total_cost,

    ADD COLUMN gross_margin_percent
        DECIMAL(7,2) NULL
        AFTER gross_profit;


ALTER TABLE sales_return_items
    ADD COLUMN unit_cost
        DECIMAL(14,4) NULL
        AFTER restock_quantity,

    ADD COLUMN restocked_cost
        DECIMAL(18,4) NULL
        AFTER unit_cost;


ALTER TABLE purchase_return_items
    ADD COLUMN total_cost
        DECIMAL(18,4) NULL
        AFTER unit_cost;


ALTER TABLE inventory_adjustment_items
    ADD COLUMN unit_cost
        DECIMAL(14,4) NULL
        AFTER quantity,

    ADD COLUMN total_cost
        DECIMAL(18,4) NULL
        AFTER unit_cost;


ALTER TABLE inventory_count_items
    ADD COLUMN unit_cost
        DECIMAL(14,4) NULL
        AFTER difference_quantity,

    ADD COLUMN variance_value
        DECIMAL(18,4) NULL
        AFTER unit_cost;


UPDATE products
SET
    last_purchase_cost = purchase_price,
    last_purchase_at = NOW()
WHERE purchase_price > 0
AND last_purchase_cost IS NULL;


UPDATE stock_levels
INNER JOIN products
    ON products.id =
        stock_levels.product_id

    AND products.company_id =
        stock_levels.company_id

SET
    stock_levels.average_unit_cost =
        COALESCE(
            NULLIF(
                products.last_purchase_cost,
                0
            ),
            products.purchase_price,
            0
        ),

    stock_levels.inventory_value =
        ROUND(
            stock_levels.quantity *
            COALESCE(
                NULLIF(
                    products.last_purchase_cost,
                    0
                ),
                products.purchase_price,
                0
            ),
            4
        );


UPDATE purchase_items
SET
    inventory_unit_cost =
        CASE
            WHEN quantity > 0
            THEN ROUND(
                net_amount / quantity,
                4
            )

            ELSE 0
        END,

    inventory_total_cost =
        ROUND(
            net_amount,
            4
        )

WHERE inventory_unit_cost IS NULL;