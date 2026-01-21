<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../service/Admin/AdminService.php';
use Service\Admin\AdminService;

header('Content-Type: application/json');

// auth check
if (! isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing reservation id']);
    exit;
}

$reservationId = $_GET['id'];

$booking = AdminService::getBookingDetail($reservationId);

if (! $booking) {
    http_response_code(404);
    echo json_encode(['error' => 'booking not found']);
    exit;
}

// ดึง summary ลูกค้า
$customerSummary = AdminService::getCustomerSummary($booking['customer_id']);

// 💥 แยก payment ออกมาให้ frontend ใช้
$payment = null;

if (! empty($booking['payment_status'])) {
    $payment = [
        'payment_status' => $booking['payment_status'],
        'payment_method' => $booking['payment_method'] ?? null,
        'payment_date'   => $booking['payment_date'] ?? null,
        'amount'         => $booking['amount'] ?? 0,
        'slip_image_url' => $booking['slip_image_url'] ?? null,
        'notes'          => $booking['notes'] ?? null,
    ];
}

echo json_encode([
    'booking'  => $booking,
    'customer' => [
        'firstName'     => $booking['firstName'] ?? '',
        'lastName'      => $booking['lastName'] ?? '',
        'email'         => $booking['email'] ?? '',
        'phone'         => $booking['phone'] ?? '',
        'totalBookings' => $customerSummary['totalBookings'] ?? 0,
        'totalSpent'    => $customerSummary['totalSpent'] ?? 0,
    ],
    'payment'  => $payment, 
]);
