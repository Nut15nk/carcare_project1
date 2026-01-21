<?php
session_start();

require_once __DIR__ . '/../../../service/Admin/AdminService.php';
use Service\Admin\AdminService;

header('Content-Type: application/json');

if (! isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing customer id']);
    exit;
}

$customerId = $_GET['id'];

$profile  = AdminService::getCustomerProfile($customerId);
$summary  = AdminService::getCustomerSummary($customerId);
$reserves = AdminService::getCustomerReservations($customerId);

if (! $profile) {
    http_response_code(404);
    echo json_encode(['error' => 'customer not found']);
    exit;
}

echo json_encode([
    'profile'      => array_merge($profile, $summary),
    'reservations' => $reserves,
]);
