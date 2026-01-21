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

$reservationId = $_POST['reservation_id'] ?? null;
if (! $reservationId) {
    http_response_code(400);
    echo json_encode(['error' => 'missing reservation id']);
    exit;
}

$db = Database::connect();
$db->beginTransaction();

try {
    // mark payment as paid (มัดจำ)
    $stmt = $db->prepare("
        UPDATE payments
        SET payment_status = 'paid',
            payment_date = NOW()
        WHERE reservation_id = ?
    ");
    $stmt->execute([$reservationId]);

    // update booking status
    $stmt = $db->prepare("
        UPDATE reservations
        SET status = 'confirmed'
        WHERE reservation_id = ?
    ");
    $stmt->execute([$reservationId]);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
