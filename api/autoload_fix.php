<?php
// api/autoload_fix.php

/**
 * Fix autoloading for Guzzle
 */

function loadGuzzleManually() {
    $guzzleBasePath = __DIR__ . '/../vendor/guzzlehttp/guzzle/src/';
    
    // ไฟล์ที่ต้องโหลดตามลำดับ
    $requiredFiles = [
        'ClientInterface.php',
        'ClientTrait.php', 
        'HandlerStack.php',
        'RequestOptions.php',
        'Client.php'
    ];
    
    foreach ($requiredFiles as $file) {
        $fullPath = $guzzleBasePath . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
        } else {
            error_log("Guzzle file not found: " . $fullPath);
            return false;
        }
    }
    
    return true;
}

// พยายามโหลด autoload ก่อน
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php'
];

$autoloadLoaded = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadLoaded = true;
        break;
    }
}

// ถ้า autoload ไม่ทำงาน ให้โหลด Guzzle แบบ manual
if (!$autoloadLoaded) {
    loadGuzzleManually();
}