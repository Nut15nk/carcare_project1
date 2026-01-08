<?php
session_start();

require_once __DIR__ . '/../../service/Admin/AdminService.php';
use Service\Admin\AdminService;

header('Content-Type: application/json');

// basic auth check
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing customer id']);
    exit;
}

$customerId = $_GET['id'];

echo json_encode([
    'profile'      => AdminService::getCustomerProfile($customerId),
    'reservations' => AdminService::getCustomerReservations($customerId),
]);
