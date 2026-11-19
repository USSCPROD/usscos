-- Customer accounts on the website: the first part of USSCOS open to the public.
--
-- Deliberately not the staff `users` table. A customer login is a stranger on the internet
-- with a password; keeping them in their own table means no role mix-up, no staff session
-- and no staff screen can ever be reached with one. They sign in with their own cookie.
--
-- A login is a PERSON. Which company they act for is customer_id, set by staff (never by
-- matching an email: thousands of addresses are shared between customer records). Until
-- it is set the login buys at retail and sees only what it ordered itself. A login linked
-- to a parent customer (Sports Inc) covers that parent's sub-stores; one linked to a
-- sub-store covers only that store.
--
-- legacy_* holds a password hash carried over from the old stencil shop (PBKDF2-SHA256).
-- The first successful sign-in re-hashes it with password_hash() and clears these.

CREATE TABLE IF NOT EXISTS customer_logins (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email              VARCHAR(200)   NOT NULL,
    password_hash      VARCHAR(255)   NULL,
    legacy_salt        VARCHAR(64)    NULL,
    legacy_hash        VARCHAR(128)   NULL,
    legacy_iterations  INT UNSIGNED   NULL,
    name               VARCHAR(100)   NOT NULL,
    phone              VARCHAR(40)    NOT NULL DEFAULT '',
    organization       VARCHAR(150)   NOT NULL DEFAULT '',
    buyer_type         VARCHAR(30)    NOT NULL DEFAULT '',
    customer_id        INT UNSIGNED   NULL,           -- the company this person acts for; staff set it
    linked_by          INT UNSIGNED   NULL,           -- users.id
    linked_at          TIMESTAMP      NULL,
    asked_trade        TINYINT(1)     NOT NULL DEFAULT 0,  -- said they are a distributor/reseller at sign-up
    status             ENUM('active','blocked') NOT NULL DEFAULT 'active',
    blocked_note       VARCHAR(255)   NULL,
    source             ENUM('website','stencil_shop') NOT NULL DEFAULT 'website',
    shop_role          VARCHAR(20)    NULL,           -- role on the old shop, for staff to act on
    signup_ip          VARCHAR(45)    NULL,
    last_login_at      TIMESTAMP      NULL,
    created_at         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_customer_logins_email (email),
    INDEX idx_customer_logins_customer (customer_id),
    INDEX idx_customer_logins_unlinked (customer_id, created_at),
    CONSTRAINT fk_customer_logins_customer FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only a hash of the cookie's token is stored, so a copy of this table signs nobody in.
CREATE TABLE IF NOT EXISTS customer_login_sessions (
    token_hash   CHAR(64)      NOT NULL PRIMARY KEY,
    login_id     INT UNSIGNED  NOT NULL,
    expires_at   DATETIME      NOT NULL,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_login_sessions_login (login_id),
    CONSTRAINT fk_customer_login_sessions_login FOREIGN KEY (login_id) REFERENCES customer_logins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_password_resets (
    token_hash   CHAR(64)      NOT NULL PRIMARY KEY,
    login_id     INT UNSIGNED  NOT NULL,
    expires_at   DATETIME      NOT NULL,
    CONSTRAINT fk_customer_password_resets_login FOREIGN KEY (login_id) REFERENCES customer_logins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed sign-ins and sign-ups, for throttling: by IP and by email.
CREATE TABLE IF NOT EXISTS customer_login_attempts (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kind         ENUM('fail','signup','reset') NOT NULL,
    ip           VARCHAR(45)   NOT NULL,
    email        VARCHAR(200)  NOT NULL DEFAULT '',
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_customer_login_attempts_ip (kind, ip, created_at),
    INDEX idx_customer_login_attempts_email (kind, email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A design saved from a designer, reopened later on any device.
-- data is the designer's own inputs (text, size, style ... or logo, field, width).
CREATE TABLE IF NOT EXISTS saved_designs (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login_id     INT UNSIGNED  NOT NULL,
    kind         ENUM('text','endzone','midfield') NOT NULL,
    name         VARCHAR(120)  NOT NULL,
    data         JSON          NOT NULL,
    summary      VARCHAR(255)  NOT NULL DEFAULT '',
    price        DECIMAL(12,2) NULL,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_saved_designs_login (login_id, updated_at),
    CONSTRAINT fk_saved_designs_login FOREIGN KEY (login_id) REFERENCES customer_logins (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A saved cart: the items as the cart holds them, re-priced whenever it is opened, so a
-- saved quote never sells at an old price. Becomes 'ordered' when placed on terms.
CREATE TABLE IF NOT EXISTS saved_quotes (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login_id        INT UNSIGNED  NOT NULL,
    name            VARCHAR(120)  NOT NULL,
    items           JSON          NOT NULL,
    total_when_saved DECIMAL(12,2) NOT NULL DEFAULT 0,
    status          ENUM('saved','ordered') NOT NULL DEFAULT 'saved',
    sales_order_id  INT UNSIGNED  NULL,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_saved_quotes_login (login_id, updated_at),
    CONSTRAINT fk_saved_quotes_login FOREIGN KEY (login_id) REFERENCES customer_logins (id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_quotes_so FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Who placed a website order, and for which customer. A card checkout by a signed-in
-- person is made for their linked customer instead of being matched by email.
-- paid_by 'terms': a distributor's order on account. No Square at all; the row is written
-- already 'ordered', so every website order, card or terms, is in this one table.
ALTER TABLE web_checkouts
    ADD COLUMN paid_by     ENUM('card','terms') NOT NULL DEFAULT 'card' AFTER status,
    ADD COLUMN login_id    INT UNSIGNED NULL AFTER residential,
    ADD COLUMN customer_id INT UNSIGNED NULL AFTER login_id,
    ADD COLUMN price_tier  VARCHAR(30)  NOT NULL DEFAULT 'Retail' AFTER customer_id,
    ADD INDEX idx_web_checkouts_login (login_id),
    ADD CONSTRAINT fk_web_checkouts_login FOREIGN KEY (login_id) REFERENCES customer_logins (id) ON DELETE SET NULL;
