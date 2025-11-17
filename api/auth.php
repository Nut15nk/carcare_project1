<?php
require_once 'config.php';

class AuthService {
    public static function login($email, $password) {
        $data = [
            'email' => $email,
            'password' => $password
        ];
        
        $response = ApiConfig::makeApiCall('/auth/login', 'POST', $data);
        
        error_log("Login API Response: " . print_r($response, true));
        
        if ($response['status'] === 200 && isset($response['data']['success']) && $response['data']['success']) {
            $userData = $response['data']['data'];
            
            // ตั้งค่า session ตาม structure จริงจาก API
            $_SESSION['user'] = [
                'userId' => $userData['userId'] ?? '',
                'email' => $userData['email'] ?? '',
                'firstName' => $userData['firstName'] ?? '',
                'lastName' => $userData['lastName'] ?? '',
                'role' => $userData['role'] ?? 'CUSTOMER', // ใช้ CUSTOMER ตาม API
                'token' => $userData['token'] ?? '',
                'phone' => $userData['phone'] ?? ''
            ];
            
            // ตั้งค่า session เก่าเพื่อความเข้ากันได้
            $_SESSION['user_id'] = $_SESSION['user']['userId'];
            $_SESSION['user_email'] = $_SESSION['user']['email'];
            $_SESSION['user_name'] = $_SESSION['user']['firstName'];
            $_SESSION['user_role'] = $_SESSION['user']['role'];
            
            error_log("Session set successfully for user: " . $_SESSION['user']['email']);
            return $_SESSION['user'];
        } else {
            $errorMsg = $response['data']['message'] ?? 'Login failed';
            error_log("Login failed: " . $errorMsg);
            return null;
        }
    }
    
    public static function register($userData) {
        $response = ApiConfig::makeApiCall('/auth/register', 'POST', $userData);
        
        error_log("Register API Response: " . print_r($response, true));
        
        if ($response['status'] === 200 && isset($response['data']['success']) && $response['data']['success']) {
            return $response['data']['data'];
        }
        
        return null;
    }
    
    public static function logout() {
        if (isset($_SESSION['user']['token'])) {
            try {
                $response = ApiConfig::makeApiCall('/auth/logout', 'POST', null, $_SESSION['user']['token']);
                error_log("Logout API Response: " . print_r($response, true));
            } catch (Exception $e) {
                error_log("Logout API Error: " . $e->getMessage());
            }
        }
        
        // ล้าง session ทั้งหมด
        session_unset();
        session_destroy();
        
        // เริ่ม session ใหม่
        session_start();
        return true;
    }
    
    public static function isLoggedIn() {
        $isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']['userId']);
        error_log("isLoggedIn check: " . ($isLoggedIn ? 'YES' : 'NO'));
        if ($isLoggedIn) {
            error_log("Current user: " . ($_SESSION['user']['email'] ?? 'Unknown'));
        }
        return $isLoggedIn;
    }
    
    public static function getUserRole() {
        return $_SESSION['user']['role'] ?? null;
    }
    
    public static function getToken() {
        return $_SESSION['user']['token'] ?? null;
    }
    
    public static function getUserId() {
        $userId = $_SESSION['user']['userId'] ?? $_SESSION['user_id'] ?? null;
        error_log("getUserId: " . ($userId ?? 'NOT FOUND'));
        return $userId;
    }
    
    public static function getUserData() {
        return $_SESSION['user'] ?? null;
    }
    
    // ฟังก์ชัน debug session
    public static function debugSession() {
        error_log("=== SESSION DEBUG ===");
        error_log("Session ID: " . session_id());
        error_log("Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'));
        error_log("Session Data: " . print_r($_SESSION, true));
        error_log("=== END SESSION DEBUG ===");
    }
}
?>