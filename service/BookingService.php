<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/uuid.php';

class BookingService
{
    private static function db()
    {
        return Database::connect();
    }

    /**
     * สร้างการจองใหม่
     */
    public static function createBooking(array $data)
    {
        $db = self::db();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        try {
            $db->beginTransaction();

            $reservationId = gen_id('RES', 10);
            $depositAmount = $data['depositAmount'] ?? 500; // มัดจำคงที่ 500 บาท
            $shopDiscount  = self::calculateShopDiscount($data['totalDays']);
            $promoDiscount = $data['discountAmount'] ?? 0;

            $totalDiscount = $shopDiscount + $promoDiscount;
            $finalPrice    = max($data['totalPrice'] - $totalDiscount, 0);

            $stmt = $db->prepare("
                INSERT INTO reservations (
                    reservation_id,
                    customer_id,
                    motorcycle_id,
                    start_datetime,
                    end_datetime,
                    total_days,
                    total_price,
                    status,
                    deposit_amount,
                    discount_amount,
                    final_price,
                    pickup_location,
                    return_location,
                    pickup_details,
                    return_details,
                    created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW()
                )
            ");

            $stmt->execute([
                $reservationId,
                $data['customerId'],
                $data['motorcycleId'],
                $data['startDate'],
                $data['endDate'],
                $data['totalDays'],
                $data['totalPrice'],
                $depositAmount,
                $totalDiscount,
                $finalPrice,
                $data['pickupLocation'],
                $data['returnLocation'],
                $data['pickupDetails'] ?? null,
                $data['returnDetails'] ?? null,
            ]);

            // ถ้ามีการใช้ส่วนลด
            if (! empty($data['discountCode']) && $data['discountAmount'] > 0) {
                $discountInfo = DiscountService::validateDiscount(
                    $data['discountCode'],
                    $data['totalDays'],
                    $data['totalPrice']
                );

                if ($discountInfo['valid']) {
                    DiscountService::recordDiscountUsage(
                        $reservationId,
                        $discountInfo['discountId'],
                        $data['discountAmount']
                    );
                }
            }

            $db->commit();
            return $reservationId;

        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('BookingService::createBooking ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * ดึงการจองทั้งหมดของลูกค้า
     */
    public static function getCustomerBookings($customerId)
    {
        $db = self::db();

        $stmt = $db->prepare("
            SELECT
                r.reservation_id AS reservationId,
                r.customer_id    AS customerId,
                r.motorcycle_id  AS motorcycleId,
                r.start_datetime AS startDate,
                r.end_datetime   AS endDate,
                r.total_days     AS totalDays,
                r.total_price    AS totalPrice,
                r.deposit_amount AS depositAmount,
                r.discount_amount AS discountAmount,
                r.final_price    AS finalPrice,
                r.pickup_location AS pickupLocation,
                r.return_location AS returnLocation,
                r.status,
                r.created_at     AS createdAt,
                r.updated_at     AS updatedAt,

                m.brand,
                m.model,
                m.price_per_day  AS pricePerDay,
                m.engine_cc      AS engineCc,
                m.image_url      AS imageUrl
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
        $db = self::db();

        $stmt = $db->prepare("
            SELECT
                r.reservation_id AS reservationId,
                r.customer_id    AS customerId,
                r.motorcycle_id  AS motorcycleId,
                r.start_datetime AS startDate,
                r.end_datetime   AS endDate,
                r.total_days     AS totalDays,
                r.total_price    AS totalPrice,
                r.deposit_amount AS depositAmount,
                r.discount_amount AS discountAmount,
                r.final_price    AS finalPrice,
                r.pickup_location AS pickupLocation,
                r.return_location AS returnLocation,
                r.status,
                r.created_at     AS createdAt,
                r.updated_at     AS updatedAt,

                m.brand,
                m.model,
                m.price_per_day  AS pricePerDay,
                m.engine_cc      AS engineCc,
                m.image_url      AS imageUrl
            FROM reservations r
            LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.reservation_id = ?
            LIMIT 1
        ");

        $stmt->execute([$reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ดึงการจองล่าสุดของลูกค้า
     */
    public static function getLatestCustomerBooking($customerId)
    {
        $db = self::db();

        $stmt = $db->prepare("
            SELECT
                r.reservation_id AS reservationId,
                r.start_datetime AS startDate,
                r.end_datetime   AS endDate,
                r.total_days     AS totalDays,
                r.final_price    AS finalPrice,
                r.status,
                r.created_at     AS createdAt,

                m.brand,
                m.model,
                m.price_per_day  AS pricePerDay,
                m.engine_cc      AS engineCc,
                m.image_url      AS imageUrl
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
     * อัปเดตสถานะการจอง
     */
    public static function updateBookingStatus($reservationId, $status)
    {
        $db   = self::db();
        $stmt = $db->prepare("
            UPDATE reservations
            SET status = ?
            WHERE reservation_id = ?
        ");
        return $stmt->execute([$status, $reservationId]);
    }

    /**
     * ยกเลิกการจอง
     */
    public static function cancelBooking($reservationId)
    {
        return self::updateBookingStatus($reservationId, 'cancelled');
    }

    /**
     * ใช้เช็คช่วงวันที่ (หน้าโชว์รถ)
     */
    public static function getBookingsByDateRange(string $startDate, string $endDate): array
    {
        $db = self::db();

        $stmt = $db->prepare("
            SELECT
                reservation_id,
                motorcycle_id AS motorcycleId,
                status
            FROM reservations
            WHERE NOT (
                end_datetime < :start
                OR start_datetime > :end
            )
        ");

        $stmt->execute([
            ':start' => $startDate . ' 00:00:00',
            ':end'   => $endDate . ' 23:59:59',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    private static function calculateShopDiscount(int $totalDays): float
    {
        $group = intdiv($totalDays, 3); // ทุก 3 วัน
        return $group * 50;
    }

}
