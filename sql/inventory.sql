CREATE DATABASE IF NOT EXISTS inventory_system;
USE inventory_system;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (email, password)
VALUES ('admin@inventory.local', 
'$2y$10$sp2HgT8qC/1JtWQJ5hRHyOJ3x1EuFe6EdJ5WcP9D3wC7sxUdAg1sO'); 
-- password = Admin@123
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_name VARCHAR(100) NOT NULL,
  category VARCHAR(100) NOT NULL,
  serial_number VARCHAR(100),
  quantity INT DEFAULT 0,
  price DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE outgoing (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  product_name VARCHAR(100),
  serial_number VARCHAR(100),
  doctor_name VARCHAR(100),
  price DECIMAL(10,2),
  terms_of_payment VARCHAR(100),
  os_number VARCHAR(100),
  quantity INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
