<?php
// api/config.php
session_start(); // เพิ่มบรรทัดนี้สำคัญ!

// โหลด autoload จาก vendor ที่ติดตั้งผ่าน Composer
require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApiConfig {
    const BASE_URL = 'http://localhost:8080/api';
    
    public static function makeApiCall($url, $method = 'GET', $data = null, $token = null, $customHeaders = []) {
        $fullUrl = self::BASE_URL . $url;
        
        // DEBUG: Log request
        error_log("API Call: $method $fullUrl");
        error_log("Token: " . ($token ? 'YES' : 'NO'));
        
        return self::makeGuzzleCall($fullUrl, $method, $data, $token, $customHeaders);
    }
    
    private static function makeGuzzleCall($fullUrl, $method, $data, $token, $customHeaders) {
        try {
            $client = new Client([
                'timeout' => 30,
                'http_errors' => false,
                'verify' => false // เพิ่มนี้ถ้ามีปัญหา SSL
            ]);
            
            $options = [
                'headers' => array_merge([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ], $customHeaders)
            ];
            
            // แก้ไข: รับ token เป็น parameter โดยตรง
            if ($token) {
                $options['headers']['Authorization'] = 'Bearer ' . $token;
            }
            
            if ($data && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $data;
            }
            
            $response = $client->request($method, $fullUrl, $options);
            
            $responseData = [
                'status' => $response->getStatusCode(),
                'data' => json_decode($response->getBody(), true),
                'method' => 'guzzle',
                'success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300
            ];
            
            // DEBUG: Log response
            error_log("API Response: " . $response->getStatusCode());
            error_log("API Response Data: " . json_encode($responseData['data']));
            
            return $responseData;
            
        } catch (Exception $e) {
            // Fallback to file_get_contents ถ้า Guzzle error
            error_log("Guzzle error: " . $e->getMessage());
            return self::makeFileGetContentsCall($fullUrl, $method, $data, $token, $customHeaders);
        }
    }
    
    // Fallback function (เก็บไว้เป็น backup)
    private static function makeFileGetContentsCall($fullUrl, $method, $data, $token, $customHeaders) {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        
        $headers = array_merge($headers, $customHeaders);
        
        $options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", $headers),
                'timeout' => 30,
                'ignore_errors' => true
            ]
        ];
        
        if ($data && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $options['http']['content'] = json_encode($data);
        }
        
        $context = stream_context_create($options);
        $response = @file_get_contents($fullUrl, false, $context);
        
        $httpCode = 200;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = isset($matches[1]) ? (int)$matches[1] : 200;
        }
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true),
            'method' => 'file_get_contents',
            'success' => $httpCode >= 200 && $httpCode < 300
        ];
    }
    
    // ตรวจสอบว่า Guzzle พร้อมใช้งาน
    public static function isGuzzleAvailable() {
        return class_exists('GuzzleHttp\Client');
    }
    
    // ฟังก์ชันตรวจสอบระบบ
    public static function getSystemInfo() {
        return [
            'guzzle_available' => self::isGuzzleAvailable(),
            'composer_vendor_exists' => is_dir(__DIR__ . '/../vendor/') ? 'YES' : 'NO',
            'curl_available' => function_exists('curl_init'),
            'file_get_contents_available' => function_exists('file_get_contents'),
            'php_version' => PHP_VERSION
        ];
    }
    
    // ฟังก์ชันดึง error message
    public static function getErrorMessage($response) {
        if (isset($response['error'])) {
            return $response['error'];
        }
        
        if (isset($response['data']['message'])) {
            return $response['data']['message'];
        }
        
        return 'Unknown error occurred';
    }
}

// Test function
if (isset($_GET['test'])) {
    header('Content-Type: application/json');
    
    $systemInfo = ApiConfig::getSystemInfo();
    $testResponse = ApiConfig::makeApiCall('/motorcycles', 'GET');
    
    echo json_encode([
        'system_info' => $systemInfo,
        'api_test' => $testResponse,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}
?>