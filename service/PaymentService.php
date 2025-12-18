<?php
// service/PaymentService.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/uuid.php';

class PaymentService {

    public static function createPayment($data) {
        $db = Database::connect();
        $paymentId = gen_id('PAY', 10);
        $stmt = $db->prepare("
            INSERT INTO payments
            (payment_id, reservation_id, customer_id, amount, payment_method, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'PENDING', NOW())
        ");
        $stmt->execute([
            $paymentId,
            $data['reservationId'],
            $data['customerId'],
            $data['amount'],
            $data['paymentMethod']
        ]);
        return $paymentId;
    }

    public static function getPaymentByReservation($reservationId) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM payments WHERE reservation_id=? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getCustomerPayments($customerId) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM payments WHERE customer_id=? ORDER BY created_at DESC");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function confirmPayment($paymentId, $adminId = null) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE payments SET status='PAID', confirmed_by=?, confirmed_at=NOW() WHERE payment_id=?");
        return $stmt->execute([$adminId, $paymentId]);
    }

    public static function uploadPaymentSlip($paymentId, $file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception("ไม่มีไฟล์อัปโหลด");
        }
        $uploadsDir = __DIR__ . '/../uploads/slips/';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $fileName = time().'_'.basename($file['name']);
        $targetPath = $uploadsDir . $fileName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("ไม่สามารถย้ายไฟล์ได้");
        }
        $urlPath = 'uploads/slips/' . $fileName;

        $db = Database::connect();
        $stmt = $db->prepare("UPDATE payments SET slip_image_url=?, updated_at=NOW() WHERE payment_id=?");
        $stmt->execute([$urlPath, $paymentId]);

        return $urlPath;
    }

    public static function getBankAccounts() {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM bank_accounts ORDER BY bank_name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
