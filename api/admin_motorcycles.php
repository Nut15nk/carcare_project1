<?php
// api/admin_motorcycles.php
require_once 'config.php';

class AdminMotorcycleService {
    
    /**
     * Get all motorcycles for admin (with more details)
     */
    public static function getAllMotorcycles() {
        try {
            $headers = [
                'Authorization: Bearer ' . ($_SESSION['user']['token'] ?? ''),
                'Content-Type: application/json'
            ];
            
            $response = ApiConfig::makeApiCall('/admin/motorcycles', 'GET', null, $headers);
            
            if ($response['status'] === 200) {
                return $response['data']['data'] ?? [];
            } else {
                error_log("AdminMotorcycleService API Error: " . $response['status']);
                return [];
            }
        } catch (Exception $e) {
            error_log("AdminMotorcycleService Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create new motorcycle
     */
    public static function createMotorcycle($motorcycleData) {
        try {
            $headers = [
                'Authorization: Bearer ' . ($_SESSION['user']['token'] ?? ''),
                'Content-Type: application/json'
            ];
            
            $response = ApiConfig::makeApiCall('/admin/motorcycles', 'POST', $motorcycleData, $headers);
            
            return $response['status'] === 201 || $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminMotorcycleService createMotorcycle Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update motorcycle
     */
    public static function updateMotorcycle($motorcycleId, $motorcycleData) {
        try {
            $headers = [
                'Authorization: Bearer ' . ($_SESSION['user']['token'] ?? ''),
                'Content-Type: application/json'
            ];
            
            $response = ApiConfig::makeApiCall("/admin/motorcycles/{$motorcycleId}", 'PUT', $motorcycleData, $headers);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminMotorcycleService updateMotorcycle Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete motorcycle
     */
    public static function deleteMotorcycle($motorcycleId) {
        try {
            $headers = [
                'Authorization: Bearer ' . ($_SESSION['user']['token'] ?? ''),
                'Content-Type: application/json'
            ];
            
            $response = ApiConfig::makeApiCall("/admin/motorcycles/{$motorcycleId}", 'DELETE', null, $headers);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminMotorcycleService deleteMotorcycle Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update motorcycle status
     */
    public static function updateMotorcycleStatus($motorcycleId, $statusData) {
        try {
            $headers = [
                'Authorization: Bearer ' . ($_SESSION['user']['token'] ?? ''),
                'Content-Type: application/json'
            ];
            
            $response = ApiConfig::makeApiCall("/admin/motorcycles/{$motorcycleId}/status", 'PUT', $statusData, $headers);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminMotorcycleService updateMotorcycleStatus Error: " . $e->getMessage());
            return false;
        }
    }
}
?>