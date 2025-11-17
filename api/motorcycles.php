<?php
require_once 'config.php';

class MotorcycleService {
    public static function getAllMotorcycles() {
        try {
            $response = ApiConfig::makeApiCall('/motorcycles');
            
            if ($response['status'] === 200) {
                return $response['data']['data'] ?? [];
            } else {
                error_log("API Error: " . $response['status']);
                return [];
            }
        } catch (Exception $e) {
            error_log("MotorcycleService Error: " . $e->getMessage());
            return [];
        }
    }
    
    public static function getMotorcycleById($id) {
        try {
            $response = ApiConfig::makeApiCall("/motorcycles/{$id}");
            return $response['status'] === 200 ? $response['data']['data'] : null;
        } catch (Exception $e) {
            error_log("MotorcycleService Error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function searchMotorcycles($filters) {
        try {
            $response = ApiConfig::makeApiCall('/motorcycles/search', 'POST', $filters);
            return $response['status'] === 200 ? $response['data']['data'] : [];
        } catch (Exception $e) {
            error_log("MotorcycleService Error: " . $e->getMessage());
            return [];
        }
    }
    
    public static function getAvailableMotorcycles($startDate, $endDate) {
        try {
            $params = http_build_query([
                'startDate' => $startDate,
                'endDate' => $endDate
            ]);
            
            $response = ApiConfig::makeApiCall("/motorcycles/available?{$params}");
            return $response['status'] === 200 ? $response['data']['data'] : [];
        } catch (Exception $e) {
            error_log("MotorcycleService Error: " . $e->getMessage());
            return [];
        }
    }
}
?>