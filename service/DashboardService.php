<?php
// services/DashboardService.php
require_once __DIR__ . '/../config/config.php';

class DashboardService {
    public static function getAdminStats() {
        $db = Database::connect();
        $stats = [];

        $stats['totalReservations'] = (int)$db->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
        $stats['pendingReservations'] = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
        $stats['activeReservations'] = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status = 'active'")->fetchColumn();
        $stats['totalRevenue'] = $db->query("SELECT COALESCE(SUM(final_price),0) FROM reservations WHERE status IN ('confirmed','active','completed')")->fetchColumn();
        $stats['availableMotorcycles'] = (int)$db->query("SELECT COUNT(*) FROM motorcycles WHERE is_available = 1")->fetchColumn();
        $stats['totalCustomers'] = (int)$db->query("SELECT COUNT(*) FROM customers")->fetchColumn();

        return $stats;
    }

    public static function getRecentActivities($limit = 10) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT r.reservation_id, c.first_name, c.last_name, m.brand, m.model, r.status, r.created_at
            FROM reservations r
            JOIN customers c ON r.customer_id = c.customer_id
            JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
            ORDER BY r.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function getTopMotorcycles($limit = 5) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT m.brand, m.model, COUNT(r.reservation_id) as rentalCount
            FROM motorcycles m
            LEFT JOIN reservations r ON m.motorcycle_id = r.motorcycle_id
            GROUP BY m.brand, m.model
            ORDER BY rentalCount DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
