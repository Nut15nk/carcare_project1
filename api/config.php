<?php
/**
 * API Configuration File
 * Centralized API settings and base URLs
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// API Base URLs
define('API_BASE_URL', 'http://localhost:8000/api');
define('API_TIMEOUT', 30); // seconds

// API Response Headers
header('Content-Type: application/json; charset=utf-8');

// Helper function to make API requests
function makeApiRequest($method, $endpoint, $data = null, $headers = []) {
    $url = API_BASE_URL . $endpoint;
    
    $defaultHeaders = [
        'Content-Type: application/json',
    ];
    
    // Add auth token if user is logged in
    if (isset($_SESSION['auth_token'])) {
        $defaultHeaders['Authorization'] = 'Bearer ' . $_SESSION['auth_token'];
    }
    
    $headers = array_merge($defaultHeaders, $headers);
    
    $options = [
        'http' => [
            'method' => $method,
            'headers' => $headers,
            'timeout' => API_TIMEOUT
        ]
    ];
    
    if ($data !== null) {
        $options['http']['content'] = json_encode($data);
    }
    
    $context = stream_context_create($options);
    
    try {
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return [
                'success' => false,
                'error' => 'API request failed',
                'status_code' => 500
            ];
        }
        return [
            'success' => true,
            'data' => json_decode($response, true),
            'status_code' => 200
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'status_code' => 500
        ];
    }
}

// Helper function to return JSON response
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
?>
