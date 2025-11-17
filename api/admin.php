<?php
// api/admin.php
require_once 'config.php';

class AdminService {
    
    /**
     * Get dashboard statistics from Spring Boot API - FIXED
     */
    public static function getDashboardStats() {
        try {
            // ตรวจสอบ session และ token
            if (!isset($_SESSION['user']['token'])) {
                error_log("No token in session for getDashboardStats");
                return self::getFallbackStats();
            }
            
            $token = $_SESSION['user']['token'];
            
            // ส่ง token เป็น parameter ที่ 4 แทน headers
            $response = ApiConfig::makeApiCall('/admin/dashboard/stats', 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            
            error_log("Dashboard stats API failed with status: " . $response['status']);
            return self::getFallbackStats();
            
        } catch (Exception $e) {
            error_log("AdminService getDashboardStats Error: " . $e->getMessage());
            return self::getFallbackStats();
        }
    }
    
    /**
     * Get all reservations from Spring Boot API - FIXED
     */
    public static function getAllReservations() {
        try {
            if (!isset($_SESSION['user']['token'])) {
                error_log("No token in session for getAllReservations");
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            // ใช้ endpoint ที่ถูกต้องและส่ง token เป็น parameter
            $response = ApiConfig::makeApiCall('/admin/bookings', 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            
            error_log("Reservations API failed with status: " . $response['status']);
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getAllReservations Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all customers from Spring Boot API - FIXED
     */
    public static function getAllCustomers() {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall('/admin/customers', 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getAllCustomers Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all employees from Spring Boot API - FIXED
     */
    public static function getAllEmployees() {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall('/admin/employees', 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getAllEmployees Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update reservation status - FIXED
     */
    public static function updateReservationStatus($reservationId, $status) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall(
                "/admin/reservations/{$reservationId}/status?status={$status}", 
                'PUT', 
                null, 
                $token
            );
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService updateReservationStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get revenue report - FIXED
     */
    public static function getRevenueReport($period = 'monthly') {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall(
                "/admin/dashboard/revenue-report?period={$period}", 
                'GET', 
                null, 
                $token
            );
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getRevenueReport Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent activities - FIXED
     */
    public static function getRecentActivities($limit = 10) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall(
                "/admin/dashboard/recent-activities?limit={$limit}", 
                'GET', 
                null, 
                $token
            );
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getRecentActivities Error: " . $e->getMessage());
            return [];
        }
    }

    // ===== ฟังก์ชันใหม่ที่เพิ่ม =====

    /**
     * Create new employee - FIXED
     */
    public static function createEmployee($employeeData) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall('/admin/employees', 'POST', $employeeData, $token);
            
            return $response['status'] === 201 || $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService createEmployee Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update employee - FIXED
     */
    public static function updateEmployee($employeeId, $employeeData) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/employees/{$employeeId}", 'PUT', $employeeData, $token);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService updateEmployee Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete employee - FIXED
     */
    public static function deleteEmployee($employeeId) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/employees/{$employeeId}", 'DELETE', null, $token);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService deleteEmployee Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update payment status - FIXED
     */
    public static function updatePaymentStatus($reservationId, $status) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall(
                "/admin/payments/{$reservationId}/status?status={$status}", 
                'PUT', 
                null, 
                $token
            );
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService updatePaymentStatus Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all motorcycles for admin - FIXED
     */
    public static function getAllMotorcycles() {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall('/admin/motorcycles', 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getAllMotorcycles Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create new motorcycle - FIXED
     */
    public static function createMotorcycle($motorcycleData) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall('/admin/motorcycles', 'POST', $motorcycleData, $token);
            
            return $response['status'] === 201 || $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService createMotorcycle Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update motorcycle - FIXED
     */
    public static function updateMotorcycle($motorcycleId, $motorcycleData) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/motorcycles/{$motorcycleId}", 'PUT', $motorcycleData, $token);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService updateMotorcycle Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete motorcycle - FIXED
     */
    public static function deleteMotorcycle($motorcycleId) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/motorcycles/{$motorcycleId}", 'DELETE', null, $token);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService deleteMotorcycle Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all discounts - FIXED
     */
    public static function getAllDiscounts() {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall('/admin/discounts', 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getAllDiscounts Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create new discount - FIXED
     */
    public static function createDiscount($discountData) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall('/admin/discounts', 'POST', $discountData, $token);
            
            return $response['status'] === 201 || $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService createDiscount Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update discount - FIXED
     */
    public static function updateDiscount($discountId, $discountData) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/discounts/{$discountId}", 'PUT', $discountData, $token);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService updateDiscount Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete discount - FIXED
     */
    public static function deleteDiscount($discountId) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/discounts/{$discountId}", 'DELETE', null, $token);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService deleteDiscount Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get payment details - FIXED
     */
    public static function getPaymentDetails($reservationId) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/payments/{$reservationId}", 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getPaymentDetails Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get customer details - FIXED
     */
    public static function getCustomerDetails($customerId) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/customers/{$customerId}", 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getCustomerDetails Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update customer status - FIXED
     */
    public static function updateCustomerStatus($customerId, $status) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall(
                "/admin/customers/{$customerId}/status?status={$status}", 
                'PUT', 
                null, 
                $token
            );
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService updateCustomerStatus Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reservation details - FIXED
     */
    public static function getReservationDetails($reservationId) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/reservations/{$reservationId}", 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getReservationDetails Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get motorcycle details - FIXED
     */
    public static function getMotorcycleDetails($motorcycleId) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/motorcycles/{$motorcycleId}", 'GET', null, $token);
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getMotorcycleDetails Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update motorcycle status - FIXED
     */
    public static function updateMotorcycleStatus($motorcycleId, $statusData) {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return false;
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall("/admin/motorcycles/{$motorcycleId}/status", 'PUT', $statusData, $token);
            
            return $response['status'] === 200;
            
        } catch (Exception $e) {
            error_log("AdminService updateMotorcycleStatus Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get system analytics - FIXED
     */
    public static function getSystemAnalytics($period = 'monthly') {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall(
                "/admin/dashboard/analytics?period={$period}", 
                'GET', 
                null, 
                $token
            );
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService getSystemAnalytics Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Export reports - FIXED
     */
    public static function exportReports($reportType, $format = 'csv') {
        try {
            if (!isset($_SESSION['user']['token'])) {
                return [];
            }
            
            $token = $_SESSION['user']['token'];
            
            $response = ApiConfig::makeApiCall(
                "/admin/reports/export?type={$reportType}&format={$format}", 
                'GET', 
                null, 
                $token
            );
            
            if ($response['status'] === 200 && isset($response['data']['data'])) {
                return $response['data']['data'];
            }
            return [];
            
        } catch (Exception $e) {
            error_log("AdminService exportReports Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Fallback stats when API fails
     */
    private static function getFallbackStats() {
        return [
            'totalBookings' => 0,
            'pendingBookings' => 0,
            'activeBookings' => 0,
            'totalRevenue' => 0,
            'availableMotorcycles' => 0
        ];
    }
}
?>