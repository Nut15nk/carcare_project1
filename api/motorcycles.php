<?php
require_once 'config.php';

class MotorcycleService {
    public static function getAllMotorcycles() {
        $response = ApiConfig::makeApiCall('/motorcycles');
        return $response['status'] === 200 ? $response['data']['data'] : [];
    }
    
    public static function getMotorcycleById($id) {
        $response = ApiConfig::makeApiCall("/motorcycles/{$id}");
        return $response['status'] === 200 ? $response['data']['data'] : null;
    }
    
    public static function searchMotorcycles($filters) {
        $response = ApiConfig::makeApiCall('/motorcycles/search', 'POST', $filters);
        return $response['status'] === 200 ? $response['data']['data'] : [];
    }
    
    public static function getAvailableMotorcycles($startDate, $endDate) {
        $params = http_build_query([
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
        
        $response = ApiConfig::makeApiCall("/motorcycles/available?{$params}");
        return $response['status'] === 200 ? $response['data']['data'] : [];
    }
}
?>