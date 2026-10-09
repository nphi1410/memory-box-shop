USE memory_box;

ALTER TABLE orders
    ADD COLUMN shipping_name VARCHAR(120) NULL AFTER custom_note,
    ADD COLUMN shipping_phone VARCHAR(30) NULL AFTER shipping_name,
    ADD COLUMN shipping_address TEXT NULL AFTER shipping_phone,
    ADD COLUMN payment_method ENUM('cod','bank_transfer') NULL AFTER shipping_address;
