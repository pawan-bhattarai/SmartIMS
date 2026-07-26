/* part 1

 SMARTIMS DATABASE
*/

CREATE DATABASE IF NOT EXISTS smartims;
USE smartims;

/*
 USERS TABLE
*/

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin','Staff') DEFAULT 'Staff',
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users
(full_name,username,email,password,role)
VALUES
('Pawan Bhattarai','pawan','pawan@example.com','admin123','Admin');

/* part 2

CATEGORIES TABLE
*/

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO categories(category_name,description)
VALUES
('Grocery','Daily grocery items'),
('Beverages','Soft drinks and juices'),
('Personal Care','Shampoo, soap and cosmetics'),
('Dairy','Milk and dairy products'),
('Bakery','Bread and bakery products'),
('Household','Cleaning and household items');

/* part 3
 SUPPLIERS TABLE
*/

CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO suppliers
(supplier_name, contact_person, phone, email, address)
VALUES
('CG Foods', 'Ram Sharma', '9811111111', 'cgfoods@gmail.com', 'Kathmandu'),
('Unilever Nepal', 'Hari Thapa', '9822222222', 'unilever@gmail.com', 'Lalitpur'),
('Bottlers Nepal', 'Sita Rai', '9833333333', 'bottlers@gmail.com', 'Bhaktapur');

/* 
PART 4 
 PRODUCTS TABLE
*/

CREATE TABLE products (

    product_id INT AUTO_INCREMENT PRIMARY KEY,

    category_id INT NOT NULL,
    supplier_id INT NOT NULL,

    product_name VARCHAR(150) NOT NULL,

    brand VARCHAR(100),

    sku VARCHAR(50) UNIQUE,

    barcode VARCHAR(50) UNIQUE,

    purchase_price DECIMAL(10,2) NOT NULL,

    selling_price DECIMAL(10,2) NOT NULL,

    expiry_date DATE,

    stock_quantity INT DEFAULT 0,

    minimum_stock INT DEFAULT 10,

    unit ENUM('pcs','kg','litre','packet','box','bottle')
    DEFAULT 'pcs',

    product_image VARCHAR(255),

    description TEXT,

    status ENUM('Active','Inactive') DEFAULT 'Active',

    created_by INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id)
        REFERENCES categories(category_id),

    FOREIGN KEY (supplier_id)
        REFERENCES suppliers(supplier_id),

    FOREIGN KEY (created_by)
        REFERENCES users(user_id)

);

/* part 5

 PURCHASES TABLE
  */

CREATE TABLE purchases (

    purchase_id INT AUTO_INCREMENT PRIMARY KEY,

    supplier_id INT NOT NULL,

    invoice_number VARCHAR(50) UNIQUE,

    purchase_date DATE,

    total_amount DECIMAL(10,2),

    payment_status ENUM('Paid','Pending')
    DEFAULT 'Paid',

    remarks TEXT,

    purchased_by INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (supplier_id)
        REFERENCES suppliers(supplier_id),

    FOREIGN KEY (purchased_by)
        REFERENCES users(user_id)

);

/* part 6
 PURCHASE DETAILS TABLE*/

CREATE TABLE purchase_details (

    purchase_detail_id INT AUTO_INCREMENT PRIMARY KEY,

    purchase_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT NOT NULL,

    purchase_price DECIMAL(10,2),

    subtotal DECIMAL(10,2),

    FOREIGN KEY(purchase_id)
    REFERENCES purchases(purchase_id)
    ON DELETE CASCADE,

    FOREIGN KEY(product_id)
    REFERENCES products(product_id)

);


/* 
   PART 7 
   SALES TABLE*/

CREATE TABLE sales (

    sale_id INT AUTO_INCREMENT PRIMARY KEY,

    invoice_number VARCHAR(50) UNIQUE,

    customer_name VARCHAR(100),

    sale_date DATE,

    payment_method ENUM('Cash','Card','eSewa','Khalti','Bank Transfer'),

    discount DECIMAL(10,2) DEFAULT 0,

    vat DECIMAL(10,2) DEFAULT 0,

    grand_total DECIMAL(10,2),

    created_by INT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by)
        REFERENCES users(user_id)

);

/* part 8
 SALE DETAILS TABLE*/

CREATE TABLE sale_details (

    sale_detail_id INT AUTO_INCREMENT PRIMARY KEY,

    sale_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT,

    selling_price DECIMAL(10,2),

    subtotal DECIMAL(10,2),

    FOREIGN KEY(sale_id)
    REFERENCES sales(sale_id)
    ON DELETE CASCADE,

    FOREIGN KEY(product_id)
    REFERENCES products(product_id)

);

/*PART 9 
   INDEXES */

CREATE INDEX idx_product_name
ON products(product_name);

CREATE INDEX idx_category
ON products(category_id);

CREATE INDEX idx_supplier
ON products(supplier_id);
    