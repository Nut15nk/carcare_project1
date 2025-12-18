<?php
require_once 'config.php';

class PaymentService {
    public static function createPayment($data) {
        $db = Database::connect();
        $stmt = $db->prepare("
            INSERT INTO payments
            (payment_id,reservation_id,customer_id,amount,payment_method,status,created_at)
            VALUES (?,?,?,?,?,'PENDING',NOW())
        ");
        $paymentId = "PAY_".uniqid();
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
        $stmt = $db->prepare("
            SELECT * FROM payments WHERE reservation_id=? ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getCustomerPayments($customerId) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM payments WHERE customer_id=? ORDER BY created_at DESC");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function confirmPayment($paymentId,$data) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE payments SET status=?, confirmed_by=?, confirmed_at=NOW() WHERE payment_id=?");
        return $stmt->execute([$data['status'],$data['adminId'],$paymentId]);
    }

    public static function uploadPaymentSlip($paymentId,$file) {
        $fileName=time().'_'.basename($file['name']);
        $path='uploads/slips/'.$fileName;
        if(!is_dir('uploads/slips')) mkdir('uploads/slips',0777,true);
        move_uploaded_file($file['tmp_name'],$path);
        $db=Database::connect();
        $stmt=$db->prepare("UPDATE payments SET slip=? WHERE payment_id=?");
        $stmt->execute([$path,$paymentId]);
        return $path;
    }
}
