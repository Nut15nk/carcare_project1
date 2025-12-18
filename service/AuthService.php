<?php
require_once __DIR__ . '/../config/config.php';

class AuthService {

    public static function login($email, $password) {
        $db = Database::connect();

        // ลำดับการตรวจสอบ (ตามที่คุณกำหนด)
        $roles = [
            "customer" => "customers",
            "employee" => "employees",
            "owner"    => "owners"
        ];

        foreach ($roles as $role => $table) {

            // หา user จาก table นี้ก่อน
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                continue; // ข้ามไป table ถัดไป
            }

            // เจอ email แล้ว → ตรวจรหัสผ่าน
            if (!password_verify($password, $user['password_hash'])) {
                throw new Exception("รหัสผ่านไม่ถูกต้อง");
            }

            // ตรวจสอบผ่าน → ส่งข้อมูลกลับ
            return [
                "id"        => $user[$role . "_id"],
                "email"     => $user["email"],
                "role"      => $role,
                "firstName" => $user["first_name"],
                "lastName"  => $user["last_name"],
            ];
        }

        // ถ้าเช็คครบทุก table แล้วยังไม่พบ email → ไม่พบผู้ใช้
        throw new Exception("ไม่พบผู้ใช้งาน");
    }
}
