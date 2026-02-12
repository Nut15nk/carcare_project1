-- =============================================
-- MOTORCYCLE RENTAL SYSTEM - DATABASE SETUP
-- =============================================
-- Version: 2.0
-- Last Updated: 2024
-- Description: Complete database schema for motorcycle rental system
-- =============================================

-- ---------------------------------------------
-- 0. DROP DATABASE IF EXISTS (Clean install)
-- ---------------------------------------------
DROP DATABASE IF EXISTS motorcycle_rental_new1;
CREATE DATABASE motorcycle_rental_new1;
USE motorcycle_rental_new1;

-- =============================================
-- 1. CREATE TABLES
-- =============================================

-- ---------------------------------------------
-- 1.1 owners (เจ้าของร้าน)
-- ---------------------------------------------
CREATE TABLE owners (
    owner_id         VARCHAR(50) PRIMARY KEY,
    email            VARCHAR(255) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NOT NULL,
    first_name       VARCHAR(255) NOT NULL,
    last_name        VARCHAR(255) NOT NULL,
    phone            VARCHAR(255) NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 1.2 employees (พนักงาน)
-- ---------------------------------------------
CREATE TABLE employees (
    employee_id      VARCHAR(50) PRIMARY KEY,
    owner_id         VARCHAR(50) NOT NULL,
    email            VARCHAR(255) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NOT NULL,
    first_name       VARCHAR(255) NOT NULL,
    last_name        VARCHAR(255) NOT NULL,
    phone            VARCHAR(255) NOT NULL,
    position         VARCHAR(255) DEFAULT 'staff',
    is_active        TINYINT(1) DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES owners(owner_id) ON DELETE CASCADE,
    INDEX idx_email (email),
    INDEX idx_owner (owner_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 1.3 customers (ลูกค้า)
-- ---------------------------------------------
CREATE TABLE customers (
    customer_id      VARCHAR(50) PRIMARY KEY,
    email            VARCHAR(255) NOT NULL UNIQUE,
    password_hash    VARCHAR(255) NOT NULL,
    first_name       VARCHAR(255) NOT NULL,
    last_name        VARCHAR(255) NOT NULL,
    phone            VARCHAR(255) NOT NULL,
    line_id          VARCHAR(255) DEFAULT NULL,
    is_active        TINYINT(1) DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 1.4 motorcycles (รถจักรยานยนต์)
-- ---------------------------------------------
CREATE TABLE motorcycles (
    motorcycle_id        VARCHAR(50) PRIMARY KEY,
    brand               VARCHAR(255) NOT NULL,
    model               VARCHAR(255) NOT NULL,
    year                INT,
    license_plate       VARCHAR(255) NOT NULL UNIQUE,
    color               VARCHAR(255),
    engine_cc           INT,
    price_per_day       DECIMAL(10,2) NOT NULL,
    image_url           VARCHAR(255),
    is_available        TINYINT(1) DEFAULT 1,
    description         TEXT,
    maintenance_status  VARCHAR(20) DEFAULT 'READY',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- ✅ CHECK constraint สำหรับ maintenance_status
    CONSTRAINT chk_motorcycle_status 
    CHECK (maintenance_status IN (
        'READY',        -- พร้อมใช้งาน
        'MAINTENANCE',  -- ซ่อมบำรุง
        'CLEANING',     -- ทำความสะอาด
        'DAMAGED',      -- เสียหายรุนแรง
        'LOST',         -- สูญหาย
        'UNAVAILABLE'   -- ไม่พร้อมใช้งาน
    )),
    
    INDEX idx_brand_model (brand, model),
    INDEX idx_available (is_available),
    INDEX idx_status (maintenance_status),
    INDEX idx_price (price_per_day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 1.5 reservations (การจอง)
-- ---------------------------------------------
CREATE TABLE reservations (
    reservation_id          VARCHAR(50) PRIMARY KEY,
    customer_id            VARCHAR(50) NOT NULL,
    employee_id            VARCHAR(50),
    motorcycle_id          VARCHAR(50) NOT NULL,
    start_datetime         DATETIME NOT NULL,
    end_datetime           DATETIME NOT NULL,
    total_days             INT NOT NULL,
    total_price            DECIMAL(10,2) NOT NULL,
    status                 VARCHAR(20) DEFAULT 'pending',
    deposit_amount         DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount        DECIMAL(10,2) DEFAULT 0.00,
    final_price            DECIMAL(10,2) NOT NULL,
    pickup_location        VARCHAR(255) NOT NULL,
    return_location        VARCHAR(255) NOT NULL,
    pickup_details         TEXT,
    return_details         TEXT,
    return_condition       TEXT,
    return_motorcycle_status VARCHAR(20),
    return_checked_at      DATETIME,
    return_checked_by      VARCHAR(50),
    created_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- ✅ CHECK constraint สำหรับสถานะการจอง
    CONSTRAINT chk_reservation_status 
    CHECK (status IN (
        'pending',     -- รอดำเนินการ
        'confirmed',   -- ยืนยันแล้ว
        'active',      -- กำลังเช่า
        'completed',   -- เสร็จสิ้น
        'cancelled'    -- ยกเลิก
    )),
    
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(employee_id) ON DELETE SET NULL,
    FOREIGN KEY (motorcycle_id) REFERENCES motorcycles(motorcycle_id) ON DELETE CASCADE,
    FOREIGN KEY (return_checked_by) REFERENCES employees(employee_id) ON DELETE SET NULL,
    
    INDEX idx_customer (customer_id),
    INDEX idx_motorcycle (motorcycle_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_datetime, end_datetime),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 1.6 payments (การชำระเงิน)
-- ---------------------------------------------
CREATE TABLE payments (
    payment_id       VARCHAR(255) PRIMARY KEY,
    reservation_id   VARCHAR(50) NOT NULL UNIQUE,
    amount           DECIMAL(10,2) NOT NULL,
    payment_method   VARCHAR(20) NOT NULL,
    payment_status   VARCHAR(20) DEFAULT 'pending',
    payment_date     TIMESTAMP NULL,
    transaction_id   VARCHAR(255),
    slip_image_url   VARCHAR(255),
    notes            TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- ✅ CHECK constraint สำหรับวิธีการชำระเงิน
    CONSTRAINT chk_payment_method 
    CHECK (payment_method IN ('cash', 'bank_transfer', 'qr_code', 'credit_card')),
    
    -- ✅ CHECK constraint สำหรับสถานะการชำระเงิน
    CONSTRAINT chk_payment_status 
    CHECK (payment_status IN ('pending', 'paid', 'verified', 'rejected', 'refunded')),
    
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    
    INDEX idx_reservation (reservation_id),
    INDEX idx_status (payment_status),
    INDEX idx_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 1.7 discounts (ส่วนลด)
-- ---------------------------------------------
CREATE TABLE discounts (
    discount_id          VARCHAR(50) PRIMARY KEY,
    discount_code        VARCHAR(255) NOT NULL UNIQUE,
    discount_type        VARCHAR(20) NOT NULL,
    discount_value       DECIMAL(10,2) NOT NULL,
    min_rental_days      INT DEFAULT 1,
    max_discount_amount  DECIMAL(10,2),
    start_date           DATE NOT NULL,
    end_date             DATE NOT NULL,
    is_active            TINYINT(1) DEFAULT 1,
    created_by           VARCHAR(50) NOT NULL,
    usage_limit          INT,
    used_count           INT DEFAULT 0,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- ✅ CHECK constraint สำหรับประเภทส่วนลด
    CONSTRAINT chk_discount_type 
    CHECK (discount_type IN ('fixed', 'percentage')),
    
    -- ✅ CHECK constraint สำหรับค่า discount
    CONSTRAINT chk_discount_value 
    CHECK (
        (discount_type = 'fixed' AND discount_value >= 0) OR
        (discount_type = 'percentage' AND discount_value BETWEEN 0 AND 100)
    ),
    
    -- ✅ CHECK constraint สำหรับวันที่
    CONSTRAINT chk_discount_dates 
    CHECK (end_date >= start_date),
    
    FOREIGN KEY (created_by) REFERENCES owners(owner_id) ON DELETE CASCADE,
    
    INDEX idx_code (discount_code),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 1.8 reservation_discounts (ส่วนลดที่ใช้แล้ว)
-- ---------------------------------------------
CREATE TABLE reservation_discounts (
    reservation_id   VARCHAR(50),
    discount_id      VARCHAR(50),
    applied_amount   DECIMAL(10,2) NOT NULL,
    applied_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (reservation_id, discount_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
    FOREIGN KEY (discount_id) REFERENCES discounts(discount_id) ON DELETE CASCADE,
    INDEX idx_discount (discount_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2. INSERT SAMPLE DATA
-- =============================================

-- ---------------------------------------------
-- 2.1 owners (เจ้าของร้าน)
-- ---------------------------------------------
INSERT INTO owners (owner_id, email, password_hash, first_name, last_name, phone) VALUES
('owner001', 'sompong@tempstation.com', '$2a$10$examplehash1', 'สมพงษ์', 'ใจดี', '0812345678'),
('owner002', 'siriporn@tempstation.com', '$2a$10$examplehash2', 'ศิริพร', 'สุขสวัสดิ์', '0898765432');

-- ---------------------------------------------
-- 2.2 employees (พนักงาน)
-- ---------------------------------------------
INSERT INTO employees (employee_id, owner_id, email, password_hash, first_name, last_name, phone, position, is_active) VALUES
('emp001', 'owner001', 'somsak@tempstation.com', '$2a$10$examplehash3', 'สมศักดิ์', 'ทำงานดี', '0823456789', 'manager', 1),
('emp002', 'owner001', 'wanida@tempstation.com', '$2a$10$examplehash4', 'วนิดา', 'บริการดี', '0834567890', 'staff', 1),
('emp003', 'owner002', 'preecha@tempstation.com', '$2a$10$examplehash5', 'ปรีชา', 'รวดเร็ว', '0845678901', 'manager', 1);

-- ---------------------------------------------
-- 2.3 customers (ลูกค้า)
-- ---------------------------------------------
INSERT INTO customers (customer_id, email, password_hash, first_name, last_name, phone, line_id, is_active) VALUES
('cust001', 'john.doe@email.com', '$2a$10$examplehash6', 'John', 'Doe', '0856789012', 'john_123', 1),
('cust002', 'jane.smith@email.com', '$2a$10$examplehash7', 'Jane', 'Smith', '0867890123', NULL, 1),
('cust003', 'surasak@email.com', '$2a$10$examplehash8', 'สุรศักดิ์', 'เดินทาง', '0878901234', 'surasak_travel', 1),
('cust004', 'testuser@email.com', '$2a$10$examplehash9', 'ทดสอบ', 'ระบบ', '0812345678', NULL, 1);

-- ---------------------------------------------
-- 2.4 motorcycles (รถจักรยานยนต์)
-- ---------------------------------------------
INSERT INTO motorcycles
(motorcycle_id, brand, model, year, license_plate, color, engine_cc, price_per_day, image_url, is_available, description, maintenance_status)
VALUES
-- HONDA (12 คัน)
('MOTO001','Honda','Super Cub',2023,'กข1234','เขียว',110,350.00,'https://www.thaihonda.co.th/honda/uploads/cache/926/photos/shares/newsupercub/ThaiHonda_Supercub2023_BikeGallery-Green-926x518.jpg',1,'รถคลาสสิก สไตล์วินเทจ เหมาะกับการใช้งานในเมือง','READY'),
('MOTO002','Honda','Wave 110i',2023,'กข1235','แดง',110,300.00,'https://www.thaihonda.co.th/honda/uploads/product_image_slide/photos/shares/10ThHonda_New-Wave_110i_2023_Wing-Center_Thumbnail_Product_430x310.png',1,'รถครอบจักรยานยนต์เศรษฐกิจ ประหยัดน้ำมัน','READY'),
('MOTO003','Honda','Scoopy i',2022,'กข1236','ขาว',110,320.00,'https://www.thaihonda.co.th/honda/uploads/product_image_slide/photos/shares/Website_Product_Photo-Thumb_nail_Product_430x310.png',1,'รถสกูตเตอร์สไตล์น่ารัก สำหรับผู้หญิง','READY'),
('MOTO004','Honda','Giorno',2024,'กข1237','เหลือง',125,380.00,'https://www.thaihonda.co.th/honda/uploads/cache/926/photos/shares/giono24/galgiorno24/Honda_Giorno_Bike_Gallery926x518_yellow_copy.jpg',1,'รถสกูตเตอร์พรีเมียม สไตล์โมเดิร์น','READY'),
('MOTO005','Honda','Wave 125i',2024,'กข1238','แดง',125,330.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/2024_Wave125i/Color/ThaiHonda_Wave125i_2024_ColorChart-685x426-red-wheel.png',1,'รถครอบจักรยานยนต์ 125cc ประสิทธิภาพสูง','READY'),
('MOTO006','Honda','Click 125i',2022,'กข1239','ฟ้า',125,400.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/AllNewClick125-2022/Colorselection/Click125-WebsiteProductPhoto-ColorSection-Urban-B-685x426.png',1,'รถสกูตเตอร์อัตโนมัติ สมรรถนะดี','READY'),
('MOTO007','Honda','Lead 125',2023,'กข1240','ดำ',125,420.00,'https://www.thaihonda.co.th/honda/uploads/cache/1440/photos/shares/Lead125/LO_LEAD125Black_Product_Photo_Thumbnail_Product.jpg',1,'รถสกูตเตอร์พรีเมียม ขนาดกะทัดรัด','READY'),
('MOTO008','Honda','PCX160',2023,'กข1241','ดำ',160,650.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/new-pcx-160-2023/7/png/02-G-B-__________-_______.png',1,'รถสกูตเตอร์อัตโนมัติ สภาพดีมาก','READY'),
('MOTO009','Honda','Click 160i',2023,'กข1242','ขาว',160,580.00,'https://www.thaihonda.co.th/honda/uploads/cache/1370/photos/shares/NewClick160-2023/Model/Honda_Click160-color_section685x426-whiteABS.png',1,'รถสกูตเตอร์สมรรถนะสูง 160cc','MAINTENANCE'), -- เปลี่ยนจาก REPAIR เป็น MAINTENANCE
('MOTO010','Honda','ADV160',2022,'กข1243','ดำ',160,750.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/NewADV160-2022/Carcolor/02-Black.png',1,'รถสกูตเตอร์ผจญภัย ออฟโรด','READY'),
('MOTO011','Honda','Forza 350',2024,'กข1244','ขาว',350,1200.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/24NewForza350/Color_Chart_W685xH426_PX.jpg',1,'รถสกูตเตอร์ทัวริงขนาดใหญ่ สบายสบาย','READY'),
('MOTO012','Honda','ADV350',2023,'กข1245','แดง',350,1300.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/ADV350RoadSync/Color_Chart_W685xH426_PX_RED.png',1,'รถสกูตเตอร์ผจญภัยขนาดใหญ่','READY'),

-- YAMAHA (7 คัน)
('MOTO013','Yamaha','Fazzio',2025,'กค1234','ขาว',125,450.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/fazzio-2025/lineup-360-retro-white/14.png?sfvrsn=6513ab43_2',1,'รถสกูตเตอร์รีโทร สไตล์คลาสสิก','READY'),
('MOTO014','Yamaha','Finn',2024,'กค1235','เขียว',125,380.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2024/finn-2024/lineup-360-green-ubs/2.png?sfvrsn=37bb5c14_2',1,'รถสกูตเตอร์เศรษฐกิจ ใช้งานง่าย','READY'),
('MOTO015','Yamaha','Grand Filano',2024,'กค1236','เทา',125,480.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2024/grand-filano-hybrid-connected-2024/lineup-360-titanium-gray/2.png?sfvrsn=d33825a6_2',1,'รถสกูตเตอร์ไฮบริดพรีเมียม','READY'),
('MOTO016','Yamaha','Aerox',2025,'กค1237','เงิน',155,700.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/all-new-aerox-2025/lineup-360-silver-star/2.png?sfvrsn=ba06ecb7_2',1,'รถสกูตเตอร์สปอร์ต สไตล์สปอร์ต','READY'),
('MOTO017','Yamaha','NMAX',2025,'กค1238','ดำ',155,750.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/all-new-nmax-2025/lineup-360-magma-black/2.png?sfvrsn=f4ef4f1a_2',1,'รถสกูตเตอร์พรีเมียม','READY'),
('MOTO018','Yamaha','XMAX',2025,'กค1239','เทา',250,1500.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/xmax-2025/lineup-360-dark-gray/2.png?sfvrsn=e29fd942_2',1,'รถสกูตเตอร์ทัวริงขนาดใหญ่ สบายสุด','READY'),

-- KAWASAKI (4 คัน)
('MOTO019','Kawasaki','Ninja 300',2021,'กจ3456','เขียว',300,1200.00,'../img/default-bike.jpg',0,'รถสปอร์ตขนาดใหญ่ กำลังซ่อมบำรุง','MAINTENANCE'),
('MOTO020','Kawasaki','Z650',2022,'กจ3457','ดำ',650,1800.00,'../img/default-bike.jpg',1,'รถเนคคิดสตรีทฟิเกอร์ สไตล์ล้ำสมัย','READY'),
('MOTO021','Kawasaki','Versys 650',2023,'กจ3458','ส้ม',650,1600.00,'../img/default-bike.jpg',1,'รถผจญภัยอเนกประสงค์','READY'),
('MOTO022','Kawasaki','Ninja 250',2022,'กจ3459','เขียว',250,1000.00,'../img/default-bike.jpg',1,'รถสปอร์ตสำหรับเริ่มต้น','READY'),

-- เพิ่มเติม
('MOTO023','Honda','CBR150R',2022,'กง9012','แดง',150,800.00,'../img/default-bike.jpg',1,'รถสปอร์ต สภาพดี','READY'),
('MOTO024','Yamaha','MT-15',2023,'กค1240','น้ำเงิน',150,850.00,'../img/default-bike.jpg',1,'รถเนคคิดสปอร์ต ขนาดกะทัดรัด','READY'),
('MOTO025','Honda','NMAX',2024,'กค1241','ดำ',155,750.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/xmax-2025/lineup-360-dark-gray/2.png?sfvrsn=e29fd942_2',1,'รถสกูตเตอร์พรีเมียม','CLEANING'),
('MOTO026','Honda','PCX160',2023,'กข1246','เทา',160,650.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/new-pcx-160-2023/7/png/02-G-B-__________-_______.png',0,'รอตรวจสภาพหลังเช่า','MAINTENANCE'),
('MOTO027','Yamaha','Aerox',2024,'กค1242','แดง',155,700.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/all-new-aerox-2025/lineup-360-silver-star/2.png?sfvrsn=ba06ecb7_2',1,'รถสปอร์ต ยอดนิยม','READY'),
('MOTO028','Honda','Wave 110i',2023,'กข1247','น้ำเงิน',110,300.00,'https://www.thaihonda.co.th/honda/uploads/product_image_slide/photos/shares/10ThHonda_New-Wave_110i_2023_Wing-Center_Thumbnail_Product_430x310.png',1,'รถประหยัดน้ำมัน','DAMAGED'),
('MOTO029','Yamaha','Grand Filano',2023,'กค1243','ครีม',125,480.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2024/grand-filano-hybrid-connected-2024/lineup-360-titanium-gray/2.png?sfvrsn=d33825a6_2',1,'รถพรีเมียม สีสวย','READY');

-- ---------------------------------------------
-- 2.5 reservations (การจอง)
-- ---------------------------------------------
INSERT INTO reservations
(reservation_id, customer_id, employee_id, motorcycle_id,
 start_datetime, end_datetime, total_days, total_price,
 status, deposit_amount, discount_amount, final_price,
 pickup_location, return_location, pickup_details, return_details,
 return_condition, return_motorcycle_status, return_checked_at, return_checked_by)
VALUES
-- เสร็จสิ้นแล้ว (completed)
('res001','cust001','emp001','MOTO008',
 '2024-01-15 10:00:00','2024-01-17 18:00:00',3,1950.00,
 'completed',1000.00,0.00,1950.00,
 'สาขาหลัก ถ.สุขุมวิท','สาขาหลัก ถ.สุขุมวิท',
 'รับรถพร้อมหมวกกันน็อค 2 ใบ น้ำมันเต็มถัง',
 'คืนรถสภาพสมบูรณ์ ไม่มีรอย',
 'รถสภาพดี ไม่มีรอยขีดข่วน น้ำมันเหลือ 3 ขีด', 'READY', '2024-01-17 18:30:00', 'emp001'),

('res002','cust002','emp002','MOTO016',
 '2024-02-01 09:00:00','2024-02-03 18:00:00',3,2100.00,
 'completed',1000.00,100.00,2000.00,
 'สาขาหลัก ถ.สุขุมวิท','สาขาหลัก ถ.สุขุมวิท',
 'รับรถช่วงเช้า พนักงานอธิบายการใช้งาน',
 'คืนรถตรงเวลา มีรอยถลอกด้านซ้ายเล็กน้อย',
 'มีรอยถลอกที่แฟริ่งด้านซ้าย ประมาณ 5 ซม.', 'MAINTENANCE', '2024-02-03 18:15:00', 'emp002'),

('res003','cust003','emp001','MOTO018',
 '2024-02-15 13:00:00','2024-02-18 13:00:00',3,4500.00,
 'completed',2000.00,0.00,4500.00,
 'สาขารัชดา','สาขารัชดา',
 'รับรถบ่าย หมวกกันน็อค 2 ใบ',
 'คืนรถเรียบร้อย',
 'รถสภาพปกติ พร้อมใช้งาน', 'READY', '2024-02-18 13:30:00', 'emp001'),

-- ยืนยันแล้ว (confirmed)
('res004','cust001',NULL,'MOTO013',
 '2024-03-20 10:00:00','2024-03-22 18:00:00',2,900.00,
 'confirmed',500.00,0.00,900.00,
 'สาขาหลัก ถ.สุขุมวิท','สาขาหลัก ถ.สุขุมวิท',
 'รับรถหมวกกันน็อค 2 ใบ',
 NULL, NULL, NULL, NULL, NULL),

('res005','cust004',NULL,'MOTO001',
 '2024-03-25 09:00:00','2024-03-27 18:00:00',3,1050.00,
 'confirmed',500.00,50.00,1000.00,
 'สาขารัชดา','สาขารัชดา',
 'รับรถเช้า ต้องการหมวกกันน็อคสีดำ',
 NULL, NULL, NULL, NULL, NULL),

-- รอดำเนินการ (pending)
('res006','cust002',NULL,'MOTO005',
 '2024-04-01 10:00:00','2024-04-03 18:00:00',3,990.00,
 'pending',500.00,0.00,990.00,
 'สาขาหลัก ถ.สุขุมวิท','สาขาหลัก ถ.สุขุมวิท',
 'รับรถเที่ยง',
 NULL, NULL, NULL, NULL, NULL),

('res007','cust003',NULL,'MOTO026',
 '2024-04-05 09:00:00','2024-04-07 18:00:00',3,1950.00,
 'pending',1000.00,0.00,1950.00,
 'สาขารัชดา','สาขารัชดา',
 'ขอรับรถเช้า',
 NULL, NULL, NULL, NULL, NULL),

-- ยกเลิก (cancelled)
('res008','cust001','emp003','MOTO020',
 '2024-03-01 10:00:00','2024-03-03 18:00:00',3,5400.00,
 'cancelled',1000.00,0.00,5400.00,
 'สาขาหลัก ถ.สุขุมวิท','สาขาหลัก ถ.สุขุมวิท',
 'ลูกค้าติดธุระ ขอเลื่อน',
 'ยกเลิกการจอง',
 NULL, NULL, NULL, NULL);

-- ---------------------------------------------
-- 2.6 payments (การชำระเงิน)
-- ---------------------------------------------
INSERT INTO payments
(payment_id, reservation_id, amount, payment_method, payment_status,
 payment_date, transaction_id, slip_image_url, notes)
VALUES
('pay001','res001',1950.00,'cash','verified',
 '2024-01-14 10:30:00','CASH001',NULL,'ชำระเงินสดหน้าร้าน'),

('pay002','res002',2000.00,'bank_transfer','verified',
 '2024-01-31 20:10:00','TRX20240201-001','/slips/slip002.jpg','โอนผ่านแอปธนาคาร'),

('pay003','res003',4500.00,'qr_code','verified',
 '2024-02-14 14:30:00','QR20240214-001','/slips/slip003.jpg','สแกนจ่าย'),

('pay004','res004',900.00,'bank_transfer','verified',
 '2024-03-19 09:15:00','TRX20240319-001','/slips/slip004.jpg','ชำระค่ามัดจำ'),

('pay005','res005',1000.00,'qr_code','verified',
 '2024-03-24 16:20:00','QR20240324-001','/slips/slip005.jpg','ชำระเต็มจำนวน'),

('pay006','res006',990.00,'bank_transfer','pending',
 NULL,'TRX20240330-001','/slips/slip006.jpg','รอตรวจสอบ'),

('pay007','res007',1950.00,'bank_transfer','pending',
 NULL,NULL,NULL,'ยังไม่ชำระ'),

('pay008','res008',5400.00,'bank_transfer','refunded',
 '2024-02-29 11:30:00','TRX20240229-001','/slips/slip008.jpg','คืนเงินเนื่องจากยกเลิก');

-- ---------------------------------------------
-- 2.7 discounts (ส่วนลด)
-- ---------------------------------------------
INSERT INTO discounts
(discount_id, discount_code, discount_type, discount_value,
 min_rental_days, max_discount_amount, start_date, end_date,
 is_active, created_by, usage_limit, used_count)
VALUES
('disc001','WELCOME100','fixed',100.00,2,100.00,'2024-01-01','2024-12-31',1,'owner001',100,10),
('disc002','SUMMER10','percentage',10.00,3,500.00,'2024-01-01','2024-12-31',1,'owner001',50,20),
('disc003','NEWYEAR50','fixed',50.00,1,50.00,'2024-12-20','2025-01-10',1,'owner002',200,0),
('disc004','LONGTRIP15','percentage',15.00,5,1000.00,'2024-01-01','2024-12-31',1,'owner001',30,5);

-- ---------------------------------------------
-- 2.8 reservation_discounts (ส่วนลดที่ใช้แล้ว)
-- ---------------------------------------------
INSERT INTO reservation_discounts
(reservation_id, discount_id, applied_amount)
VALUES
('res002','disc001',100.00),
('res005','disc001',50.00);

-- =============================================
-- 3. CREATE VIEWS (สำหรับ报表/รายงาน)
-- =============================================

-- ---------------------------------------------
-- 3.1 view_customer_summary (สรุปลูกค้า)
-- ---------------------------------------------
CREATE VIEW view_customer_summary AS
SELECT 
    c.customer_id,
    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
    c.email,
    c.phone,
    COUNT(DISTINCT r.reservation_id) AS total_bookings,
    COUNT(DISTINCT CASE WHEN r.status = 'completed' THEN r.reservation_id END) AS completed_bookings,
    COALESCE(SUM(r.final_price), 0) AS total_spent,
    COALESCE(AVG(r.total_days), 0) AS avg_rental_days,
    MAX(r.created_at) AS last_booking_date,
    CASE 
        WHEN MAX(r.created_at) >= DATE_SUB(NOW(), INTERVAL 3 MONTH) THEN 'Active'
        WHEN MAX(r.created_at) >= DATE_SUB(NOW(), INTERVAL 12 MONTH) THEN 'Inactive'
        ELSE 'Churned'
    END AS customer_status
FROM customers c
LEFT JOIN reservations r ON c.customer_id = r.customer_id
GROUP BY c.customer_id, c.first_name, c.last_name, c.email, c.phone;

-- ---------------------------------------------
-- 3.2 view_motorcycle_summary (สรุปรถเช่า)
-- ---------------------------------------------
CREATE VIEW view_motorcycle_summary AS
SELECT 
    m.motorcycle_id,
    CONCAT(m.brand, ' ', m.model) AS motorcycle_name,
    m.license_plate,
    m.price_per_day,
    m.maintenance_status,
    COUNT(DISTINCT r.reservation_id) AS total_bookings,
    COUNT(DISTINCT CASE WHEN r.status = 'completed' THEN r.reservation_id END) AS completed_bookings,
    COALESCE(SUM(r.final_price), 0) AS total_revenue,
    COALESCE(AVG(r.total_days), 0) AS avg_rental_days,
    CASE 
        WHEN m.is_available = 1 AND m.maintenance_status = 'READY' THEN 'พร้อมใช้งาน'
        WHEN m.maintenance_status = 'MAINTENANCE' THEN 'ซ่อมบำรุง'
        WHEN m.maintenance_status = 'CLEANING' THEN 'ทำความสะอาด'
        WHEN m.maintenance_status = 'DAMAGED' THEN 'เสียหาย'
        WHEN m.maintenance_status = 'LOST' THEN 'สูญหาย'
        ELSE 'ไม่พร้อมใช้งาน'
    END AS status_display
FROM motorcycles m
LEFT JOIN reservations r ON m.motorcycle_id = r.motorcycle_id
GROUP BY m.motorcycle_id, m.brand, m.model, m.license_plate, 
         m.price_per_day, m.maintenance_status, m.is_available;

-- ---------------------------------------------
-- 3.3 view_revenue_report (รายงานรายได้)
-- ---------------------------------------------
CREATE VIEW view_revenue_report AS
SELECT 
    DATE_FORMAT(r.updated_at, '%Y-%m') AS month,
    COUNT(DISTINCT r.reservation_id) AS total_bookings,
    COUNT(DISTINCT CASE WHEN r.status = 'completed' THEN r.reservation_id END) AS completed_bookings,
    COALESCE(SUM(CASE WHEN r.status = 'completed' THEN r.final_price END), 0) AS revenue,
    COALESCE(SUM(p.amount), 0) AS payment_collected,
    COUNT(DISTINCT c.customer_id) AS unique_customers
FROM reservations r
LEFT JOIN payments p ON r.reservation_id = p.reservation_id AND p.payment_status = 'verified'
LEFT JOIN customers c ON r.customer_id = c.customer_id
GROUP BY DATE_FORMAT(r.updated_at, '%Y-%m')
ORDER BY month DESC;

-- =============================================
-- 4. CREATE INDEXES (เพิ่มประสิทธิภาพ)
-- =============================================

-- reservations indexes
CREATE INDEX idx_reservations_dates ON reservations(start_datetime, end_datetime);
CREATE INDEX idx_reservations_status_created ON reservations(status, created_at);

-- payments indexes
CREATE INDEX idx_payments_reservation_status ON payments(reservation_id, payment_status);
CREATE INDEX idx_payments_date ON payments(payment_date);

-- motorcycles indexes
CREATE INDEX idx_motorcycles_status_price ON motorcycles(maintenance_status, price_per_day);
CREATE INDEX idx_motorcycles_brand ON motorcycles(brand);

-- =============================================
-- 5. CREATE TRIGGERS (ความอัตโนมัติ)
-- ---------------------------------------------

-- 5.1 อัปเดต is_available อัตโนมัติเมื่อ maintenance_status เปลี่ยน
DELIMITER $$
CREATE TRIGGER trg_motorcycles_update_available
BEFORE UPDATE ON motorcycles
FOR EACH ROW
BEGIN
    IF NEW.maintenance_status IN ('MAINTENANCE', 'DAMAGED', 'LOST', 'UNAVAILABLE') THEN
        SET NEW.is_available = 0;
    ELSEIF NEW.maintenance_status = 'READY' THEN
        SET NEW.is_available = 1;
    END IF;
END$$
DELIMITER ;

-- 5.2 ตรวจสอบวันที่จองไม่ซ้อนทับกัน
DELIMITER $$
CREATE TRIGGER trg_reservations_check_overlap
BEFORE INSERT ON reservations
FOR EACH ROW
BEGIN
    DECLARE overlap_count INT;
    
    SELECT COUNT(*) INTO overlap_count
    FROM reservations
    WHERE motorcycle_id = NEW.motorcycle_id
      AND status IN ('confirmed', 'active')
      AND NEW.start_datetime < end_datetime
      AND NEW.end_datetime > start_datetime;
    
    IF overlap_count > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'รถคันนี้ถูกจองในช่วงเวลาดังกล่าวแล้ว';
    END IF;
END$$
DELIMITER ;

-- 5.3 อัปเดต used_count ใน discounts อัตโนมัติ
DELIMITER $$
CREATE TRIGGER trg_reservation_discounts_update_count
AFTER INSERT ON reservation_discounts
FOR EACH ROW
BEGIN
    UPDATE discounts 
    SET used_count = used_count + 1 
    WHERE discount_id = NEW.discount_id;
END$$
DELIMITER ;

-- =============================================
-- 6. VERIFICATION QUERIES (ตรวจสอบข้อมูล)
-- =============================================

-- ตรวจสอบจำนวนรถแยกตามสถานะ
SELECT '--- MOTORCYCLE STATUS SUMMARY ---' AS '';
SELECT 
    maintenance_status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM motorcycles), 1) as percentage
FROM motorcycles 
GROUP BY maintenance_status 
ORDER BY FIELD(maintenance_status, 'READY', 'MAINTENANCE', 'CLEANING', 'DAMAGED', 'LOST', 'UNAVAILABLE');

-- ตรวจสอบการจองแยกตามสถานะ
SELECT '--- RESERVATION STATUS SUMMARY ---' AS '';
SELECT 
    status,
    COUNT(*) as count,
    SUM(final_price) as total_amount
FROM reservations 
GROUP BY status 
ORDER BY status;

-- ตรวจสอบรายได้
SELECT '--- REVENUE SUMMARY ---' AS '';
SELECT 
    COALESCE(SUM(final_price), 0) as total_revenue,
    COUNT(DISTINCT reservation_id) as paid_bookings
FROM reservations 
WHERE status = 'completed';

-- =============================================
-- 7. GRANT PERMISSIONS (สำหรับแอพพลิเคชัน)
-- =============================================

-- CREATE USER IF NOT EXISTS 'rental_app'@'localhost' IDENTIFIED BY 'password123';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON motorcycle_rental_new.* TO 'rental_app'@'localhost';
-- GRANT EXECUTE ON motorcycle_rental_new.* TO 'rental_app'@'localhost';
-- FLUSH PRIVILEGES;

-- =============================================
-- COMPLETE! Database is ready to use
-- =============================================
SELECT '✅ DATABASE SETUP COMPLETED SUCCESSFULLY!' AS '';