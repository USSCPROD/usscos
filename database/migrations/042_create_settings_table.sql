-- General key/value settings table
CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT         NULL,
    label         VARCHAR(150) NOT NULL DEFAULT '',
    description   VARCHAR(255) NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lead routing defaults
INSERT INTO settings (setting_key, setting_value, label, description) VALUES
    ('lead_routing_stencil_email',    'chip@usscproducts.com',  'Stencil Inquiries — Email',   'Email address that receives stencil quote request notifications'),
    ('lead_routing_stencil_user_id',  NULL,                     'Stencil Inquiries — Assign To','User ID to auto-assign stencil leads to'),
    ('lead_routing_paint_email',      'sales@usscproducts.com', 'Paint Inquiries — Email',      'Email address that receives paint quote request notifications'),
    ('lead_routing_paint_user_id',    NULL,                     'Paint Inquiries — Assign To',  'User ID to auto-assign paint leads to'),
    ('lead_routing_general_email',    'sales@usscproducts.com', 'General Inquiries — Email',    'Email address that receives general contact form notifications'),
    ('lead_routing_general_user_id',  NULL,                     'General Inquiries — Assign To','User ID to auto-assign general contact leads to'),
    ('lead_routing_paint_pool',       NULL,                     'Paint — Round-Robin Pool',     'Comma-separated user IDs for paint lead round-robin assignment'),
    ('lead_routing_paint_next_index', '0',                      'Paint — Next Index',           'Current position in the round-robin pool')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
