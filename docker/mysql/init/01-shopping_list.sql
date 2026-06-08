CREATE TABLE IF NOT EXISTS shopping_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    is_bought TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO shopping_items (name, quantity, is_bought) VALUES
    ('Молоко', 2, 0),
    ('Хлеб', 1, 0),
    ('Яйца', 10, 0),
    ('Сыр', 1, 0),
    ('Масло', 1, 0);
