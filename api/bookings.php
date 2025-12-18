<?php
require_once 'config.php';

class BookingService {
    public static function getUserById($userId) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT customer_id as id, first_name, last_name, email, phone FROM customers WHERE customer_id=?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function createBooking($data) {
        $db = Database::connect();
        $stmt = $db->prepare("
            INSERT INTO reservations
            (reservation_id,customer_id,motorcycle_id,start_date,end_date,total_price,status,created_at)
            VALUES (?,?,?,?,?,?, 'PENDING', NOW())
        ");
        $reservationId = "RES_".uniqid();
        $stmt->execute([
            $reservationId,
            $data['customerId'],
            $data['motorcycleId'],
            $data['startDate'],
            $data['endDate'],
            $data['totalPrice']
        ]);
        return $reservationId;
    }

    public static function getCustomerBookings($customerId) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT r.*, m.brand, m.model, m.price_per_day as pricePerDay, m.engine_cc as engineCc, m.image_url as imageUrl
            FROM reservations r
            LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.customer_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getBookingById($reservationId) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT r.*, m.brand, m.model, m.price_per_day as pricePerDay, m.engine_cc as engineCc, m.image_url as imageUrl
            FROM reservations r
            LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.reservation_id=?
        ");
        $stmt->execute([$reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function updateBookingStatus($reservationId,$status) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE reservations SET status=? WHERE reservation_id=?");
        return $stmt->execute([$status,$reservationId]);
    }

    public static function cancelBooking($reservationId) {
        return self::updateBookingStatus($reservationId,'CANCELLED');
    }
}
