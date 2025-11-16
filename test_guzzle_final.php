<?php
// test_guzzle_correct_path.php

echo "<h2>Guzzle Test - Correct Path</h2>";

// ตรวจสอบ path จริง
$guzzlePath = __DIR__ . '/assets/vendor/guzzlehttp/guzzle/src/Client.php';
echo "Checking path: $guzzlePath<br>";

if (file_exists($guzzlePath)) {
    echo "✅ Guzzle found at correct path!<br>";
    
    // โหลดไฟล์ที่จำเป็นจาก assets/vendor
    $requiredFiles = [
        '/assets/vendor/guzzlehttp/guzzle/src/ClientInterface.php',
        '/assets/vendor/guzzlehttp/guzzle/src/ClientTrait.php',
        '/assets/vendor/guzzlehttp/guzzle/src/HandlerStack.php',
        '/assets/vendor/guzzlehttp/guzzle/src/RequestOptions.php',
        '/assets/vendor/guzzlehttp/guzzle/src/Client.php'
    ];
    
    foreach ($requiredFiles as $file) {
        $fullPath = __DIR__ . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
            echo "✅ Loaded: $file<br>";
        } else {
            echo "❌ Missing: $file<br>";
        }
    }
    
    // ตรวจสอบ class
    if (class_exists('GuzzleHttp\Client')) {
        echo "✅ GuzzleHttp\Client class loaded successfully!<br>";
        
        // ทดสอบใช้งาน
        try {
            $client = new GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->request('GET', 'http://localhost:8080/api/motorcycles', [
                'http_errors' => false
            ]);
            
            echo "✅ Guzzle request: SUCCESS (HTTP " . $response->getStatusCode() . ")<br>";
            $data = json_decode($response->getBody(), true);
            echo "✅ Data count: " . count($data['data'] ?? []) . " motorcycles<br>";
            
        } catch (Exception $e) {
            echo "❌ Guzzle error: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ GuzzleHttp\Client class not found after loading<br>";
    }
    
} else {
    echo "❌ Guzzle not found at: $guzzlePath<br>";
    
    // แสดง paths ที่มีอยู่
    echo "<h3>Available vendor paths:</h3>";
    $vendorPaths = [
        __DIR__ . '/vendor/',
        __DIR__ . '/assets/vendor/',
        __DIR__ . '/api/vendor/'
    ];
    
    foreach ($vendorPaths as $path) {
        echo "Path: $path - " . (is_dir($path) ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
    }
}

// ทดสอบ file_get_contents (fallback)
echo "<h3>Fallback Test</h3>";
$response = @file_get_contents('http://localhost:8080/api/motorcycles');
if ($response !== false) {
    $data = json_decode($response, true);
    echo "✅ file_get_contents: WORKING (" . count($data['data'] ?? []) . " motorcycles)<br>";
} else {
    echo "❌ file_get_contents: FAILED<br>";
}