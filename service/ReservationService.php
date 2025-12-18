<?php
// services/ReservationService.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/uuid.php';

class ReservationService {
    public static function create($data) {
        $db = Database::connect();

        // validation basic (motorcycle available?)
        $motor = $db->prepare("SELECT * FROM motorcycles WHERE motorcycle_id = ?");
        $motor->execute([$data['motorcycleId']]);
        $motor = $motor->fetch();
        if (!$motor) throw new Exception("Motorcycle not found");

        // check overlapping reservations
        $stmtOverlap = $db->prepare("
            SELECT 1 FROM reservations
            WHERE motorcycle_id = ?
            AND status IN ('pending','confirmed','approved','active')
            AND NOT (end_date < ? OR start_date > ?)
            LIMIT 1
        ");
        $stmtOverlap->execute([$data['motorcycleId'], $data['startDate'], $data['endDate']]);
        if ($stmtOverlap->fetch()) {
            throw new Exception("รถไม่ว่างในช่วงวันที่ที่เลือก");
        }

        // calculate days and price (include both dates)
        $start = new DateTime($data['startDate']);
        $end = new DateTime($data['endDate']);
        $interval = $start->diff($end);
        $days = (int)$interval->format('%a') + 1;
        if ($days <= 0) $days = 1;
        $pricePerDay = $motor['price_per_day'];
        $totalPrice = $pricePerDay * $days;

        // apply discount if provided (simple percent or discount table)
        $discountAmount = 0.0;
        if (!empty($data['discountCode'])) {
            // lookup code in discounts
            $ds = $db->prepare("SELECT * FROM discounts WHERE discount_code = ? AND is_active = 1 AND start_date <= ? AND end_date >= ?");
            $today = date('Y-m-d');
            $ds->execute([$data['discountCode'], $today, $today]);
            $row = $ds->fetch();
            if ($row) {
                if ($row['discount_type'] === 'PERCENT') {
                    $discountAmount = $totalPrice * ($row['discount_value'] / 100.0);
                } else {
                    $discountAmount = floatval($row['discount_value']);
                }
                // enforce max discount
                if ($row['max_discount_amount'] !== null) {
                    $discountAmount = min($discountAmount, $row['max_discount_amount']);
                }
            }
        }

        $finalPrice = $totalPrice - $discountAmount;
        if ($finalPrice < 0) $finalPrice = 0;

        $id = gen_id('RES', 10);
        $stmt = $db->prepare("
            INSERT INTO reservations
            (reservation_id, customer_id, employee_id, motorcycle_id, start_date, end_date, total_days, total_price, status, deposit_amount, discount_amount, final_price, pickup_location, return_location, special_requests, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?, NOW())
        ");
        $deposit = round($finalPrice * 0.3, 2);
        $ok = $stmt->execute([
            $id,
            $data['customerId'],
            $data['employeeId'] ?? null,
            $data['motorcycleId'],
            $data['startDate'],
            $data['endDate'],
            $days,
            $totalPrice,
            $deposit,
            $discountAmount,
            $finalPrice,
            $data['pickupLocation'] ?? null,
            $data['returnLocation'] ?? null,
            $data['specialRequests'] ?? null
        ]);
        return $ok ? $id : false;
    }

    public static function getByCustomer($customerId) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT r.*, m.brand, m.model, m.image_url
            FROM reservations r
            JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            WHERE r.customer_id = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    public static function getById($id) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT r.*, m.brand, m.model, m.image_url, c.first_name, c.last_name, c.phone
            FROM reservations r
            JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            LEFT JOIN customers c ON r.customer_id = c.customer_id
            WHERE r.reservation_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function updateStatus($id, $status) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE reservations SET status = ?, updated_at = NOW() WHERE reservation_id = ?");
        return $stmt->execute([$status, $id]);
    }

    public static function cancel($id) {
        return self::updateStatus($id, 'cancelled');
    }

    public static function calculatePriceSimple($startDate, $endDate, $pricePerDay) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $days = (int)$start->diff($end)->format('%a') + 1;
        if ($days <= 0) $days = 1;
        $total = $pricePerDay * $days;
        // sample discount rule: 50 per every 3 days
        $discount = floor($days / 3) * 50;
        return [
            'days' => $days,
            'totalPrice' => $total,
            'discount' => $discount,
            'finalPrice' => max(0, $total - $discount)
        ];
    }
}
