CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL,
    status TINYINT UNSIGNED NOT NULL DEFAULT 1,
    reason VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    status TINYINT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image LONGTEXT,
    category_id INT,
    status TINYINT UNSIGNED NOT NULL DEFAULT 1,
    expiration_date DATE,
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    client VARCHAR(150) NOT NULL,
    sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status TINYINT UNSIGNED NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Insertar datos de prueba
INSERT INTO users (name, email, phone, password, role, status) VALUES
('Administrador', 'admin@test.com', '1234567890', '$2y$10$L1.KGidJUaVfpp5sBVDV6OPt4rtC/Bv0VxmOZ5NQju4adGxRGkpv.', 'admin', 1),
('Juan Pérez', 'juan@test.com', '1234567891', '$2y$10$L1.KGidJUaVfpp5sBVDV6OPt4rtC/Bv0VxmOZ5NQju4adGxRGkpv.', 'vendedor', 1),
('María García', 'maria@test.com', '1234567892', '$2y$10$L1.KGidJUaVfpp5sBVDV6OPt4rtC/Bv0VxmOZ5NQju4adGxRGkpv.', 'bodega', 1),
('Carlos López', 'carlos@test.com', '1234567893', '$2y$10$L1.KGidJUaVfpp5sBVDV6OPt4rtC/Bv0VxmOZ5NQju4adGxRGkpv.', 'vendedor', 1);