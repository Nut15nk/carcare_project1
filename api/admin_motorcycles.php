<?php
require_once 'config.php';

class AdminMotorcycleService {

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
            UPDATE motorcycles
            SET brand = ?, model = ?, price_per_day = ?, status = ?
            WHERE id = ?
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
        $stmt = $db->prepare("DELETE FROM motorcycles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function updateMotorcycleStatus($id, $data) {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE motorcycles SET status=? WHERE id=?");
        return $stmt->execute([$data['status'], $id]);
    }
}
