<?php
// service/PaymentService.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/uuid.php';

class PaymentService
{
    /**
     * สร้าง payment (เหมือน processPayment ใน Java)
     * - ตรวจ reservation
     * - กัน payment ซ้ำ
     * - ใช้ราคาจาก DB
     * - return payment object
     */
    public static function createPayment(array $data)
    {
        $db = Database::connect();

        // เปิด exception ให้เหมือน Spring
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. ตรวจ reservation
        $stmt = $db->prepare(
            "SELECT reservation_id, customer_id, final_price
             FROM reservations
             WHERE reservation_id = ?"
        );
        $stmt->execute([$data['reservationId']]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $reservation) {
            throw new Exception("ไม่พบข้อมูลการจอง");
        }

        // 2. กัน payment ซ้ำ (เหมือน repository.findByReservationId)
        $existingPayment = self::getPaymentByReservation($data['reservationId']);
        if ($existingPayment) {
            return $existingPayment;
        }

        // 3. validate payment method
        $allowedMethods = ['BANK_TRANSFER', 'PROMPTPAY'];
        if (! in_array($data['paymentMethod'], $allowedMethods)) {
            throw new Exception("Payment method ไม่ถูกต้อง");
        }

        // 4. create payment
        $paymentId = gen_id('PAY', 10);

        $stmt = $db->prepare("
            INSERT INTO payments
            (payment_id, reservation_id, amount, payment_method, payment_status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");

        $stmt->execute([
            $paymentId,
            $reservation['reservation_id'],
            $reservation['final_price'],
            $data['paymentMethod'],
        ]);

        // 5. return payment (เหมือน convertToResponse)
        return self::getPaymentByReservation($data['reservationId']);
    }

    /**
     * ดึง payment ล่าสุดของ reservation
     * (เหมือน findByReservationId)
     */
    public static function getPaymentByReservation(string $reservationId)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
        SELECT
            p.payment_id     AS paymentId,
            p.reservation_id AS reservationId,
            p.amount,
            p.payment_method AS paymentMethod,
            p.payment_status AS paymentStatus,
            p.payment_date   AS paymentDate,
            p.transaction_id AS transactionId,
            p.slip_image_url AS slipImageUrl,
            p.notes,
            p.created_at     AS createdAt
        FROM payments p
        WHERE p.reservation_id = ?
        LIMIT 1
    ");
        $stmt->execute([$reservationId]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * ดึง payment ทั้งหมดของ customer
     */
    public static function getCustomerPayments(string $customerId)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT *
            FROM payments
            WHERE customer_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$customerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * อัปเดตสถานะ payment (เหมือน updatePaymentStatus)
     */
    public static function updatePaymentStatus(string $paymentId, string $status)
    {
        $allowedStatus = ['PENDING', 'PAID', 'CANCELLED'];
        if (! in_array($status, $allowedStatus)) {
            throw new Exception("Payment status ไม่ถูกต้อง");
        }

        $db = Database::connect();

        $stmt = $db->prepare("
            UPDATE payments
            SET status = ?,
                payment_date = IF(? = 'PAID', NOW(), payment_date),
                updated_at = NOW()
            WHERE payment_id = ?
        ");

        $stmt->execute([$status, $status, $paymentId]);

        return self::getPaymentById($paymentId);
    }

    /**
     * confirm payment (admin)
     */
    public static function confirmPayment(string $paymentId, ?string $adminId = null)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            UPDATE payments
            SET status = 'PAID',
                confirmed_by = ?,
                confirmed_at = NOW(),
                payment_date = NOW(),
                updated_at = NOW()
            WHERE payment_id = ?
        ");

        return $stmt->execute([$adminId, $paymentId]);
    }

    /**
     * upload slip (เหมือน uploadPaymentSlip ใน Java)
     */
    public static function uploadPaymentSlip(string $paymentId, array $file)
    {
        if (! isset($file['tmp_name']) || ! is_uploaded_file($file['tmp_name'])) {
            throw new Exception("ไม่มีไฟล์อัปโหลด");
        }

        $uploadsDir = __DIR__ . '/../uploads/slips/';
        if (! is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $fileName   = time() . '_' . basename($file['name']);
        $targetPath = $uploadsDir . $fileName;

        if (! move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("ไม่สามารถอัปโหลดไฟล์ได้");
        }

        $urlPath = 'uploads/slips/' . $fileName;

        $db   = Database::connect();
        $stmt = $db->prepare("
            UPDATE payments
            SET slip_image_url = ?,
                updated_at = NOW()
            WHERE payment_id = ?
        ");
        $stmt->execute([$urlPath, $paymentId]);

        return $urlPath;
    }

    /**
     * ดึง payment ด้วย id
     */
    public static function getPaymentById(string $paymentId)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT *
            FROM payments
            WHERE payment_id = ?
            LIMIT 1
        ");
        $stmt->execute([$paymentId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ดึงบัญชีธนาคาร
     */
    public static function getBankAccounts()
    {
        $db = Database::connect();

        try {
            $stmt = $db->prepare("
            SELECT *
            FROM bank_accounts
            ORDER BY bank_name ASC
        ");
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // table ไม่มี → ไม่ต้องพังทั้งหน้า
            error_log("Bank account table missing: " . $e->getMessage());
            return [];
        }
    }

}
