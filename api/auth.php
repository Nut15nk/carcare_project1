<?php
require_once 'config.php';

class AuthService {
    public static function login($email, $password) {
        $db = Database::connect();
        $roles = ["customer"=>"customers","employee"=>"employees","owner"=>"owners"];

        foreach($roles as $role => $table) {
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) continue;

            if (!password_verify($password,$user['password_hash'])) {
                throw new Exception("รหัสผ่านไม่ถูกต้อง");
            }

            $_SESSION['user'] = [
                'id'=>$user[$role."_id"],
                'email'=>$user['email'],
                'role'=>strtoupper($role),
                'firstName'=>$user['first_name'] ?? '',
                'lastName'=>$user['last_name'] ?? '',
                'phone'=>$user['phone'] ?? ''
            ];

            return $_SESSION['user'];
        }
        throw new Exception("ไม่พบผู้ใช้งาน");
    }

    public static function registerCustomer($data) {
        $db = Database::connect();
        $tables = ["customers","employees","owners"];
        foreach($tables as $t) {
            $check = $db->prepare("SELECT email FROM {$t} WHERE email = ? LIMIT 1");
            $check->execute([$data['email']]);
            if ($check->fetch()) throw new Exception("อีเมลนี้ถูกใช้งานแล้ว");
        }

        $stmt = $db->prepare("
            INSERT INTO customers
            (customer_id,email,password_hash,first_name,last_name,phone,created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $customerId = "CUS_".uniqid();
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt->execute([
            $customerId,
            $data['email'],
            $passwordHash,
            $data['firstName'] ?? '',
            $data['lastName'] ?? '',
            $data['phone'] ?? ''
        ]);

        return true;
    }

    public static function isLoggedIn() { return isset($_SESSION['user']); }
    public static function getUserId() { return $_SESSION['user']['id'] ?? null; }
    public static function getUserData() { return $_SESSION['user'] ?? null; }
    public static function logout() { unset($_SESSION['user']); session_destroy(); }
}
