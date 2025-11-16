<?php
require_once 'config.php';

class UserService {
    public static function getUserById($id) {
        $token = AuthService::getToken();
        $response = ApiConfig::makeApiCall("/users/{$id}", 'GET', null, $token);
        
        return $response['status'] === 200 ? $response['data']['data'] : null;
    }
    
    public static function updateProfile($userId, $profileData) {
        $token = AuthService::getToken();
        $response = ApiConfig::makeApiCall("/users/{$userId}/profile", 'PUT', $profileData, $token);
        
        return $response['status'] === 200 ? $response['data']['data'] : null;
    }
}
?>