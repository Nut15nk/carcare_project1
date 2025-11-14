<?php
/**
 * Motorcycles API
 * Handles all motorcycle-related API calls
 */

require_once __DIR__ . '/config.php';

class MotorcyclesAPI {
    
    /**
     * Get all motorcycles
     */
    public static function getAll($filters = []) {
        // For now, return mock data (from session)
        if (isset($_SESSION['motorcycles'])) {
            return [
                'success' => true,
                'data' => $_SESSION['motorcycles']
            ];
        }
        
        return [
            'success' => false,
            'error' => 'No motorcycles found',
            'data' => []
        ];
    }
    
    /**
     * Get motorcycle by ID
     */
    public static function getById($id) {
        if (isset($_SESSION['motorcycles'])) {
            foreach ($_SESSION['motorcycles'] as $motorcycle) {
                if ($motorcycle['id'] == $id) {
                    return [
                        'success' => true,
                        'data' => $motorcycle
                    ];
                }
            }
        }
        
        return [
            'success' => false,
            'error' => 'Motorcycle not found'
        ];
    }
    
    /**
     * Get available motorcycles for date range
     */
    public static function getAvailable($startDate, $endDate) {
        $available = [];
        
        if (isset($_SESSION['motorcycles'])) {
            foreach ($_SESSION['motorcycles'] as $motorcycle) {
                if ($motorcycle['status'] === 'available') {
                    $available[] = $motorcycle;
                }
            }
        }
        
        return [
            'success' => true,
            'data' => $available
        ];
    }
    
    /**
     * Get motorcycle availability status
     */
    public static function checkAvailability($motorcycleId, $startDate, $endDate) {
        $motorcycle = self::getById($motorcycleId);
        
        if (!$motorcycle['success']) {
            return ['success' => false, 'error' => 'Motorcycle not found'];
        }
        
        // Check bookings for conflicts
        if (isset($_SESSION['mock_bookings'])) {
            foreach ($_SESSION['mock_bookings'] as $booking) {
                if ($booking['motorcycleId'] == $motorcycleId) {
                    $bookStart = strtotime($booking['startDate']);
                    $bookEnd = strtotime($booking['endDate']);
                    $checkStart = strtotime($startDate);
                    $checkEnd = strtotime($endDate);
                    
                    // If dates overlap
                    if ($checkStart <= $bookEnd && $checkEnd >= $bookStart) {
                        return ['success' => false, 'available' => false, 'message' => 'Not available for selected dates'];
                    }
                }
            }
        }
        
        return [
            'success' => true,
            'available' => true,
            'message' => 'Available for selected dates'
        ];
    }
    
    /**
     * Search motorcycles
     */
    public static function search($query) {
        $results = [];
        
        if (isset($_SESSION['motorcycles'])) {
            foreach ($_SESSION['motorcycles'] as $motorcycle) {
                $searchStr = strtolower($query);
                $brand = strtolower($motorcycle['brand'] ?? '');
                $model = strtolower($motorcycle['model'] ?? '');
                
                if (strpos($brand, $searchStr) !== false || strpos($model, $searchStr) !== false) {
                    $results[] = $motorcycle;
                }
            }
        }
        
        return [
            'success' => true,
            'data' => $results,
            'count' => count($results)
        ];
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'getAll':
                $result = MotorcyclesAPI::getAll($_GET);
                jsonResponse($result);
                break;
                
            case 'getById':
                if (!isset($_GET['id'])) {
                    jsonResponse(['success' => false, 'error' => 'ID required'], 400);
                }
                $result = MotorcyclesAPI::getById($_GET['id']);
                jsonResponse($result);
                break;
                
            case 'checkAvailability':
                if (!isset($_GET['id']) || !isset($_GET['start']) || !isset($_GET['end'])) {
                    jsonResponse(['success' => false, 'error' => 'Required parameters missing'], 400);
                }
                $result = MotorcyclesAPI::checkAvailability($_GET['id'], $_GET['start'], $_GET['end']);
                jsonResponse($result);
                break;
                
            case 'search':
                if (!isset($_GET['q'])) {
                    jsonResponse(['success' => false, 'error' => 'Search query required'], 400);
                }
                $result = MotorcyclesAPI::search($_GET['q']);
                jsonResponse($result);
                break;
                
            default:
                jsonResponse(['success' => false, 'error' => 'Unknown action'], 404);
        }
    }
}
?>
