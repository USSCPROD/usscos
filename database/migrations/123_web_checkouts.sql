-- One row per online checkout: the website cart, priced, handed to Square to pay.
--
-- The cart is priced on the server and kept here, so what becomes the sales order is what
-- was priced, not what the browser later claims. Square keeps the card; nothing about the
-- card is stored here.
--
-- status   open     a Square payment link was made; the customer may or may not pay
--          ordered  Square says it is paid, and sales_order_id is the order it became
--          failed   paid, but the order could not be made (error says why): staff must look
--
-- square_order_id is UNIQUE so the return page, the webhook and the reconcile script can
-- all try to turn the same payment into an order and only one of them ever can.

CREATE TABLE IF NOT EXISTS web_checkouts (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token                  CHAR(32)       NOT NULL,
    status                 ENUM('open','ordered','failed') NOT NULL DEFAULT 'open',
    items                  JSON           NOT NULL,       -- the cart as sent, for re-pricing
    lines                  JSON           NOT NULL,       -- the priced lines
    subtotal               DECIMAL(12,2)  NOT NULL,
    shipping_service       VARCHAR(50)    NOT NULL,
    shipping_name          VARCHAR(120)   NOT NULL,
    shipping_amount        DECIMAL(12,2)  NOT NULL,
    total                  DECIMAL(12,2)  NOT NULL,
    ship_zip               VARCHAR(10)    NOT NULL,
    residential            TINYINT(1)     NOT NULL DEFAULT 0,
    square_environment     VARCHAR(12)    NOT NULL,
    square_payment_link_id VARCHAR(64)    NULL,
    square_order_id        VARCHAR(64)    NULL,
    square_payment_id      VARCHAR(64)    NULL,
    sales_order_id         INT UNSIGNED   NULL,
    error                  TEXT           NULL,
    created_at             TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ordered_at             TIMESTAMP      NULL,
    updated_at             TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_web_checkouts_token (token),
    UNIQUE KEY uq_web_checkouts_square_order (square_order_id),
    INDEX idx_web_checkouts_status (status, created_at),
    CONSTRAINT fk_web_checkouts_so FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
