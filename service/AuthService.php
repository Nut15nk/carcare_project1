<?php
require_once __DIR__ . '/../config/config.php';

class AuthService
{

    // --- ฟังก์ชัน Login (ของเดิม) ---
    public static function login($email, $password) {
        $db = Database::connect();

        $roles = [
            "customer" => "customers",
            "employee" => "employees",
            "owner"    => "owners",
        ];

        foreach ($roles as $role => $table) {
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                continue; 
            }

            if (!password_verify($password, $user['password_hash'])) {
                throw new Exception("รหัสผ่านไม่ถูกต้อง");
            }

            return [
                "id"        => $user[$role . "_id"],
                "email"     => $user["email"],
                "role"      => $role,
                "firstName" => $user["first_name"],
                "lastName"  => $user["last_name"],
            ];
        }

        throw new Exception("ไม่พบผู้ใช้งาน");
    }

    // --- ฟังก์ชัน Register (ส่วนที่เพิ่มใหม่) ---
    public static function register($data) {
        $db = Database::connect();

        // 1. เช็คก่อนว่าอีเมลซ้ำไหม? (เช็คในตาราง customers)
        $stmt = $db->prepare("SELECT customer_id FROM customers WHERE email = ? LIMIT 1");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return false; // อีเมลซ้ำ ส่งกลับเป็น false
        }

        // 2. สร้าง ID ใหม่ (ใช้ uniqid เพื่อให้ไม่ซ้ำ)
        $newId = "cust" . uniqid();

        // 3. เข้ารหัส Password
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        try {
            // 4. บันทึกลงฐานข้อมูล
            // หมายเหตุ: date_of_birth ใส่ NULL ไปก่อน เพราะในฟอร์มไม่มีให้กรอก
            $sql = "INSERT INTO customers (
                        customer_id, email, password_hash, first_name, last_name, 
                        phone, address,  date_of_birth, is_verified
                    ) VALUES (
                        :id, :email, :pass, :fname, :lname, 
                        :phone, :address, NULL, 0
                    )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id'       => $newId,
                ':email'    => $data['email'],
                ':pass'     => $passwordHash,
                ':fname'    => $data['firstName'],
                ':lname'    => $data['lastName'],
                ':phone'    => $data['phone'],
                ':address'  => $data['address'],
            ]);

            // 5. Return ข้อมูลกลับเพื่อให้ Auto Login ได้เลย
            return [
                "id"        => $newId,
                "email"     => $data['email'],
                "role"      => "customer",
                "firstName" => $data['firstName'],
                "lastName"  => $data['lastName']
            ];

        } catch (PDOException $e) {
            // ถ้ามี Error (เช่น Database เชื่อมต่อไม่ได้) ให้ Log ไว้
            error_log("Register Error: " . $e->getMessage());
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
        }
    }
}
?>