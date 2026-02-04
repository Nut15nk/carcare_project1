<?php
// pages/api/admin/customer_detail.php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../service/Admin/AdminService.php';

use Service\Admin\AdminService;

header('Content-Type: application/json');

// รับค่า customerId
$customerId = $_GET['id'] ?? '';

if (empty($customerId)) {
    http_response_code(400);
    echo json_encode(['error' => 'missing customer id']);
    exit;
}

try {
    // ดึงข้อมูลลูกค้า
    $profile = AdminService::getCustomerProfile($customerId);

    // ตรวจสอบว่าพบข้อมูลลูกค้าหรือไม่
    if (empty($profile['customerId']) || $profile['customerId'] != $customerId) {
        http_response_code(404);
        echo json_encode(['error' => 'customer not found']);
        exit;
    }

    // ดึงข้อมูลการจอง
    $reservations = AdminService::getCustomerReservations($customerId);

    // ส่งข้อมูลกลับ
    echo json_encode([
        'profile'      => $profile,
        'reservations' => $reservations,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Customer detail API error: " . $e->getMessage());
    echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
