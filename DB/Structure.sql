-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS motorcycle_rental;
USE motorcycle_rental;

-- 1. ตารางเจ้าของร้าน
CREATE TABLE owners (
    owner_id VARCHAR(50) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,  -- แก้เป็น 255
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,    -- แก้เป็น 255
    last_name VARCHAR(255) NOT NULL,     -- แก้เป็น 255
    phone VARCHAR(255) NOT NULL,         -- แก้เป็น 255
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. ตารางพนักงาน
CREATE TABLE employees (
    employee_id VARCHAR(50) PRIMARY KEY,
    owner_id VARCHAR(50) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,  -- แก้เป็น 255
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,    -- แก้เป็น 255
    last_name VARCHAR(255) NOT NULL,     -- แก้เป็น 255
    phone VARCHAR(255) NOT NULL,         -- แก้เป็น 255
    position VARCHAR(255) DEFAULT 'staff', -- แก้เป็น 255
    is_active TINYINT(1) DEFAULT TRUE,   -- แก้เป็น TINYINT(1)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES owners(owner_id) ON DELETE CASCADE
);

-- 3. ตารางลูกค้า
CREATE TABLE customers (
    customer_id VARCHAR(50) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,  -- แก้เป็น 255
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,    -- แก้เป็น 255
    last_name VARCHAR(255) NOT NULL,     -- แก้เป็น 255
    phone VARCHAR(255) NOT NULL,         -- แก้เป็น 255
    address VARCHAR(255),                -- แก้เป็น VARCHAR(255)
    license_number VARCHAR(255),         -- แก้เป็น VARCHAR(255)
    date_of_birth DATE,
    id_card_number VARCHAR(255),         -- แก้เป็น VARCHAR(255)
    is_verified TINYINT(1) DEFAULT FALSE, -- แก้เป็น TINYINT(1)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. ตารางรถจักรยานยนต์
CREATE TABLE motorcycles (
    motorcycle_id VARCHAR(50) PRIMARY KEY,
    brand VARCHAR(255) NOT NULL,         -- แก้เป็น 255
    model VARCHAR(255) NOT NULL,         -- แก้เป็น 255
    year INT,
    license_plate VARCHAR(255) UNIQUE NOT NULL, -- แก้เป็น 255
    color VARCHAR(255),                  -- แก้เป็น 255
    engine_cc INT,
    price_per_day DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    is_available TINYINT(1) DEFAULT TRUE, -- แก้เป็น TINYINT(1)
    description TEXT,
    maintenance_status VARCHAR(20) DEFAULT 'ready',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. ตารางการจอง
CREATE TABLE reservations (
    reservation_id VARCHAR(50) PRIMARY KEY,
    customer_id VARCHAR(50) NOT NULL,
    employee_id VARCHAR(50),
    motorcycle_id VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    deposit_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    final_price DECIMAL(10,2) NOT NULL,
    pickup_location VARCHAR(255),
    return_location VARCHAR(255),
    special_requests TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (motorcycle_id) REFERENCES motorcycles(motorcycle_id) ON DELETE CASCADE
);

-- 6. ตารางการชำระเงิน
CREATE TABLE payments (
    payment_id VARCHAR(255) PRIMARY KEY, -- แก้เป็น VARCHAR(255)
    reservation_id VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    payment_status VARCHAR(20) DEFAULT 'pending',
    payment_date TIMESTAMP NULL,
    transaction_id VARCHAR(255),         -- แก้เป็น VARCHAR(255)
    slip_image_url VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE
);

-- 7. ตารางส่วนลด
CREATE TABLE discounts (
    discount_id VARCHAR(50) PRIMARY KEY,
    discount_code VARCHAR(255) UNIQUE NOT NULL, -- แก้เป็น 255
    discount_type VARCHAR(20) NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_rental_days INT DEFAULT 1,
    max_discount_amount DECIMAL(10,2),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT TRUE,   -- แก้เป็น TINYINT(1)
    created_by VARCHAR(50) NOT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES owners(owner_id)
);

-- ตารางสัมพันธ์ระหว่างส่วนลดและการจอง
CREATE TABLE reservation_discounts (
    reservation_id VARCHAR(50),
    discount_id VARCHAR(50),
    applied_amount DECIMAL(10,2) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reservation_id, discount_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (discount_id) REFERENCES discounts(discount_id) ON DELETE CASCADE
);