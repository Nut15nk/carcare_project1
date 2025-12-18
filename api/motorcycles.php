<?php
require_once 'config.php';

class MotorcycleService {
    public static function getAllMotorcycles() {
        $db=Database::connect();
        $stmt=$db->query("SELECT * FROM motorcycles ORDER BY created_at DESC");
        $bikes=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return self::addAvailabilityFlag($bikes);
    }

    public static function getMotorcycleById($id) {
        $db=Database::connect();
        $stmt=$db->prepare("SELECT * FROM motorcycles WHERE motorcycle_id=?");
        $stmt->execute([$id]);
        $bike=$stmt->fetch(PDO::FETCH_ASSOC);
        return self::addAvailabilityFlag([$bike])[0]??null;
    }

    public static function getAvailableMotorcycles($startDate,$endDate) {
        $db=Database::connect();
        $stmt=$db->prepare("
            SELECT * FROM motorcycles
            WHERE motorcycle_id NOT IN (
                SELECT motorcycle_id FROM reservations
                WHERE NOT (end_date < ? OR start_date > ?)
            )
        ");
        $stmt->execute([$startDate,$endDate]);
        $bikes=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return self::addAvailabilityFlag($bikes,true);
    }

    private static function addAvailabilityFlag($bikes,$available=null) {
        foreach($bikes as &$bike) {
            $bike['isAvailable']=$available??true;
        }
        return $bikes;
    }
}
