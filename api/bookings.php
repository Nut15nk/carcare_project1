<?php
require_once 'config.php';
require_once 'auth.php';

class BookingService {
    
    // เพิ่มฟังก์ชันใหม่: ดึงข้อมูลผู้ใช้โดย ID
    public static function getUserById($userId) {
        try {
            $token = AuthService::getToken();
            
            error_log("Getting user by ID: " . $userId);
            
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            
            $response = ApiConfig::makeApiCall("/users/{$userId}", 'GET', null, null, $headers);
            
            error_log("Get User by ID Response: " . print_r($response, true));
            
            if ($response['status'] === 200 && isset($response['data']['success']) && $response['data']['success']) {
                return $response['data']['data'] ?? null;
            } else {
                $errorMsg = $response['data']['message'] ?? 'Unknown error';
                error_log("Get User by ID API Error: " . $errorMsg);
                return null;
            }
        } catch (Exception $e) {
            error_log("Get User by ID Error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function createBooking($bookingData) {
        try {
            $token = AuthService::getToken();
            $userId = AuthService::getUserId();
            
            error_log("Creating booking for user: " . $userId);
            error_log("Booking data: " . print_r($bookingData, true));
            
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            if ($userId) {
                $headers['X-User-ID'] = $userId;
            }
            
            $response = ApiConfig::makeApiCall('/reservations', 'POST', $bookingData, null, $headers);
            
            error_log("Create Booking API Response: " . print_r($response, true));
            
            if ($response['status'] === 200 || $response['status'] === 201) {
                return $response['data']['data'] ?? $response['data'];
            } else {
                $errorMsg = $response['data']['message'] ?? 'Unknown error';
                error_log("Booking API Error - Status: " . $response['status'] . ", Message: " . $errorMsg);
                throw new Exception($errorMsg);
            }
        } catch (Exception $e) {
            error_log("BookingService Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function getCustomerBookings($customerId) {
        try {
            $token = AuthService::getToken();
            
            error_log("Getting bookings for customer: " . $customerId);
            
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            
            $response = ApiConfig::makeApiCall("/reservations/customer/{$customerId}", 'GET', null, null, $headers);
            
            // Debug response
            error_log("Get Customer Bookings API Response - Status: " . $response['status']);
            error_log("Get Customer Bookings API Data: " . print_r($response['data'], true));
            
            if ($response['status'] === 200 && isset($response['data']['success']) && $response['data']['success']) {
                $bookings = $response['data']['data'] ?? [];
                error_log("Found " . count($bookings) . " bookings for customer " . $customerId);
                return $bookings;
            } else {
                $errorMsg = $response['data']['message'] ?? 'Unknown error';
                error_log("Get Bookings API Error - Status: " . $response['status'] . ", Message: " . $errorMsg);
                return [];
            }
        } catch (Exception $e) {
            error_log("BookingService getCustomerBookings Error: " . $e->getMessage());
            return [];
        }
    }
    
    public static function getBookingById($bookingId) {
        try {
            $token = AuthService::getToken();
            
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            
            $response = ApiConfig::makeApiCall("/reservations/{$bookingId}", 'GET', null, null, $headers);
            
            error_log("Get Booking by ID API Response: " . print_r($response, true));
            
            if ($response['status'] === 200) {
                return $response['data']['data'] ?? null;
            } else {
                error_log("Get Booking by ID API Error: " . $response['status']);
                return null;
            }
        } catch (Exception $e) {
            error_log("BookingService Error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function calculatePrice($startDate, $endDate, $pricePerDay, $discountCode = null) {
        try {
            $params = [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'pricePerDay' => $pricePerDay
            ];
            
            if ($discountCode) {
                $params['discountCode'] = $discountCode;
            }
            
            $queryString = http_build_query($params);
            $response = ApiConfig::makeApiCall("/reservations/calculate-price?{$queryString}");
            
            if ($response['status'] === 200) {
                return $response['data']['data'] ?? ['finalPrice' => 0];
            } else {
                error_log("Calculate Price API Error: " . $response['status']);
                return ['finalPrice' => 0];
            }
        } catch (Exception $e) {
            error_log("BookingService Error: " . $e->getMessage());
            return ['finalPrice' => 0];
        }
    }
    
    public static function cancelBooking($bookingId) {
        try {
            $token = AuthService::getToken();
            
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            
            $response = ApiConfig::makeApiCall("/reservations/{$bookingId}/cancel", 'POST', null, null, $headers);
            
            if ($response['status'] === 200) {
                return $response['data']['data'] ?? $response['data'];
            } else {
                error_log("Cancel Booking API Error: " . $response['status']);
                return null;
            }
        } catch (Exception $e) {
            error_log("BookingService Error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function updateBookingStatus($bookingId, $status) {
        try {
            $token = AuthService::getToken();
            
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            
            $updateData = ['status' => $status];
            $response = ApiConfig::makeApiCall("/reservations/{$bookingId}/status", 'PUT', $updateData, null, $headers);
            
            if ($response['status'] === 200) {
                return $response['data']['data'] ?? $response['data'];
            } else {
                error_log("Update Booking Status API Error: " . $response['status']);
                return null;
            }
        } catch (Exception $e) {
            error_log("BookingService Error: " . $e->getMessage());
            return null;
        }
    }
    
    public static function getLatestCustomerBooking($customerId) {
        try {
            $token = AuthService::getToken();
            
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
            
            $response = ApiConfig::makeApiCall("/reservations/customer/{$customerId}/latest", 'GET', null, null, $headers);
            
            if ($response['status'] === 200) {
                return $response['data']['data'] ?? null;
            } else {
                error_log("Get Latest Booking API Error: " . $response['status']);
                return null;
            }
        } catch (Exception $e) {
            error_log("BookingService Error: " . $e->getMessage());
            return null;
        }
    }
}
?>