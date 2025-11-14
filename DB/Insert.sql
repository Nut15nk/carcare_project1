-- เพิ่มข้อมูลเจ้าของร้าน
INSERT INTO owners (owner_id, email, password_hash, first_name, last_name, phone) VALUES
('OWN001', 'sompong@tempstation.com', '$2b$10$examplehash1', 'สมพงษ์', 'ใจดี', '0812345678'),
('OWN002', 'siriporn@tempstation.com', '$2b$10$examplehash2', 'ศิริพร', 'สุขสวัสดิ์', '0898765432');

-- เพิ่มข้อมูลพนักงาน
INSERT INTO employees (employee_id, owner_id, email, password_hash, first_name, last_name, phone, position) VALUES
('EMP001', 'OWN001', 'somsak@tempstation.com', '$2b$10$examplehash3', 'สมศักดิ์', 'ทำงานดี', '0823456789', 'manager'),
('EMP002', 'OWN001', 'wanida@tempstation.com', '$2b$10$examplehash4', 'วนิดา', 'บริการดี', '0834567890', 'staff'),
('EMP003', 'OWN002', 'preecha@tempstation.com', '$2b$10$examplehash5', 'ปรีชา', 'รวดเร็ว', '0845678901', 'manager');

-- เพิ่มข้อมูลลูกค้า (สมัครผ่านเว็บ)
INSERT INTO customers (customer_id, email, password_hash, first_name, last_name, phone, address, license_number, is_verified) VALUES
('CUST001', 'john.doe@email.com', '$2b$10$examplehash6', 'John', 'Doe', '0856789012', '123 ถนนสุขุมวิท กรุงเทพ', 'L1234567', TRUE),
('CUST002', 'jane.smith@email.com', '$2b$10$examplehash7', 'Jane', 'Smith', '0867890123', '456 ถนนสีลม กรุงเทพ', 'L7654321', TRUE),
('CUST003', 'surasak@email.com', '$2b$10$examplehash8', 'สุรศักดิ์', 'เดินทาง', '0878901234', '789 ถนนรัชดา กรุงเทพ', 'L1122334', FALSE);

-- เพิ่มข้อมูลรถจักรยานยนต์
INSERT INTO motorcycles (motorcycle_id, brand, model, year, license_plate, color, engine_cc, price_per_day, is_available, description) VALUES
('MOTO001', 'Honda', 'PCX160', 2023, 'กข1234', 'ดำ', 160, 650.00, TRUE, 'รถสกูตเตอร์อัตโนมัติ สภาพดีมาก'),
('MOTO002', 'Yamaha', 'NMAX', 2023, 'กค5678', 'ขาว', 155, 700.00, TRUE, 'รถสกูตเตอร์พรีเมี่ยม'),
('MOTO003', 'Honda', 'CBR150R', 2022, 'กง9012', 'แดง', 150, 800.00, TRUE, 'รถสปอร์ต สภาพดี'),
('MOTO004', 'Kawasaki', 'Ninja 300', 2021, 'กจ3456', 'เขียว', 300, 1200.00, FALSE, 'รถสปอร์ตขนาดใหญ่ กำลังซ่อมบำรุง');

-- เพิ่มข้อมูลการจอง
INSERT INTO reservations (reservation_id, customer_id, employee_id, motorcycle_id, start_date, end_date, total_days, total_price, deposit_amount, discount_amount, final_price, status) VALUES
-- ลูกค้าจองผ่านพนักงาน
('RES001', 'CUST001', 'EMP001', 'MOTO001', '2024-01-15', '2024-01-17', 3, 1950.00, 1000.00, 0, 1950.00, 'completed'),
-- ลูกค้าจองผ่านเว็บ (employee_id เป็น NULL)
('RES002', 'CUST002', NULL, 'MOTO002', '2024-01-20', '2024-01-22', 3, 2100.00, 1000.00, 100.00, 2000.00, 'confirmed'),
('RES003', 'CUST003', NULL, 'MOTO003', '2024-01-25', '2024-01-27', 3, 2400.00, 1000.00, 0, 2400.00, 'pending');

-- เพิ่มข้อมูลการชำระเงิน
INSERT INTO payments (payment_id, reservation_id, amount, payment_method, payment_status, payment_date, transaction_id) VALUES
('PAY001', 'RES001', 1950.00, 'cash', 'paid', '2024-01-14 10:30:00', 'CASH001'),
('PAY002', 'RES002', 2000.00, 'bank_transfer', 'paid', '2024-01-19 14:20:00', 'BANK002'),
('PAY003', 'RES003', 2400.00, 'qr_code', 'pending', NULL, NULL);

-- เพิ่มข้อมูลส่วนลด
INSERT INTO discounts (discount_id, discount_code, discount_type, discount_value, min_rental_days, max_discount_amount, start_date, end_date, created_by) VALUES
('DISC001', 'WELCOME100', 'fixed', 100.00, 2, 100.00, '2024-01-01', '2024-12-31', 'OWN001'),
('DISC002', 'SUMMER10', 'percentage', 10.00, 3, 500.00, '2024-01-01', '2024-12-31', 'OWN001');

-- เพิ่มข้อมูลการใช้ส่วนลด
INSERT INTO reservation_discounts (reservation_id, discount_id, applied_amount) VALUES
('RES002', 'DISC001', 100.00);