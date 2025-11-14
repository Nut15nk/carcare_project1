<?php
/**
 * Users API
 * Handles user-related API calls (profile, authentication)
 */

require_once __DIR__ . '/config.php';

class UsersAPI {
    
    /**
     * Get user profile
     */
    public static function getProfile($email = null) {
        if ($email === null && isset($_SESSION['user_email'])) {
            $email = $_SESSION['user_email'];
        }
        
        if (!$email) {
            return ['success' => false, 'error' => 'User email required'];
        }
        
        // Return session-based user data
        return [
            'success' => true,
            'data' => [
                'name' => $_SESSION['user_name'] ?? 'Unknown',
                'email' => $_SESSION['user_email'] ?? '',
                'role' => $_SESSION['user_role'] ?? 'customer',
                'created_at' => $_SESSION['user_created_at'] ?? date('Y-m-d')
            ]
        ];
    }
    
    /**
     * Update user profile
     */
    public static function updateProfile($data) {
        if (!isset($_SESSION['user_email'])) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }
        
        // Update allowed fields
        $allowedFields = ['name', 'phone', 'address', 'line_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $sessionKey = 'user_' . $field;
                $_SESSION[$sessionKey] = $data[$field];
            }
        }
        
        return [
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => self::getProfile()['data']
        ];
    }
    
    /**
     * Login user
     */
    public static function login($email, $password) {
        // Simple validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // In a real app, this would query a database
        // For now, we're using the session-based system
        return [
            'success' => true,
            'message' => 'Login successful',
            'redirect' => 'index.php?page=home'
        ];
    }
    
    /**
     * Register new user
     */
    public static function register($data) {
        $required = ['name', 'email', 'password', 'password_confirm'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return ['success' => false, 'error' => "Missing field: $field"];
            }
        }
        
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }
        
        // Check password match
        if ($data['password'] !== $data['password_confirm']) {
            return ['success' => false, 'error' => 'Passwords do not match'];
        }
        
        // Check password length
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        
        // In a real app, check if email already exists in database
        
        return [
            'success' => true,
            'message' => 'Registration successful',
            'redirect' => 'index.php?page=login'
        ];
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        session_destroy();
        return [
            'success' => true,
            'message' => 'Logged out successfully',
            'redirect' => 'index.php?page=login'
        ];
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return [
            'authenticated' => isset($_SESSION['user_email']),
            'user_email' => $_SESSION['user_email'] ?? null,
            'user_role' => $_SESSION['user_role'] ?? null
        ];
    }
    
    /**
     * Change password
     */
    public static function changePassword($oldPassword, $newPassword, $confirmPassword) {
        if (!isset($_SESSION['user_email'])) {
            return ['success' => false, 'error' => 'User not authenticated'];
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }
        
        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'error' => 'New passwords do not match'];
        }
        
        // In a real app, verify old password against database
        
        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'getProfile':
                $email = $_GET['email'] ?? null;
                $result = UsersAPI::getProfile($email);
                jsonResponse($result);
                break;
                
            case 'isAuthenticated':
                $result = UsersAPI::isAuthenticated();
                jsonResponse($result);
                break;
                
            case 'logout':
                $result = UsersAPI::logout();
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
            case 'login':
                if (!isset($data['email']) || !isset($data['password'])) {
                    jsonResponse(['success' => false, 'error' => 'Email and password required'], 400);
                }
                $result = UsersAPI::login($data['email'], $data['password']);
                jsonResponse($result);
                break;
                
            case 'register':
                $result = UsersAPI::register($data);
                jsonResponse($result);
                break;
                
            case 'updateProfile':
                $result = UsersAPI::updateProfile($data);
                jsonResponse($result);
                break;
                
            case 'changePassword':
                if (!isset($data['oldPassword']) || !isset($data['newPassword']) || !isset($data['confirmPassword'])) {
                    jsonResponse(['success' => false, 'error' => 'All password fields required'], 400);
                }
                $result = UsersAPI::changePassword($data['oldPassword'], $data['newPassword'], $data['confirmPassword']);
                jsonResponse($result);
                break;
                
            default:
                jsonResponse(['success' => false, 'error' => 'Unknown action'], 404);
        }
    }
}
?>
