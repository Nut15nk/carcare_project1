<?php
require_once 'config.php';

class AuthService {
    public static function login($email, $password) {
        $data = [
            'email' => $email,
            'password' => $password
        ];
        
        $response = ApiConfig::makeApiCall('/auth/login', 'POST', $data);
        
        if ($response['status'] === 200 && $response['data']['success']) {
            $_SESSION['user'] = $response['data']['data'];
            return $response['data']['data'];
        }
        
        return null;
    }
    
    public static function register($userData) {
        $response = ApiConfig::makeApiCall('/auth/register', 'POST', $userData);
        
        if ($response['status'] === 200 && $response['data']['success']) {
            return $response['data']['data'];
        }
        
        return null;
    }
    
    public static function logout() {
        if (isset($_SESSION['user']['token'])) {
            $response = ApiConfig::makeApiCall('/auth/logout', 'POST', null, $_SESSION['user']['token']);
        }
        
        session_destroy();
        return true;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user']);
    }
    
    public static function getUserRole() {
        return $_SESSION['user']['role'] ?? null;
    }
    
    public static function getToken() {
        return $_SESSION['user']['token'] ?? null;
    }
    
    public static function getUserId() {
        return $_SESSION['user']['userId'] ?? null;
    }
}
?>