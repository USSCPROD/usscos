ALTER TABLE products
    MODIFY COLUMN item_type ENUM(
        'inventory_part',
        'inventory_assembly',
        'non_inventory_part',
        'service',
        'other_charge',
        'group',
        'discount',
        'payment',
        'sales_tax_item',
        'raw_material'
    ) NOT NULL DEFAULT 'inventory_part';
