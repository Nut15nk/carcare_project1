<?php
echo "<h1>Path Checker</h1>";

$paths = [
    'vendor/guzzlehttp/guzzle/src/Client.php',
    '../vendor/guzzlehttp/guzzle/src/Client.php', 
    '../../vendor/guzzlehttp/guzzle/src/Client.php',
    'vendor/autoload.php'
];

foreach ($paths as $path) {
    $exists = file_exists($path) ? "✅" : "❌";
    echo "<p>$exists $path</p>";
}

echo "<h2>Current Directory: " . __DIR__ . "</h2>";
?>