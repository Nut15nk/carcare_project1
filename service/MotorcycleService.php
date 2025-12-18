<?php
require_once __DIR__ . '/../config/config.php';

class MotorcycleService {

    // ดึงรถทั้งหมด
    public static function getAllMotorcycles() {
        $db = Database::connect();
        $stmt = $db->query("SELECT * FROM motorcycles ORDER BY created_at DESC");
        $bikes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return self::mapFields($bikes);
    }

    // ดึงรถตาม ID
    public static function getMotorcycleById($id) {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM motorcycles WHERE motorcycle_id=?");
        $stmt->execute([$id]);
        $bike = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$bike) return null;
        return self::mapFields([$bike])[0];
    }

    // ดึงรถที่ว่างตามวันที่
    public static function getAvailableMotorcycles($startDate, $endDate) {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT * FROM motorcycles m
            WHERE m.maintenance_status='ready'
            AND m.motorcycle_id NOT IN (
                SELECT r.motorcycle_id FROM reservations r
                WHERE r.status IN ('pending','confirmed','approved','active')
                AND NOT (r.end_date < ? OR r.start_date > ?)
            )
        ");
        $stmt->execute([$startDate, $endDate]);
        $bikes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return self::mapFields($bikes, true);
    }

    // Search/filter motorcycles (brand, model, type, price)
    public static function searchMotorcycles($filters = [], $startDate = null, $endDate = null) {
        if ($startDate && $endDate) {
            $bikes = self::getAvailableMotorcycles($startDate, $endDate);
        } else {
            $bikes = self::getAllMotorcycles();
        }

        return array_filter($bikes, function($bike) use ($filters) {
            // Brand
            if (!empty($filters['brand']) && strcasecmp($bike['brand'], $filters['brand']) !== 0) return false;
            // Model
            if (!empty($filters['model']) && stripos($bike['model'], $filters['model']) === false) return false;
            // Min Price
            if (isset($filters['minPrice']) && $bike['pricePerDay'] < $filters['minPrice']) return false;
            // Max Price
            if (isset($filters['maxPrice']) && $bike['pricePerDay'] > $filters['maxPrice']) return false;
            // EngineCC / Type
            if (!empty($filters['type'])) {
                $cc = $bike['engineCc'] ?? 0;
                $typeMatch = false;
                if ($filters['type'] === 'small' && $cc <= 150) $typeMatch = true;
                if ($filters['type'] === 'medium' && $cc > 150 && $cc <= 300) $typeMatch = true;
                if ($filters['type'] === 'large' && $cc > 300) $typeMatch = true;
                if (!$typeMatch) return false;
            }
            return true;
        });
    }

    // map field ครบ
        private static function mapFields($bikes, $isAvailableDefault = null) {
        foreach ($bikes as &$bike) {
            $bike['motorcycleId']      = $bike['motorcycle_id'] ?? '';
            $bike['brand']             = $bike['brand'] ?? '';
            $bike['model']             = $bike['model'] ?? '';
            $bike['year']              = $bike['year'] ?? 'N/A';
            $bike['licensePlate']      = $bike['license_plate'] ?? '';
            $bike['color']             = $bike['color'] ?? '';
            $bike['engineCc']          = $bike['engine_cc'] ?? 0;
            $bike['pricePerDay']       = $bike['price_per_day'] ?? 0;
            $bike['imageUrl']          = $bike['image_url'] ?? '';
            $bike['description']       = $bike['description'] ?? 'รถจักรยานยนต์คุณภาพดี';
            $bike['maintenanceStatus'] = $bike['maintenance_status'] ?? '';
            $bike['isAvailable']       = $isAvailableDefault ?? ($bike['is_available'] ?? true);
        }
        return $bikes;
    }
}
