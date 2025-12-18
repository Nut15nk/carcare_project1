<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/uuid.php';

class BookingService
{
    /**
     * สร้างการจองใหม่
     */
    public static function createBooking($data)
    {
        $db            = Database::connect();
        $reservationId = gen_id('RES', 10);
        $depositAmount = $data['depositAmount'] ?? round($data['totalPrice'] * 0.2, 2);

        $sql = "
            INSERT INTO reservations
            (reservation_id, customer_id, motorcycle_id, start_date, end_date, total_days, total_price,
            deposit_amount, discount_amount, final_price, pickup_location, return_location, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW())
        ";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $reservationId,
                $data['customerId'],
                $data['motorcycleId'],
                $data['startDate'],
                $data['endDate'],
                $data['totalDays'],
                $data['totalPrice'],
                $depositAmount,
                $data['discountAmount'] ?? 0,
                $data['finalPrice'],
                $data['pickupLocation'],
                $data['returnLocation'],
            ]);
            return $reservationId;
        } catch (PDOException $e) {
            error_log("BookingService Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ดึงการจองทั้งหมดของลูกค้า
     */
    public static function getCustomerBookings($customerId)
    {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT 
                r.reservation_id as reservationId,
                r.customer_id as customerId,
                r.motorcycle_id as motorcycleId,
                r.start_date as startDate,
                r.end_date as endDate,
                r.total_days as totalDays,
                r.total_price as totalPrice,
                r.deposit_amount as depositAmount,
                r.discount_amount as discountAmount,
                r.final_price as finalPrice,
                r.pickup_location as pickupLocation,
                r.return_location as returnLocation,
                r.status,
                r.created_at as createdAt,
                r.updated_at as updatedAt,
                m.brand,
                m.model,
                m.price_per_day as pricePerDay,
                m.engine_cc as engineCc,
                m.image_url as imageUrl
            FROM reservations r
            LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.customer_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * ดึงข้อมูลการจองตาม reservationId
     */
    public static function getBookingById($reservationId)
    {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT 
                r.reservation_id as reservationId,
                r.customer_id as customerId,
                r.motorcycle_id as motorcycleId,
                r.start_date as startDate,
                r.end_date as endDate,
                r.total_days as totalDays,
                r.total_price as totalPrice,
                r.deposit_amount as depositAmount,
                r.discount_amount as discountAmount,
                r.final_price as finalPrice,
                r.pickup_location as pickupLocation,
                r.return_location as returnLocation,
                r.status,
                r.created_at as createdAt,
                r.updated_at as updatedAt,
                m.brand,
                m.model,
                m.price_per_day as pricePerDay,
                m.engine_cc as engineCc,
                m.image_url as imageUrl
            FROM reservations r
            LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.reservation_id=?
        ");
        $stmt->execute([$reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ดึงการจองล่าสุดของลูกค้า
     */
    public static function getLatestCustomerBooking($customerId)
    {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT 
                r.reservation_id as reservationId,
                r.customer_id as customerId,
                r.motorcycle_id as motorcycleId,
                r.start_date as startDate,
                r.end_date as endDate,
                r.total_days as totalDays,
                r.total_price as totalPrice,
                r.deposit_amount as depositAmount,
                r.discount_amount as discountAmount,
                r.final_price as finalPrice,
                r.pickup_location as pickupLocation,
                r.return_location as returnLocation,
                r.status,
                r.created_at as createdAt,
                r.updated_at as updatedAt,
                m.brand,
                m.model,
                m.price_per_day as pricePerDay,
                m.engine_cc as engineCc,
                m.image_url as imageUrl
            FROM reservations r
            LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.customer_id = ?
            ORDER BY r.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * อัพเดตสถานะการจอง
     */
    public static function updateBookingStatus($reservationId, $status)
    {
        $db   = Database::connect();
        $stmt = $db->prepare("UPDATE reservations SET status=? WHERE reservation_id=?");
        return $stmt->execute([$status, $reservationId]);
    }

    /**
     * ยกเลิกการจอง
     */
    public static function cancelBooking($reservationId)
    {
        return self::updateBookingStatus($reservationId, 'CANCELLED');
    }
}
