<?php
require_once 'config.php';

class AdminService {

    public static function getDashboardStats() {
        $db = Database::connect();
        $stats = [];

        $stats['totalBookings'] = $db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
        $stats['pendingBookings'] = $db->query("SELECT COUNT(*) FROM reservations WHERE status='PENDING'")->fetchColumn();
        $stats['activeBookings'] = $db->query("SELECT COUNT(*) FROM reservations WHERE status='ACTIVE'")->fetchColumn();
        $stats['totalRevenue'] = $db->query("SELECT SUM(amount) FROM payments WHERE status='CONFIRMED'")->fetchColumn();
        $stats['availableMotorcycles'] = $db->query("SELECT COUNT(*) FROM motorcycles WHERE status='AVAILABLE'")->fetchColumn();

        return $stats;
    }

    public static function getAllReservations() {
        $db = Database::connect();
        $stmt = $db->query("
            SELECT r.*, u.first_name, u.last_name, m.brand, m.model 
            FROM reservations r
            INNER JOIN users u ON r.customer_id = u.id
            INNER JOIN motorcycles m ON r.motorcycle_id = m.id
            ORDER BY r.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllCustomers() {
        $db = Database::connect();
        $stmt = $db->query("SELECT id, first_name, last_name, email, phone, role FROM users WHERE role='CUSTOMER'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllEmployees() {
        $db = Database::connect();
        $stmt = $db->query("SELECT id, first_name, last_name, email, phone, role FROM users WHERE role='EMPLOYEE'");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateReservationStatus($reservationId, $status) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $reservationId]);
    }

    public static function getRevenueReport($period) {
        $db = Database::connect();
        $sql = "";

        if ($period === 'daily') {
            $sql = "SELECT DATE(created_at) AS date, SUM(amount) AS total FROM payments WHERE status='CONFIRMED' GROUP BY DATE(created_at)";
        } elseif ($period === 'yearly') {
            $sql = "SELECT YEAR(created_at) AS date, SUM(amount) AS total FROM payments WHERE status='CONFIRMED' GROUP BY YEAR(created_at)";
        } else {
            $sql = "SELECT DATE_FORMAT(created_at,'%Y-%m') AS date, SUM(amount) AS total FROM payments WHERE status='CONFIRMED' GROUP BY DATE_FORMAT(created_at,'%Y-%m')";
        }

        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getRecentActivities($limit) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT 'booking' AS type, id, created_at FROM reservations
            UNION
            SELECT 'payment' AS type, id, created_at FROM payments
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createEmployee($data) {
        $db = Database::connect();
        $stmt = $db->prepare("
            INSERT INTO users (first_name, last_name, email, password, phone, role)
            VALUES (?, ?, ?, ?, ?, 'EMPLOYEE')
        ");
        return $stmt->execute([
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['phone']
        ]);
    }

    public static function updateEmployee($id, $data) {
        $db = Database::connect();
        $stmt = $db->prepare("
            UPDATE users SET first_name=?, last_name=?, phone=? WHERE id=?
        ");
        return $stmt->execute([
            $data['firstName'],
            $data['lastName'],
            $data['phone'],
            $id
        ]);
    }

    public static function deleteEmployee($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM users WHERE id=? AND role='EMPLOYEE'");
        return $stmt->execute([$id]);
    }

    public static function updatePaymentStatus($reservationId, $status) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE payments SET status=? WHERE reservation_id=?");
        return $stmt->execute([$status, $reservationId]);
    }

    public static function getAllMotorcycles() {
        $db = Database::connect();
        return $db->query("SELECT * FROM motorcycles")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createMotorcycle($data) {
        $db = Database::connect();
        $stmt = $db->prepare("
            INSERT INTO motorcycles (brand, model, price_per_day, status, image)
            VALUES (?, ?, ?, 'AVAILABLE', ?)
        ");
        return $stmt->execute([
            $data['brand'],
            $data['model'],
            $data['pricePerDay'],
            $data['image']
        ]);
    }

    public static function updateMotorcycle($id, $data) {
        $db = Database::connect();
        $stmt = $db->prepare("
            UPDATE motorcycles SET brand=?, model=?, price_per_day=?, status=? WHERE id=?
        ");
        return $stmt->execute([
            $data['brand'],
            $data['model'],
            $data['pricePerDay'],
            $data['status'],
            $id
        ]);
    }

    public static function deleteMotorcycle($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM motorcycles WHERE id=?");
        return $stmt->execute([$id]);
    }

    public static function getAllDiscounts() {
        $db = Database::connect();
        return $db->query("SELECT * FROM discounts")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createDiscount($data) {
        $db = Database::connect();
        $stmt = $db->prepare("
            INSERT INTO discounts (code, percent, active)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([
            $data['code'],
            $data['percent'],
            $data['active']
        ]);
    }

    public static function updateDiscount($id, $data) {
        $db = Database::connect();
        $stmt = $db->prepare("
            UPDATE discounts SET code=?, percent=?, active=? WHERE id=?
        ");
        return $stmt->execute([
            $data['code'],
            $data['percent'],
            $data['active'],
            $id
        ]);
    }

    public static function deleteDiscount($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM discounts WHERE id=?");
        return $stmt->execute([$id]);
    }

    public static function getPaymentDetails($reservationId) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM payments WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getCustomerDetails($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function updateCustomerStatus($id, $status) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE users SET active=? WHERE id=? AND role='CUSTOMER'");
        return $stmt->execute([$status, $id]);
    }

    public static function getReservationDetails($id) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT r.*, m.brand, m.model, u.first_name, u.last_name
            FROM reservations r
            INNER JOIN motorcycles m ON r.motorcycle_id = m.id
            INNER JOIN users u ON r.customer_id = u.id
            WHERE r.id=?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getMotorcycleDetails($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM motorcycles WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function updateMotorcycleStatus($id, $data) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE motorcycles SET status=? WHERE id=?");
        return $stmt->execute([$data['status'], $id]);
    }

    public static function getSystemAnalytics($period) {
        $db = Database::connect();
        $sql = "
            SELECT DATE(created_at) AS date,
            COUNT(*) AS bookings,
            (SELECT COUNT(*) FROM payments WHERE DATE(payments.created_at)=DATE(reservations.created_at)) AS payments
            FROM reservations
            GROUP BY DATE(created_at)
        ";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function exportReports($type, $format) {
        $db = Database::connect();

        if ($type === 'payments') {
            $stmt = $db->query("SELECT * FROM payments");
        } else {
            $stmt = $db->query("SELECT * FROM reservations");
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
