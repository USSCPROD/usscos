-- Agreed prices: what a specific customer pays for a specific product.
--
-- WHY. The tier ladder in App\Services\CustomerPricing answers "what does a distributor
-- pay", which is the right question until somebody negotiates. Today a negotiated price
-- lives in a rep's head and is retyped onto each quote - so it drifts, nobody can see what
-- was agreed, and when the rep is away the next person quotes list price to an account
-- that has had a deal for three years.
--
-- min_qty is what makes this one table instead of two. A single row with min_qty = 1 is a
-- flat override. Several rows - 1, 25, 100 - are that customer's volume breaks. The
-- roadmap asked for both; they are the same thing with a quantity on it, and splitting
-- them would mean two places to look when a price comes out wrong.
--
-- The price is stored, not a discount percentage. A percentage recalculates itself every
-- time the list price moves, which means an agreed figure silently changes without anybody
-- agreeing to it. Storing the number makes the list price irrelevant to what was agreed,
-- which is the point of agreeing it.

CREATE TABLE IF NOT EXISTS customer_product_prices (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id  INT UNSIGNED   NOT NULL,
    product_id   INT UNSIGNED   NOT NULL,

    -- The quantity this price starts at. 1 means "any quantity".
    min_qty      DECIMAL(12,2)  NOT NULL DEFAULT 1.00,
    unit_price   DECIMAL(12,4)  NOT NULL,

    -- Why, and who agreed it. A price with no story behind it is the thing nobody dares
    -- change later.
    note         VARCHAR(255)   NULL,
    created_by   INT UNSIGNED   NULL,
    created_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- One price per customer, product and break. Re-agreeing a figure updates the row
    -- rather than adding a second one that quietly wins or loses depending on row order.
    UNIQUE KEY uq_cpp (customer_id, product_id, min_qty),

    -- The lookup is (customer, product) ordered by break, on every quote line.
    INDEX idx_cpp_lookup (customer_id, product_id, min_qty),
    INDEX idx_cpp_product (product_id),

    -- CASCADE here, unlike the activity log. An agreed price is not history worth keeping
    -- once the customer or the product is gone - it is a live term, and a dangling one
    -- would price a future row against a product that no longer exists.
    CONSTRAINT fk_cpp_customer FOREIGN KEY (customer_id)
        REFERENCES customers (id) ON DELETE CASCADE,
    CONSTRAINT fk_cpp_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT fk_cpp_user FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
