<?php
// เปิดแสดง error ทั้งหมด
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Final Debug - Profile Page</h1>";

// Test session
session_start();
echo "<p>✓ Session started</p>";

// Test loading ProfilePages.php directly
echo "<h2>Testing ProfilePages.php Directly</h2>";
try {
    require_once 'pages/ProfilePages.php';
    echo "<p style='color:green'>✓ ProfilePages.php loaded successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ ProfilePages.php Error: " . $e->getMessage() . "</p>";
    echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
}

echo "<p>✓ Debug completed</p>";
?>