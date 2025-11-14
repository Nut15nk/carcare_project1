<?php
/**
 * Bookings API
 * Handles all booking-related API calls
 */

require_once __DIR__ . '/config.php';

class BookingsAPI {
    
    /**
     * Create a new booking
     */
    public static function create($data) {
        // Validate required fields
        $required = ['motorcycleId', 'startDate', 'endDate', 'returnLocation'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return ['success' => false, 'error' => "Missing field: $field"];
            }
        }
        
        // Get user info from session
        if (!isset($_SESSION['user_email'])) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }
        
        // Create booking object
        $booking = [
            'id' => 'BK' . time() . random_int(1000, 9999),
            'motorcycleId' => $data['motorcycleId'],
            'motorcycleName' => $data['motorcycleName'] ?? 'Unknown',
            'userEmail' => $_SESSION['user_email'],
            'userName' => $_SESSION['user_name'] ?? 'Guest',
            'startDate' => $data['startDate'],
            'endDate' => $data['endDate'],
            'totalDays' => $data['totalDays'] ?? 1,
            'pricePerDay' => $data['pricePerDay'] ?? 0,
            'totalPrice' => $data['totalPrice'] ?? 0,
            'discount' => $data['discount'] ?? 0,
            'returnLocation' => $data['returnLocation'],
            'paymentProof' => $data['paymentProof'] ?? '',
            'status' => 'confirmed',
            'createdAt' => date('Y-m-d H:i:s')
        ];
        
        // Save to session
        if (!isset($_SESSION['mock_bookings'])) {
            $_SESSION['mock_bookings'] = [];
        }
        $_SESSION['mock_bookings'][] = $booking;
        
        return [
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => $booking
        ];
    }
    
    /**
     * Get user's bookings
     */
    public static function getUserBookings($userEmail = null) {
        if ($userEmail === null && isset($_SESSION['user_email'])) {
            $userEmail = $_SESSION['user_email'];
        }
        
        if (!$userEmail) {
            return ['success' => false, 'error' => 'User email required'];
        }
        
        $bookings = [];
        if (isset($_SESSION['mock_bookings'])) {
            foreach ($_SESSION['mock_bookings'] as $booking) {
                if (isset($booking['userEmail']) && $booking['userEmail'] === $userEmail) {
                    $bookings[] = $booking;
                }
            }
        }
        
        return [
            'success' => true,
            'data' => $bookings,
            'count' => count($bookings)
        ];
    }
    
    /**
     * Get booking by ID
     */
    public static function getById($bookingId) {
        if (isset($_SESSION['mock_bookings'])) {
            foreach ($_SESSION['mock_bookings'] as $booking) {
                if ($booking['id'] === $bookingId) {
                    return ['success' => true, 'data' => $booking];
                }
            }
        }
        
        return ['success' => false, 'error' => 'Booking not found'];
    }
    
    /**
     * Cancel a booking
     */
    public static function cancel($bookingId) {
        if (!isset($_SESSION['mock_bookings'])) {
            return ['success' => false, 'error' => 'No bookings found'];
        }
        
        foreach ($_SESSION['mock_bookings'] as &$booking) {
            if ($booking['id'] === $bookingId) {
                // Only allow cancellation if status is not already cancelled/completed
                if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed') {
                    $booking['status'] = 'cancelled';
                    return [
                        'success' => true,
                        'message' => 'Booking cancelled successfully',
                        'data' => $booking
                    ];
                } else {
                    return ['success' => false, 'error' => 'Booking cannot be cancelled'];
                }
            }
        }
        
        return ['success' => false, 'error' => 'Booking not found'];
    }
    
    /**
     * Update booking status
     */
    public static function updateStatus($bookingId, $status) {
        $validStatuses = ['pending', 'confirmed', 'active', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'error' => 'Invalid status'];
        }
        
        if (!isset($_SESSION['mock_bookings'])) {
            return ['success' => false, 'error' => 'No bookings found'];
        }
        
        foreach ($_SESSION['mock_bookings'] as &$booking) {
            if ($booking['id'] === $bookingId) {
                $booking['status'] = $status;
                return [
                    'success' => true,
                    'message' => 'Booking status updated',
                    'data' => $booking
                ];
            }
        }
        
        return ['success' => false, 'error' => 'Booking not found'];
    }
    
    /**
     * Get all bookings (admin)
     */
    public static function getAll($filters = []) {
        if (!isset($_SESSION['mock_bookings'])) {
            return ['success' => true, 'data' => [], 'count' => 0];
        }
        
        $bookings = $_SESSION['mock_bookings'];
        
        // Apply filters
        if (isset($filters['status'])) {
            $bookings = array_filter($bookings, function($b) use ($filters) {
                return $b['status'] === $filters['status'];
            });
        }
        
        if (isset($filters['motorcycleId'])) {
            $bookings = array_filter($bookings, function($b) use ($filters) {
                return $b['motorcycleId'] === $filters['motorcycleId'];
            });
        }
        
        return [
            'success' => true,
            'data' => array_values($bookings),
            'count' => count($bookings)
        ];
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'getUserBookings':
                $email = $_GET['email'] ?? null;
                $result = BookingsAPI::getUserBookings($email);
                jsonResponse($result);
                break;
                
            case 'getById':
                if (!isset($_GET['id'])) {
                    jsonResponse(['success' => false, 'error' => 'ID required'], 400);
                }
                $result = BookingsAPI::getById($_GET['id']);
                jsonResponse($result);
                break;
                
            case 'getAll':
                $filters = [];
                if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
                if (isset($_GET['motorcycleId'])) $filters['motorcycleId'] = $_GET['motorcycleId'];
                $result = BookingsAPI::getAll($filters);
                jsonResponse($result);
                break;
                
            default:
                jsonResponse(['success' => false, 'error' => 'Unknown action'], 404);
        }
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'create':
                $result = BookingsAPI::create($data);
                jsonResponse($result);
                break;
                
            case 'cancel':
                if (!isset($_GET['id'])) {
                    jsonResponse(['success' => false, 'error' => 'Booking ID required'], 400);
                }
                $result = BookingsAPI::cancel($_GET['id']);
                jsonResponse($result);
                break;
                
            case 'updateStatus':
                if (!isset($_GET['id']) || !isset($_GET['status'])) {
                    jsonResponse(['success' => false, 'error' => 'ID and status required'], 400);
                }
                $result = BookingsAPI::updateStatus($_GET['id'], $_GET['status']);
                jsonResponse($result);
                break;
                
            default:
                jsonResponse(['success' => false, 'error' => 'Unknown action'], 404);
        }
    }
}
?>
