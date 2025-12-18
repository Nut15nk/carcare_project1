-- 1. เพิ่มข้อมูลเจ้าของร้าน
INSERT INTO owners (owner_id, email, password_hash, first_name, last_name, phone) VALUES
('owner001', 'sompong@tempstation.com', '$2a$10$examplehash1', 'สมพงษ์', 'ใจดี', '0812345678'),
('owner002', 'siriporn@tempstation.com', '$2a$10$examplehash2', 'ศิริพร', 'สุขสวัสดิ์', '0898765432');

-- 2. เพิ่มข้อมูลพนักงาน
INSERT INTO employees (employee_id, owner_id, email, password_hash, first_name, last_name, phone, position) VALUES
('emp001', 'owner001', 'somsak@tempstation.com', '$2a$10$examplehash3', 'สมศักดิ์', 'ทำงานดี', '0823456789', 'manager'),
('emp002', 'owner001', 'wanida@tempstation.com', '$2a$10$examplehash4', 'วนิดา', 'บริการดี', '0834567890', 'staff'),
('emp003', 'owner002', 'preecha@tempstation.com', '$2a$10$examplehash5', 'ปรีชา', 'รวดเร็ว', '0845678901', 'manager');

-- 3. เพิ่มข้อมูลลูกค้า
INSERT INTO customers (customer_id, email, password_hash, first_name, last_name, phone, address, license_number, date_of_birth, id_card_number, is_verified) VALUES
('cust001', 'john.doe@email.com', '$2a$10$examplehash6', 'John', 'Doe', '0856789012', '123 ถนนสุขุมวิท กรุงเทพ', 'L1234567', '1990-05-15', '1234567890123', 1),
('cust002', 'jane.smith@email.com', '$2a$10$examplehash7', 'Jane', 'Smith', '0867890123', '456 ถนนสีลม กรุงเทพ', 'L7654321', '1988-08-20', '9876543210987', 1),
('cust003', 'surasak@email.com', '$2a$10$examplehash8', 'สุรศักดิ์', 'เดินทาง', '0878901234', '789 ถนนรัชดา กรุงเทพ', 'L1122334', '1995-12-10', '4567891230567', 0),
('cust004', 'testuser@email.com', '$2a$10$examplehash9', 'ทดสอบ', 'ระบบ', '0812345678', 'ที่อยู่ทดสอบ', 'L12345678', '1992-03-25', '1112223334445', 1);

-- 4. เพิ่มข้อมูลรถจักรยานยนต์ (ชุดใหม่ 25 คัน)
-- หมายเหตุ: ผมตัดคำว่า motorcycle_rental. ออกเพื่อให้รันได้ทั่วไป และตรวจสอบคอลัมน์ created_at/updated_at ในตารางด้วยนะครับ
INSERT INTO motorcycles (motorcycle_id,brand,model,`year`,license_plate,color,engine_cc,price_per_day,image_url,is_available,description,maintenance_status,created_at,updated_at) VALUES
('MOTO001','Honda','Super Cub',2023,'กข1234','เขียว',110,350.00,'https://www.thaihonda.co.th/honda/uploads/cache/926/photos/shares/newsupercub/ThaiHonda_Supercub2023_BikeGallery-Green-926x518.jpg',1,'รถคลาสสิก สไตล์วินเทจ เหมาะกับการใช้งานในเมือง','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO002','Honda','Wave 110i',2023,'กข1235','แดง',110,300.00,'https://www.thaihonda.co.th/honda/uploads/product_image_slide/photos/shares/10ThHonda_New-Wave_110i_2023_Wing-Center_Thumbnail_Product_430x310.png',1,'รถครอบจักรยานยนต์เศรษฐกิจ ประหยัดน้ำมัน','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO003','Honda','Scoopy i',2022,'กข1236','ขาว',110,320.00,'https://www.thaihonda.co.th/honda/uploads/product_image_slide/photos/shares/Website_Product_Photo-Thumb_nail_Product_430x310.png',1,'รถสกูตเตอร์สไตล์น่ารัก สำหรับผู้หญิง','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO004','Honda','Giorno',2024,'กข1237','เหลือง',125,380.00,'https://www.thaihonda.co.th/honda/uploads/cache/926/photos/shares/giono24/galgiorno24/Honda_Giorno_Bike_Gallery926x518_yellow_copy.jpg',1,'รถสกูตเตอร์พรีเมียม สไตล์โมเดิร์น','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO005','Honda','Wave 125i',2024,'กข1238','แดง',125,330.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/2024_Wave125i/Color/ThaiHonda_Wave125i_2024_ColorChart-685x426-red-wheel.png',1,'รถครอบจักรยานยนต์ 125cc ประสิทธิภาพสูง','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO006','Honda','Click 125i',2022,'กข1239','ฟ้า',125,400.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/AllNewClick125-2022/Colorselection/Click125-WebsiteProductPhoto-ColorSection-Urban-B-685x426.png',1,'รถสกูตเตอร์อัตโนมัติ สมรรถนะดี','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO007','Honda','Lead 125',2023,'กข1240','ดำ',125,420.00,'https://www.thaihonda.co.th/honda/uploads/cache/1440/photos/shares/Lead125/LO_LEAD125Black_Product_Photo_Thumbnail_Product.jpg',1,'รถสกูตเตอร์พรีเมียม ขนาดกะทัดรัด','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO008','Honda','PCX160',2023,'กข1241','ดำ',160,650.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/new-pcx-160-2023/7/png/02-G-B-__________-_______.png',1,'รถสกูตเตอร์อัตโนมัติ สภาพดีมาก','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO009','Honda','Click 160i',2023,'กข1242','ขาว',160,580.00,'https://www.thaihonda.co.th/honda/uploads/cache/1370/photos/shares/NewClick160-2023/Model/Honda_Click160-color_section685x426-whiteABS.png',1,'รถสกูตเตอร์สมรรถนะสูง 160cc','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO010','Honda','ADV160',2022,'กข1243','ดำ',160,750.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/NewADV160-2022/Carcolor/02-Black.png',1,'รถสกูตเตอร์ผจญภัย ออฟโรด','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO011','Honda','Forza 350',2024,'กข1244','ขาว',350,1200.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/24NewForza350/Color_Chart_W685xH426_PX.jpg',1,'รถสกูตเตอร์ทัวริงขนาดใหญ่ สบายสบาย','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO012','Honda','ADV350',2023,'กข1245','แดง',350,1300.00,'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/ADV350RoadSync/Color_Chart_W685xH426_PX_RED.png',1,'รถสกูตเตอร์ผจญภัยขนาดใหญ่','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO013','Yamaha','Fazzio',2025,'กค1234','ขาว',125,450.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/fazzio-2025/lineup-360-retro-white/14.png?sfvrsn=6513ab43_2',1,'รถสกูตเตอร์รีโทร สไตล์คลาสสิก','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO014','Yamaha','Finn',2024,'กค1235','เขียว',125,380.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2024/finn-2024/lineup-360-green-ubs/2.png?sfvrsn=37bb5c14_2',1,'รถสกูตเตอร์เศรษฐกิจ ใช้งานง่าย','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO015','Yamaha','Grand Filano',2024,'กค1236','เทา',125,480.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2024/grand-filano-hybrid-connected-2024/lineup-360-titanium-gray/2.png?sfvrsn=d33825a6_2',1,'รถสกูตเตอร์ไฮบริดพรีเมียม','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO016','Yamaha','Aerox',2025,'กค1237','เงิน',155,700.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/all-new-aerox-2025/lineup-360-silver-star/2.png?sfvrsn=ba06ecb7_2',1,'รถสกูตเตอร์สปอร์ต สไตล์สปอร์ต','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO017','Yamaha','NMAX',2025,'กค1238','ดำ',155,750.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/all-new-nmax-2025/lineup-360-magma-black/2.png?sfvrsn=f4ef4f1a_2',1,'รถสกูตเตอร์พรีเมี่ยม','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO018','Yamaha','XMAX',2025,'กค1239','เทา',250,1500.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/xmax-2025/lineup-360-dark-gray/2.png?sfvrsn=e29fd942_2',1,'รถสกูตเตอร์ทัวริงขนาดใหญ่ สบายสุด','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO019','Kawasaki','Ninja 300',2021,'กจ3456','เขียว',300,1200.00,'../img/default-bike.jpg',0,'รถสปอร์ตขนาดใหญ่ กำลังซ่อมบำรุง','maintenance','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO020','Kawasaki','Z650',2022,'กจ3457','ดำ',650,1800.00,'../img/default-bike.jpg',1,'รถเนคคิดสตรีทฟิเกอร์ สไตล์ล้ำสมัย','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO021','Kawasaki','Versys 650',2023,'กจ3458','ส้ม',650,1600.00,'../img/default-bike.jpg',1,'รถผจญภัยอเนกประสงค์','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO022','Honda','CBR150R',2022,'กง9012','แดง',150,800.00,'../img/default-bike.jpg',1,'รถสปอร์ต สภาพดี','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO023','Yamaha','MT-15',2023,'กค1240','น้ำเงิน',150,850.00,'../img/default-bike.jpg',1,'รถเนคคิดสปอร์ต ขนาดกะทัดรัด','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO024','Kawasaki','Ninja 250',2022,'กจ3459','เขียว',250,1000.00,'../img/default-bike.jpg',1,'รถสปอร์ตสำหรับเริ่มต้น','ready','2025-11-17 03:01:44','2025-11-17 03:01:44'),
('MOTO025','Yamaha','NMAX',2024,'กค1241','ดำ',155,750.00,'https://storagetym.blob.core.windows.net/www2021/images/product-2021/commuter/model-year-2025/xmax-2025/lineup-360-dark-gray/2.png?sfvrsn=e29fd942_2',1,'รถสกูตเตอร์พรีเมียม','ready','2025-11-17 09:29:06','2025-11-17 09:29:06');

-- 5. เพิ่มข้อมูลการจอง (อัปเดต ID รถให้ตรงกับรุ่นในรายการใหม่แล้ว)
INSERT INTO reservations (reservation_id, customer_id, employee_id, motorcycle_id, start_date, end_date, total_days, total_price, status, deposit_amount, discount_amount, final_price, pickup_location, return_location) VALUES
-- MOTO008 คือ PCX160
('res001', 'cust001', 'emp001', 'MOTO008', '2024-01-15', '2024-01-17', 3, 1950.00, 'completed', 1000.00, 0.00, 1950.00, 'สาขาหลัก', 'สาขาหลัก'),
-- MOTO017 คือ NMAX
('res002', 'cust002', NULL, 'MOTO017', '2024-01-20', '2024-01-22', 3, 2250.00, 'confirmed', 1000.00, 100.00, 2150.00, 'สาขาหลัก', 'สาขาหลัก'),
-- MOTO022 คือ CBR150R
('res003', 'cust003', NULL, 'MOTO022', '2024-01-25', '2024-01-27', 3, 2400.00, 'pending', 1000.00, 0.00, 2400.00, 'สาขาหลัก', 'สาขาหลัก'),
-- MOTO008 คือ PCX160
('res004', 'cust004', NULL, 'MOTO008', '2025-11-20', '2025-11-22', 2, 1300.00, 'pending', 390.00, 0.00, 1300.00, 'สาขาหลัก', 'สาขาหลัก');

-- 6. เพิ่มข้อมูลการชำระเงิน
INSERT INTO payments (payment_id, reservation_id, amount, payment_method, payment_status, payment_date, transaction_id, slip_image_url) VALUES
('pay001', 'res001', 1950.00, 'cash', 'paid', '2024-01-14 10:30:00', 'CASH001', NULL),
('pay002', 'res002', 2150.00, 'bank_transfer', 'paid', '2024-01-19 14:20:00', 'BANK002', '/slips/slip002.jpg'),
('pay003', 'res003', 2400.00, 'qr_code', 'pending', NULL, NULL, NULL),
('pay004', 'res004', 1300.00, 'bank_transfer', 'pending', NULL, NULL, NULL);

-- 7. เพิ่มข้อมูลส่วนลด
INSERT INTO discounts (discount_id, discount_code, discount_type, discount_value, min_rental_days, max_discount_amount, start_date, end_date, is_active, created_by, usage_limit, used_count) VALUES
('disc001', 'WELCOME100', 'fixed', 100.00, 2, 100.00, '2024-01-01', '2024-12-31', 1, 'owner001', 100, 5),
('disc002', 'SUMMER10', 'percentage', 10.00, 3, 500.00, '2024-01-01', '2024-12-31', 1, 'owner001', 50, 12),
('disc003', 'NEWUSER50', 'fixed', 50.00, 1, 50.00, '2025-01-01', '2025-12-31', 1, 'owner002', 200, 8);

-- 8. เพิ่มข้อมูลการใช้ส่วนลด
INSERT INTO reservation_discounts (reservation_id, discount_id, applied_amount) VALUES
('res002', 'disc001', 100.00);