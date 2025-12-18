<?php
// services/AdminMotorcycleService.php
require_once __DIR__ . '/MotorcycleService.php';
require_once __DIR__ . '/../config/uuid.php';

class AdminMotorcycleService {
    public static function create($data) {
        $db = Database::connect();
        $id = gen_id('MOT', 10);
        $stmt = $db->prepare("
            INSERT INTO motorcycles (motorcycle_id, brand, model, year, license_plate, color, engine_cc, price_per_day, image_url, is_available, description, maintenance_status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $ok = $stmt->execute([
            $id,
            $data['brand'] ?? null,
            $data['model'] ?? null,
            $data['year'] ?? null,
            $data['licensePlate'] ?? null,
            $data['color'] ?? null,
            $data['engineCc'] ?? null,
            $data['pricePerDay'] ?? 0,
            $data['imageUrl'] ?? null,
            isset($data['isAvailable']) ? ($data['isAvailable'] ? 1 : 0) : 1,
            $data['description'] ?? null,
            $data['maintenanceStatus'] ?? 'ready'
        ]);
        return $ok ? $id : false;
    }

    public static function update($id, $data) {
        $db = Database::connect();
        $fields = [];
        $params = [];

        $map = [
            'brand' => 'brand',
            'model' => 'model',
            'year' => 'year',
            'licensePlate' => 'license_plate',
            'color' => 'color',
            'engineCc' => 'engine_cc',
            'pricePerDay' => 'price_per_day',
            'imageUrl' => 'image_url',
            'isAvailable' => 'is_available',
            'description' => 'description',
            'maintenanceStatus' => 'maintenance_status'
        ];
        foreach ($map as $k => $col) {
            if (isset($data[$k])) {
                $fields[] = "$col = ?";
                if ($k === 'isAvailable') $params[] = $data[$k] ? 1 : 0;
                else $params[] = $data[$k];
            }
        }
        if (empty($fields)) return false;
        $params[] = $id;
        $sql = "UPDATE motorcycles SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE motorcycle_id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete($id) {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM motorcycles WHERE motorcycle_id = ?");
        return $stmt->execute([$id]);
    }

    public static function updateStatus($id, $status) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE motorcycles SET maintenance_status = ?, is_available = ? WHERE motorcycle_id = ?");
        $isAvailable = ($status === 'ready') ? 1 : 0;
        return $stmt->execute([$status, $isAvailable, $id]);
    }
}
