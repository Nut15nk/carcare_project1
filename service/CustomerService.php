<?php
// services/CustomerService.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/uuid.php';

class CustomerService
{
    public static function create($data)
    {
        $db = Database::connect();
        $id = gen_id('CUST', 10);


        $stmt = $db->prepare("
            INSERT INTO customers (customer_id, email, password_hash, first_name, last_name, phone, address, date_of_birth, is_verified, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");

        $ok = $stmt->execute([
            $id,
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['firstName'] ?? null,
            $data['lastName'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['dateOfBirth'] ?? null

        ]);
        return $ok ? $id : false;
    }

    public static function findByEmail($email)
    {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function getById($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function updateProfile($id, $data)
    {
        $db = Database::connect();


        $stmt = $db->prepare("
            UPDATE customers SET first_name = ?, last_name = ?, phone = ?, address = ?, updated_at = NOW()
            WHERE customer_id = ?
        ");

        return $stmt->execute([
            $data['firstName'] ?? null,
            $data['lastName'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $id
        ]);
    }
}