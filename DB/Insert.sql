-- เพิ่มข้อมูลเจ้าของร้าน
INSERT INTO owners (owner_id, email, password_hash, first_name, last_name, phone) VALUES
('owner001', 'sompong@tempstation.com', '$2a$10$examplehash1', 'สมพงษ์', 'ใจดี', '0812345678'),
('owner002', 'siriporn@tempstation.com', '$2a$10$examplehash2', 'ศิริพร', 'สุขสวัสดิ์', '0898765432');

-- เพิ่มข้อมูลพนักงาน
INSERT INTO employees (employee_id, owner_id, email, password_hash, first_name, last_name, phone, position) VALUES
('emp001', 'owner001', 'somsak@tempstation.com', '$2a$10$examplehash3', 'สมศักดิ์', 'ทำงานดี', '0823456789', 'manager'),
('emp002', 'owner001', 'wanida@tempstation.com', '$2a$10$examplehash4', 'วนิดา', 'บริการดี', '0834567890', 'staff'),
('emp003', 'owner002', 'preecha@tempstation.com', '$2a$10$examplehash5', 'ปรีชา', 'รวดเร็ว', '0845678901', 'manager');

-- เพิ่มข้อมูลลูกค้า (สมัครผ่านเว็บ)
INSERT INTO customers (customer_id, email, password_hash, first_name, last_name, phone, address, license_number, date_of_birth, id_card_number, is_verified) VALUES
('cust001', 'john.doe@email.com', '$2a$10$examplehash6', 'John', 'Doe', '0856789012', '123 ถนนสุขุมวิท กรุงเทพ', 'L1234567', '1990-05-15', '1234567890123', 1),
('cust002', 'jane.smith@email.com', '$2a$10$examplehash7', 'Jane', 'Smith', '0867890123', '456 ถนนสีลม กรุงเทพ', 'L7654321', '1988-08-20', '9876543210987', 1),
('cust003', 'surasak@email.com', '$2a$10$examplehash8', 'สุรศักดิ์', 'เดินทาง', '0878901234', '789 ถนนรัชดา กรุงเทพ', 'L1122334', '1995-12-10', '4567891230567', 0),
('cust004', 'testuser@email.com', '$2a$10$examplehash9', 'ทดสอบ', 'ระบบ', '0812345678', 'ที่อยู่ทดสอบ', 'L12345678', '1992-03-25', '1112223334445', 1);

-- เพิ่มข้อมูลรถจักรยานยนต์
INSERT INTO motorcycles (motorcycle_id, brand, model, year, license_plate, color, engine_cc, price_per_day, image_url, is_available, description, maintenance_status) VALUES
('moto001', 'Honda', 'PCX160', 2023, 'กข1234', 'ดำ', 160, 650.00, '/images/pcx160.jpg', 1, 'รถสกูตเตอร์อัตโนมัติ สภาพดีมาก', 'ready'),
('moto002', 'Yamaha', 'NMAX', 2023, 'กค5678', 'ขาว', 155, 700.00, '/images/nmax.jpg', 1, 'รถสกูตเตอร์พรีเมี่ยม', 'ready'),
('moto003', 'Honda', 'CBR150R', 2022, 'กง9012', 'แดง', 150, 800.00, '/images/cbr150r.jpg', 1, 'รถสปอร์ต สภาพดี', 'ready'),
('moto004', 'Kawasaki', 'Ninja 300', 2021, 'กจ3456', 'เขียว', 300, 1200.00, '/images/ninja300.jpg', 0, 'รถสปอร์ตขนาดใหญ่ กำลังซ่อมบำรุง', 'maintenance');

-- เพิ่มข้อมูลการจอง
INSERT INTO reservations (reservation_id, customer_id, employee_id, motorcycle_id, start_date, end_date, total_days, total_price, status, deposit_amount, discount_amount, final_price, pickup_location, return_location) VALUES
-- ลูกค้าจองผ่านพนักงาน
('res001', 'cust001', 'emp001', 'moto001', '2024-01-15', '2024-01-17', 3, 1950.00, 'completed', 1000.00, 0.00, 1950.00, 'สาขาหลัก', 'สาขาหลัก'),
-- ลูกค้าจองผ่านเว็บ (employee_id เป็น NULL)
('res002', 'cust002', NULL, 'moto002', '2024-01-20', '2024-01-22', 3, 2100.00, 'confirmed', 1000.00, 100.00, 2000.00, 'สาขาหลัก', 'สาขาหลัก'),
('res003', 'cust003', NULL, 'moto003', '2024-01-25', '2024-01-27', 3, 2400.00, 'pending', 1000.00, 0.00, 2400.00, 'สาขาหลัก', 'สาขาหลัก'),
-- การจองล่าสุดสำหรับทดสอบ
('res004', 'cust004', NULL, 'moto001', '2025-11-20', '2025-11-22', 2, 1300.00, 'pending', 390.00, 0.00, 1300.00, 'สาขาหลัก', 'สาขาหลัก');

-- เพิ่มข้อมูลการชำระเงิน - แก้ payment_id เป็น VARCHAR(255)
INSERT INTO payments (payment_id, reservation_id, amount, payment_method, payment_status, payment_date, transaction_id, slip_image_url) VALUES
('pay001', 'res001', 1950.00, 'cash', 'paid', '2024-01-14 10:30:00', 'CASH001', NULL),
('pay002', 'res002', 2000.00, 'bank_transfer', 'paid', '2024-01-19 14:20:00', 'BANK002', '/slips/slip002.jpg'),
('pay003', 'res003', 2400.00, 'qr_code', 'pending', NULL, NULL, NULL),
('pay004', 'res004', 1300.00, 'bank_transfer', 'pending', NULL, NULL, NULL);

-- เพิ่มข้อมูลส่วนลด - แก้ is_active เป็น 1
INSERT INTO discounts (discount_id, discount_code, discount_type, discount_value, min_rental_days, max_discount_amount, start_date, end_date, is_active, created_by, usage_limit, used_count) VALUES
('disc001', 'WELCOME100', 'fixed', 100.00, 2, 100.00, '2024-01-01', '2024-12-31', 1, 'owner001', 100, 5),
('disc002', 'SUMMER10', 'percentage', 10.00, 3, 500.00, '2024-01-01', '2024-12-31', 1, 'owner001', 50, 12),
('disc003', 'NEWUSER50', 'fixed', 50.00, 1, 50.00, '2025-01-01', '2025-12-31', 1, 'owner002', 200, 8);

-- เพิ่มข้อมูลการใช้ส่วนลด
INSERT INTO reservation_discounts (reservation_id, discount_id, applied_amount) VALUES
('res002', 'disc001', 100.00);